<?php


namespace AppBundle\Service;


use AppBundle\Cache\GeneDiversityUpdater;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\PedigreeRegister;
use AppBundle\Entity\Ram;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Output\AnimalDetailsOutput;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\Translation;
use AppBundle\Validation\AdminValidator;
use AppBundle\Validation\AnimalDetailsValidator;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AnimalDetailsUpdaterService extends ControllerServiceBase
{
    const LOG_EMPTY = 'LEEG';

    const ERROR_ULN_ALREADY_EXISTS = 'Het opgegeven nieuwe uln is al in gebruik bij een ander dier';
    const ERROR_LOCATION_NOT_FOUND = 'Er is geen locatie gevonden met ubn: ';

    /* Parent error messages */
    const ERROR_NOT_FOUND = 'ERROR_NOT_FOUND';
    const ERROR_INCORRECT_GENDER = 'ERROR_INCORRECT_GENDER';
    const ERROR_ULN_IDENTICAL_TO_CHILD = 'ERROR_ULN_IDENTICAL_TO_CHILD';
    const ERROR_PARENT_YOUNGER_THAN_CHILD = 'ERROR_PARENT_YOUNGER_THAN_CHILD';

    private $parentErrors = [
        Ram::class => [
            self::ERROR_NOT_FOUND => 'Geen vader gevonden voor gegeven uln: ',
            self::ERROR_INCORRECT_GENDER => 'Voor de vader is een dier gevonden dat geen ram is.',
            self::ERROR_ULN_IDENTICAL_TO_CHILD => 'De vader mag geen uln hebben wat identiek is aan het kind',
            self::ERROR_PARENT_YOUNGER_THAN_CHILD => 'De geboortedatum van de vader is later dan die van het kind',
        ],
        Ewe::class => [
            self::ERROR_NOT_FOUND => 'Geen moeder gevonden voor gegeven uln: ',
            self::ERROR_INCORRECT_GENDER => 'Voor de moeder is een dier gevonden dat geen ooi is.',
            self::ERROR_ULN_IDENTICAL_TO_CHILD => 'De moeder mag geen uln hebben wat identiek is aan het kind',
            self::ERROR_PARENT_YOUNGER_THAN_CHILD => 'De geboortedatum van de moeder is later dan die van het kind',
        ]
    ];


    /** @var string */
    private $animalIdLogPrefix;
    /** @var string */
    private $actionLogMessage;
    /** @var Request */
    private $request;
    /** @var boolean */
    private $anyValueWasUpdated;


    /**
     * @param Request $request
     * @param $ulnString
     * @return JsonResponse
     */
    public function updateAnimalDetails(Request $request, $ulnString)
    {
        $this->request = $request;

        //Get content to array
        $content = RequestUtil::getContentAsArray($request);
        /** @var Animal $animal */
        $animal = $this->getManager()->getRepository(Animal::class)->findAnimalByUlnString($ulnString);

        $isAdminEnv = $content->get(JsonInputConstant::IS_ADMIN_ENV);

        if($animal == null) {
            if($this->getUser() instanceof Employee) {
                $errorMessage = "No animal was found with uln: ".$ulnString;
            } else {
                //For regular users, hide the fact that the animal does not exist in the database at all.
                $errorMessage = "For this account, no animal was found with uln: ".$ulnString;
            }
            return ResultUtil::errorResult($errorMessage, Response::HTTP_NOT_FOUND);
        }

        $this->extractAnimalIdData($animal);

        if($isAdminEnv) {
            if(!AdminValidator::isAdmin($this->getEmployee(), AccessLevelType::SUPER_ADMIN))
            { return AdminValidator::getStandardErrorResponse(); }

            //Animal Edit from ADMIN environment
            $animal = $this->updateAsAdmin($animal, $content);
            if ($animal instanceof JsonResponse) {
                return $animal;
            }

            if($animal->getLocation()) {
                //Clear cache for this location, to reflect changes on the livestock
                $this->clearLivestockCacheForLocation($animal->getLocation());
            }

            return $this->getAnimalDetailsOutputForAdminEnvironment($animal);
        }

        //User environment
        $user = $this->getUser();

        if(!$user instanceof Employee) {
            $animalOwner = $animal->getOwner();
            if ($animalOwner !== $user && $animalOwner !== $user->getEmployer()) {
                $message = 'Dit dier is op dit moment niet in uw bezit en u bent niet door de huidige eigenaar geautoriseerd,'
                    .' dus het is niet toegestaan voor u om de gegevens aan te passen.';
                return ResultUtil::errorResult($message, Response::HTTP_UNAUTHORIZED);
            }
        }

        //Animal Edit from USER environment
        $this->updateValues($animal, $content);

        //Clear cache for this location, to reflect changes on the livestock
        if($animal->getLocation()) {
            //Clear cache for this location, to reflect changes on the livestock
            $this->clearLivestockCacheForLocation($animal->getLocation());
        }

        return $this->getAnimalDetailsOutputForUserEnvironment($animal);
    }


    /**
     * @param Animal $animal
     * @param Collection $content
     * @return Animal
     */
    private function updateValues($animal, Collection $content)
    {
        if(!($animal instanceof Animal)){ return $animal; }

        //Keep track if any changes were made
        $anyValueWasUpdated = false;

        $this->clearActionLogMessage();

        //Collar color & number
        if($content->containsKey('collar')) {
            $collar = $content->get('collar');
            $newCollarNumber = ArrayUtil::get('number',$collar);
            $newCollarColor = ArrayUtil::get('color',$collar);

            $oldCollarColor = $animal->getCollarColor();
            $oldCollarNumber = $animal->getCollarNumber();

            if($oldCollarColor != $newCollarColor) {
                $animal->setCollarColor($newCollarColor);
                $anyValueWasUpdated = true;

                $this->updateActionLogMessage('halsbandkleur', $oldCollarColor, $newCollarColor);
            }

            if($oldCollarNumber != $newCollarNumber) {
                $animal->setCollarNumber($newCollarNumber);
                $anyValueWasUpdated = true;

                $this->updateActionLogMessage('halsbandnr', $oldCollarNumber, $newCollarNumber);
            }

        }

        //Only update animal in database if any values were actually updated
        if($anyValueWasUpdated) {
            $this->getManager()->persist($animal);
            $this->getManager()->flush();

            $this->saveActionLogMessage();
        }

        //TODO if breedCode was updated toggle $isBreedCodeUpdated boolean to true
        $isBreedCodeUpdated = false;
        if($isBreedCodeUpdated) {
            //Update heterosis and recombination values of parent and children if breedCode of parent was changed
            GeneDiversityUpdater::updateByParentId($this->getConnection(), $animal->getId());
        }

        return $animal;
    }


    /**
     * @param Animal $animal
     * @param Collection $content
     * @return Animal|JsonResponse
     * @throws \Exception
     */
    private function updateAsAdmin($animal, Collection $content)
    {
        $this->clearActionLogMessage();

        $animalArray = $content->get(JsonInputConstant::ANIMAL);
        if($animalArray == null || !$animal instanceof Animal) {
            return null;
        }

        $animalArray = $content->get(JsonInputConstant::ANIMAL);
        $type = ArrayUtil::get(JsonInputConstant::TYPE, $animalArray);

        switch ($type) {
            case ucfirst(JsonInputConstant::EWE): $clazz = Ewe::class; break;
            case ucfirst(JsonInputConstant::RAM): $clazz = Ram::class; break;
            case ucfirst(JsonInputConstant::NEUTER): $clazz = Neuter::class; break;
            default: return null;
        }

        /** @var Animal $updatedAnimal */
        $updatedAnimal = $this->getBaseSerializer()->denormalizeToObject($animalArray, $clazz);


        /* Update Parents */

        foreach ([Ram::class, Ewe::class] as $parentClazz)
        {
            $currentParent = $animal->getParent($parentClazz);
            $newParent = $updatedAnimal->getParent($parentClazz);

            if ($this->hasParentChanged($currentParent, $newParent)) {
                $ulnStringCurrentParent = $currentParent ? $currentParent->getUln() : self::LOG_EMPTY;
                if ($newParent) {
                    $ulnStringNewParent = $newParent->getUln();
                    if ($animal->getUln() === $ulnStringNewParent) {
                        return $this->getParentErrorResponse(self::ERROR_ULN_IDENTICAL_TO_CHILD, $parentClazz);
                    }

                    $parent = $this->getManager()->getRepository(Animal::class)->findAnimalByUlnString($ulnStringNewParent);

                    if ($parent === null) {
                        return $this->getParentErrorResponse(self::ERROR_NOT_FOUND, $parentClazz, $ulnStringNewParent);
                    }

                    if (
                        ($parentClazz === Ram::class && !($parent instanceof Ram)) ||
                        ($parentClazz === Ewe::class && !($parent instanceof Ewe))
                    ) {
                        return $this->getParentErrorResponse(self::ERROR_INCORRECT_GENDER, $parentClazz);
                    }

                    if ($animal->getDateOfBirth() && $parent->getDateOfBirth()) {
                        if ($animal->getDateOfBirth() < $parent->getDateOfBirth()) {
                            return $this->getParentErrorResponse(self::ERROR_PARENT_YOUNGER_THAN_CHILD, $parentClazz);
                        }
                    }

                    $animal->setParent($parent);

                } else {
                    $ulnStringNewParent = self::LOG_EMPTY;
                    $animal->removeParent($parentClazz);
                }

                switch ($parentClazz) {
                    case Ram::class: $dutchParentType = 'vader'; break;
                    case Ewe::class: $dutchParentType = 'moeder'; break;
                    default:
                        throw new \Exception('Invalid parent type.' ,  428);
                }

                $this->updateActionLogMessage($dutchParentType, $ulnStringCurrentParent, $ulnStringNewParent);
            }

        }


        $updateLocation = false;
        if ($updatedAnimal->getLocation()) {
            if ($animal->getLocation()) {
                if ($animal->getLocation()->getUbn() !== $updatedAnimal->getLocation()->getUbn()) {
                    $updateLocation = true;
                }
            } else {
                $updateLocation = true;
            }
        } elseif ($animal->getLocation()) {
            $this->updateActionLogMessage('ubn', $animal->getLocation()->getUbn(), self::LOG_EMPTY);
            $animal->setLocation(null);
        }

        if ($updateLocation) {
            $ubnNewLocation = $updatedAnimal->getLocation()->getUbn();
            $newLocation = $this->getManager()->getRepository(Location::class)->findOnePrioritizedByActiveUbn($ubnNewLocation);
            if ($newLocation === null) {
                return ResultUtil::errorResult(self::ERROR_LOCATION_NOT_FOUND.$ubnNewLocation, Response::HTTP_PRECONDITION_REQUIRED);
            }

            $ubnCurrentLocation = $animal->getLocation() ? $animal->getLocation()->getUbn() : self::LOG_EMPTY;

            $this->updateActionLogMessage('ubn', $ubnCurrentLocation, $ubnNewLocation);
            $animal->setLocation($newLocation);
        }


        if($updatedAnimal->getPedigreeCountryCode() !== $animal->getPedigreeCountryCode() ||
            $updatedAnimal->getPedigreeNumber() !== $animal->getPedigreeNumber()
        ) {
            $oldStn = $animal->getPedigreeCountryCode().$animal->getPedigreeNumber();
            $oldStn = $oldStn == '' ? 'LEEG' : $oldStn;
            $animal->setPedigreeCountryCode($updatedAnimal->getPedigreeCountryCode());
            $animal->setPedigreeNumber($updatedAnimal->getPedigreeNumber());
            $stn = $updatedAnimal->getPedigreeCountryCode().$updatedAnimal->getPedigreeNumber();
            $this->updateActionLogMessage('stn', $oldStn, $stn);
        }

        if($updatedAnimal->getUlnCountryCode() !== $animal->getUlnCountryCode() ||
            $updatedAnimal->getUlnNumber() !== $animal->getUlnNumber()
        ) {
            if ($this->getManager()->getRepository(Animal::class)->findAnimalByUlnString($updatedAnimal->getUln())) {
                return ResultUtil::errorResult(self::ERROR_ULN_ALREADY_EXISTS, Response::HTTP_PRECONDITION_REQUIRED);
            }
            $oldUln = $animal->getUlnCountryCode().$animal->getUlnNumber();
            $oldUln = $oldUln == '' ? 'LEEG' : $oldUln;
            $animal->setUlnCountryCode($updatedAnimal->getUlnCountryCode());
            $animal->setUlnNumber($updatedAnimal->getUlnNumber());
            $animal->setAnimalOrderNumber(StringUtil::getLast5CharactersFromString($updatedAnimal->getUlnNumber()));
            $uln = $updatedAnimal->getUlnCountryCode().$updatedAnimal->getUlnNumber();
            $this->updateActionLogMessage('uln', $oldUln, $uln);
        }

        if($updatedAnimal->getNickname() !== $animal->getNickname()) {
            $oldNickName = $animal->getNickname();
            $oldNickName = $oldNickName == '' ? 'LEEG' : $oldNickName;
            $animal->setNickname($updatedAnimal->getNickname());
            $this->updateActionLogMessage('nickname', $oldNickName, $updatedAnimal->getNickname());
        }

        if($updatedAnimal->getCollarColor() !== $animal->getCollarColor() ||
            $updatedAnimal->getCollarNumber() !== $animal->getCollarNumber()
        ) {
            $oldCollar = $animal->getCollarColor().$animal->getCollarNumber();
            $oldCollar = $oldCollar == '' ? 'LEEG' : $oldCollar;
            $animal->setCollarColor($updatedAnimal->getCollarColor());
            $animal->setCollarNumber($updatedAnimal->getCollarNumber());
            $collar = $updatedAnimal->getCollarColor().$updatedAnimal->getCollarNumber();
            $this->updateActionLogMessage('halsband', $oldCollar, $collar);
        }

        if($updatedAnimal->getBreedCode() !== $animal->getBreedCode()) {
            $oldBreedCode = $animal->getBreedCode();
            $oldBreedCode = $oldBreedCode == '' ? 'LEEG' : $oldBreedCode;
            $animal->setBreedCode($updatedAnimal->getBreedCode());
            $this->updateActionLogMessage('rascode', $oldBreedCode, $updatedAnimal->getBreedCode());
        }

        if($updatedAnimal->getScrapieGenotype() !== $animal->getScrapieGenotype()) {
            $oldScrapieGenotype = $animal->getScrapieGenotype();
            $oldScrapieGenotype = $oldScrapieGenotype == '' ? 'LEEG' : $oldScrapieGenotype;
            $animal->setScrapieGenotype($updatedAnimal->getScrapieGenotype());
            $this->updateActionLogMessage('scrapieGenotype', $oldScrapieGenotype, $updatedAnimal->getScrapieGenotype());
        }


        if(is_string($updatedAnimal->getDateOfBirth())) {
            $updatedDateOfBirth = new \DateTime($updatedAnimal->getDateOfBirth());
            $updatedAnimal->setDateOfBirth($updatedDateOfBirth);
        } elseif ($updatedAnimal->getDateOfBirth() == null) {
            $updatedDateOfBirth = null;
        } else {
            $updatedDateOfBirth = $updatedAnimal->getDateOfBirth();
        }

        if($updatedDateOfBirth !== $animal->getDateOfBirth()) {
            $oldDateOfBirth = $animal->getDateOfBirthString();
            $oldDateOfBirth = $oldDateOfBirth == null ? 'LEEG' : $oldDateOfBirth;
            $animal->setDateOfBirth($updatedDateOfBirth);
            $this->updateActionLogMessage('geboorteDatum', $oldDateOfBirth, $animal->getDateOfBirthString());
        }


        if(is_string($updatedAnimal->getDateOfDeath())) {
            $updatedDateOfDeath = new \DateTime($updatedAnimal->getDateOfDeath());
            $updatedAnimal->setDateOfDeath($updatedDateOfDeath);
        } elseif ($updatedAnimal->getDateOfDeath() == null) {
            $updatedDateOfDeath = null;
        } else {
            $updatedDateOfDeath = $updatedAnimal->getDateOfDeath();
        }

        if($updatedDateOfDeath !== $animal->getDateOfDeath()) {
            $oldDateOfDeath = $animal->getDateOfDeathString();
            $oldDateOfDeath = $oldDateOfDeath == null ? 'LEEG' : $oldDateOfDeath;
            $animal->setDateOfDeath($updatedDateOfDeath);
            $this->updateActionLogMessage('sterfteDatum', $oldDateOfDeath, $animal->getDateOfDeathString());
        }


        if($updatedAnimal->getIsAlive() !== $animal->getIsAlive()) {
            $oldIsAlive = StringUtil::getBooleanAsString($animal->getIsAlive());
            $animal->setIsAlive($updatedAnimal->getIsAlive());
            $this->updateActionLogMessage('isLevendStatus', $oldIsAlive, StringUtil::getBooleanAsString($updatedAnimal->getIsAlive()));
        }


        if($updatedAnimal->getNote() !== $animal->getNote()) {
            $oldNote = $animal->getNote();
            $oldNote = $oldNote == '' ? 'LEEG' : $oldNote;
            $animal->setNote($updatedAnimal->getNote());
            $this->updateActionLogMessage('notitie', $oldNote, $updatedAnimal->getNote());
        }

        $updatedBreedType = Translation::getEnglish($updatedAnimal->getBreedType());
        if($updatedBreedType !== $animal->getBreedType()) {
            $oldBreedType = $updatedAnimal->getBreedType();
            $oldBreedType = $oldBreedType == '' ? 'LEEG' : $oldBreedType;
            $animal->setBreedType($updatedBreedType);
            $this->updateActionLogMessage('rasStatus', $oldBreedType, $updatedBreedType);
        }


        if($updatedAnimal->getUbnOfBirth() !== $animal->getUbnOfBirth()) {
            $oldUbnOfBirth = $animal->getUbnOfBirth();
            $oldUbnOfBirth = $oldUbnOfBirth == '' ? 'LEEG' : $oldUbnOfBirth;
            $animal->setUbnOfBirth($updatedAnimal->getUbnOfBirth());
            $this->updateActionLogMessage('fokkerUbn(alleen nummer)', $oldUbnOfBirth, $updatedAnimal->getUbnOfBirth());
        }


        $updatedLocationOfBirthUbn = null;
        if($updatedAnimal->getLocationOfBirth()) {
            if(is_array($updatedAnimal->getLocationOfBirth())) {
                /** @var Location $updatedLocationOfBirth */
                $updatedLocationOfBirth = $this->getBaseSerializer()->deserializeToObject($updatedAnimal->getLocationOfBirth(), Location::class);
                $updatedLocationOfBirth->setLocationId(null);
                $updatedAnimal->setLocationOfBirth($updatedLocationOfBirth);
            }
            $updatedLocationOfBirthUbn = $updatedAnimal->getLocationOfBirth()->getUbn();
        }
        $locationOfBirthUbn = 'LEEG';
        if($animal->getLocationOfBirth()) {
            $locationOfBirthUbn = $animal->getLocationOfBirth()->getUbn();
        }
        if($updatedLocationOfBirthUbn == null && $locationOfBirthUbn != null) {
            $animal->setLocationOfBirth(null);
            $this->updateActionLogMessage('fokkerUbn(LOCATIE)', $locationOfBirthUbn, 'LEEG');
        } elseif($updatedLocationOfBirthUbn !== null && ($locationOfBirthUbn !== $updatedLocationOfBirthUbn)) {
            $locationOfBirth = $this->getManager()->getRepository(Location::class)->findOnePrioritizedByActiveUbn($updatedLocationOfBirthUbn);
            if($locationOfBirth) {
                $animal->setLocationOfBirth($locationOfBirth);
                $this->updateActionLogMessage('fokkerUbn(LOCATIE)', $locationOfBirthUbn, $updatedLocationOfBirthUbn);
            } else {
                //$this->updateActionLogMessage('fokkerUbn(LOCATIE)', $locationOfBirthUbn, $updatedLocationOfBirthUbn.'(bestaat niet in database)');
            }
        }


        $updatedPedigreeRegisterId = null;
        if($updatedAnimal->getPedigreeRegister()) {
            if(is_array($updatedAnimal->getPedigreeRegister())) {
                /** @var PedigreeRegister $updatedPedigreeRegister */
                $updatedPedigreeRegister = $this->getBaseSerializer()->deserializeToObject($updatedAnimal->getPedigreeRegister(), PedigreeRegister::class);
                $updatedAnimal->setPedigreeRegister($updatedPedigreeRegister);
            }
            $updatedPedigreeRegisterId = $updatedAnimal->getPedigreeRegister()->getId();
        }
        $pedigreeRegisterId = null;
        $oldPedigreeRegisterAbbreviation = 'LEEG';
        if($animal->getPedigreeRegister()) {
            $pedigreeRegisterId = $animal->getPedigreeRegister()->getId();
            $oldPedigreeRegisterAbbreviation = $animal->getPedigreeRegister()->getAbbreviation();
        }
        if($updatedPedigreeRegisterId === null && $pedigreeRegisterId !== null) {
            $animal->setPedigreeRegister(null);
            $this->updateActionLogMessage('stamboek', $oldPedigreeRegisterAbbreviation, 'LEEG');
        } elseif($updatedPedigreeRegisterId != null && ($pedigreeRegisterId != $updatedPedigreeRegisterId)) {
            $pedigreeRegister = $this->getManager()->getRepository(PedigreeRegister::class)->find($updatedPedigreeRegisterId);
            if($pedigreeRegister) {
                $animal->setPedigreeRegister($pedigreeRegister);
                $this->updateActionLogMessage('stamboek', $oldPedigreeRegisterAbbreviation, $pedigreeRegister->getAbbreviation());
            } else {
                $this->updateActionLogMessage('stamboek met id', $oldPedigreeRegisterAbbreviation, $updatedPedigreeRegisterId.' niet in database');
            }
        }

        if($this->anyValueWasUpdated) {
            $this->saveAdminActionLogMessage();
            $this->getManager()->persist($animal);
            $this->getManager()->flush();
        }

        return $animal;
    }


    /**
     * @param Animal $currentParent
     * @param Animal $newParent
     * @return bool
     */
    private function hasParentChanged($currentParent, $newParent)
    {
        if ($newParent) {
            if ($currentParent) {
                $hasParentChanged = $currentParent->getUln() !== $newParent->getUln();
            } else {
                $hasParentChanged = true;
            }

        } else {
            $hasParentChanged = $currentParent !== null;
        }

        return $hasParentChanged;
    }


    /**
     * @param string $key
     * @param string $parentClazz
     * @param string $data
     * @return JsonResponse
     * @throws \Exception
     */
    private function getParentErrorResponse($key, $parentClazz, $data = '')
    {
        if ($parentClazz !== Ewe::class && $parentClazz !== Ram::class) {
            throw new \Exception('Parent is not a Ram or Ewe', 428);
        }

        return ResultUtil::errorResult(
            $this->parentErrors[$parentClazz][$key].$data,
            Response::HTTP_PRECONDITION_REQUIRED
        );
    }


    /**
     * @param Animal $animal
     */
    private function extractAnimalIdData(Animal $animal)
    {
        $this->animalIdLogPrefix = 'animal[id: '.$animal->getId() . ', uln: ' . $animal->getUln().']: ';
    }


    private function clearActionLogMessage()
    {
        $this->actionLogMessage = '';
        $this->anyValueWasUpdated = false;
    }


    /**
     * @param $type
     * @param $oldValue
     * @param $newValue
     */
    private function updateActionLogMessage($type, $oldValue, $newValue)
    {
        if ($oldValue !== $newValue) {
            $prefix = $this->actionLogMessage === '' ? '' : ', ';
            $this->actionLogMessage = $this->actionLogMessage . $prefix . $type . ': '.$oldValue.' => '.$newValue;
            $this->anyValueWasUpdated = true;
        }
    }


    private function saveActionLogMessage()
    {
        ActionLogWriter::editAnimalDetails($this->getManager(), $this->getAccountOwner($this->request),
            $this->getUser(), $this->animalIdLogPrefix . $this->actionLogMessage,true);
    }


    private function saveAdminActionLogMessage()
    {
        ActionLogWriter::updateAnimalDetailsAdminEnvironment($this->getManager(), $this->getUser(), $this->animalIdLogPrefix .$this->actionLogMessage);
    }
}