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
    /** @var string */
    private $actionLogMessage;
    /** @var Request */
    private $request;


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

        if($isAdminEnv) {
            if(!AdminValidator::isAdmin($this->getEmployee(), AccessLevelType::SUPER_ADMIN))
            { return AdminValidator::getStandardErrorResponse(); }

            //Animal Edit from ADMIN environment
            $this->updateAsAdmin($animal, $content);

            if($animal->getLocation()) {
                //Clear cache for this location, to reflect changes on the livestock
                $this->clearLivestockCacheForLocation($animal->getLocation());
            }

            return $this->getAnimalDetailsOutputForAdminEnvironment($animal);
        }

        //User environment

        $animalDetailsValidator = new AnimalDetailsValidator($this->getManager(), false, $this->getSelectedLocation($request), $ulnString);
        if(!$animalDetailsValidator->getIsInputValid()) {
            return $animalDetailsValidator->createJsonResponse();
        }

        //Animal Edit from USER environment
        $this->updateValues($animal, $content);

        if($animal->getLocation()) {
            //Clear cache for this location, to reflect changes on the livestock
            $this->clearLivestockCacheForLocation($animal->getLocation());
        }

        return $this->getAnimalDetailsOutputForUserEnvironment($animal, $animal->getLocation());
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
     * @return Animal
     */
    private function updateAsAdmin($animal, Collection $content)
    {
        $updateString = '';

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

//        $locationArray = ArrayUtil::get(JsonInputConstant::LOCATION, $animalArray);
//        unset($animalArray[JsonInputConstant::LOCATION]);
//        $updatedLocation = $locationArray != null ? $this->getBaseSerializer()->deserializeToObject($locationArray, Location::class) : null;
        /** @var Animal $updatedAnimal */
        $updatedAnimal = $this->getBaseSerializer()->denormalizeToObject($animalArray, $clazz);

        if($updatedAnimal->getPedigreeCountryCode() != $animal->getPedigreeCountryCode() ||
            $updatedAnimal->getPedigreeNumber() != $animal->getPedigreeNumber()
        ) {
            $oldStn = $animal->getPedigreeCountryCode().$animal->getPedigreeNumber();
            $oldStn = $oldStn == '' ? 'LEEG' : $oldStn;
            $animal->setPedigreeCountryCode($updatedAnimal->getPedigreeCountryCode());
            $animal->setPedigreeNumber($updatedAnimal->getPedigreeNumber());
            $stn = $updatedAnimal->getPedigreeCountryCode().$updatedAnimal->getPedigreeNumber();
            $updateString = $updateString.'stn: '.$oldStn.' => '.$stn.', ';
        }

        if($updatedAnimal->getUlnCountryCode() != $animal->getUlnCountryCode() ||
            $updatedAnimal->getUlnNumber() != $animal->getUlnNumber()
        ) {
            $oldUln = $animal->getUlnCountryCode().$animal->getUlnNumber();
            $oldUln = $oldUln == '' ? 'LEEG' : $oldUln;
            $animal->setUlnCountryCode($updatedAnimal->getUlnCountryCode());
            $animal->setUlnNumber($updatedAnimal->getUlnNumber());
            $animal->setAnimalOrderNumber(StringUtil::getLast5CharactersFromString($updatedAnimal->getUlnNumber()));
            $uln = $updatedAnimal->getUlnCountryCode().$updatedAnimal->getUlnNumber();
            $updateString = $updateString.'uln: '.$oldUln.' => '.$uln.', ';
        }

        if($updatedAnimal->getNickname() != $animal->getNickname()) {
            $oldNickName = $animal->getNickname();
            $oldNickName = $oldNickName == '' ? 'LEEG' : $oldNickName;
            $animal->setNickname($updatedAnimal->getNickname());
            $updateString = $updateString.'nickname: '.$oldNickName.' => '.$updatedAnimal->getNickname().', ';
        }

        if($updatedAnimal->getCollarColor() != $animal->getCollarColor() ||
            $updatedAnimal->getCollarNumber() != $animal->getCollarNumber()
        ) {
            $oldCollar = $animal->getCollarColor().$animal->getCollarNumber();
            $oldCollar = $oldCollar == '' ? 'LEEG' : $oldCollar;
            $animal->setCollarColor($updatedAnimal->getCollarColor());
            $animal->setCollarNumber($updatedAnimal->getCollarNumber());
            $collar = $updatedAnimal->getCollarColor().$updatedAnimal->getCollarNumber();
            $updateString = $updateString.'halsband: '.$oldCollar.' => '.$collar.', ';
        }

        if($updatedAnimal->getBreedCode() != $animal->getBreedCode()) {
            $oldBreedCode = $animal->getBreedCode();
            $oldBreedCode = $oldBreedCode == '' ? 'LEEG' : $oldBreedCode;
            $animal->setBreedCode($updatedAnimal->getBreedCode());
            $updateString = $updateString.'rascode: '.$oldBreedCode.' => '.$updatedAnimal->getBreedCode().', ';
        }

        if($updatedAnimal->getScrapieGenotype() != $animal->getScrapieGenotype()) {
            $oldScrapieGenotype = $animal->getScrapieGenotype();
            $oldScrapieGenotype = $oldScrapieGenotype == '' ? 'LEEG' : $oldScrapieGenotype;
            $animal->setScrapieGenotype($updatedAnimal->getScrapieGenotype());
            $updateString = $updateString.'scrapieGenotype: '.$oldScrapieGenotype.' => '.$updatedAnimal->getScrapieGenotype().', ';
        }


        if(is_string($updatedAnimal->getDateOfBirth())) {
            $updatedDateOfBirth = new \DateTime($updatedAnimal->getDateOfBirth());
            $updatedAnimal->setDateOfBirth($updatedDateOfBirth);
        } elseif ($updatedAnimal->getDateOfBirth() == null) {
            $updatedDateOfBirth = null;
        } else {
            $updatedDateOfBirth = $updatedAnimal->getDateOfBirth();
        }

        if($updatedDateOfBirth != $animal->getDateOfBirth()) {
            $oldDateOfBirth = $animal->getDateOfBirthString();
            $oldDateOfBirth = $oldDateOfBirth == null ? 'LEEG' : $oldDateOfBirth;
            $animal->setDateOfBirth($updatedDateOfBirth);
            $updateString = $updateString.'geboorteDatum: '.$oldDateOfBirth.' => '. $animal->getDateOfBirthString().', ';
        }


        if(is_string($updatedAnimal->getDateOfDeath())) {
            $updatedDateOfDeath = new \DateTime($updatedAnimal->getDateOfDeath());
            $updatedAnimal->setDateOfDeath($updatedDateOfDeath);
        } elseif ($updatedAnimal->getDateOfDeath() == null) {
            $updatedDateOfDeath = null;
        } else {
            $updatedDateOfDeath = $updatedAnimal->getDateOfDeath();
        }

        if($updatedDateOfDeath != $animal->getDateOfDeath()) {
            $oldDateOfDeath = $animal->getDateOfDeathString();
            $oldDateOfDeath = $oldDateOfDeath == null ? 'LEEG' : $oldDateOfDeath;
            $animal->setDateOfDeath($updatedDateOfDeath);
            $updateString = $updateString.'sterfteDatum: '.$oldDateOfDeath.' => '. $animal->getDateOfDeathString().', ';
        }


        if($updatedAnimal->getIsAlive() != $animal->getIsAlive()) {
            $oldIsAlive = StringUtil::getBooleanAsString($animal->getIsAlive());
            $animal->setIsAlive($updatedAnimal->getIsAlive());
            $updateString = $updateString.'isLevendStatus: '. $oldIsAlive.' => '.StringUtil::getBooleanAsString($updatedAnimal->getIsAlive()).', ';
        }


        if($updatedAnimal->getNote() != $animal->getNote()) {
            $oldNote = $animal->getNote();
            $oldNote = $oldNote == '' ? 'LEEG' : $oldNote;
            $animal->setNote($updatedAnimal->getNote());
            $updateString = $updateString.'notitie: '.$oldNote.' => '.$updatedAnimal->getNote().', ';
        }

        $updatedBreedType = Translation::getEnglish($updatedAnimal->getBreedType());
        if($updatedBreedType != $animal->getBreedType()) {
            $oldBreedType = $updatedAnimal->getBreedType();
            $oldBreedType = $oldBreedType == '' ? 'LEEG' : $oldBreedType;
            $animal->setBreedType($updatedBreedType);
            $updateString = $updateString.'rasStatus: '.$oldBreedType.' => '.$updatedBreedType.', ';
        }


        if($updatedAnimal->getUbnOfBirth() != $animal->getUbnOfBirth()) {
            $oldUbnOfBirth = $animal->getUbnOfBirth();
            $oldUbnOfBirth = $oldUbnOfBirth == '' ? 'LEEG' : $oldUbnOfBirth;
            $animal->setUbnOfBirth($updatedAnimal->getUbnOfBirth());
            $updateString = $updateString.'fokkerUbn(alleen nummer): '.$oldUbnOfBirth.' => '.$updatedAnimal->getUbnOfBirth().', ';
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
            $updateString = $updateString.'fokkerUbn(LOCATIE): LEEG, ';
        } elseif($updatedLocationOfBirthUbn != null && ($locationOfBirthUbn != $updatedLocationOfBirthUbn)) {
            $locationOfBirth = $this->getManager()->getRepository(Location::class)->findOnePrioritizedByActiveUbn($updatedLocationOfBirthUbn);
            if($locationOfBirth) {
                $animal->setLocationOfBirth($locationOfBirth);
                $updateString = $updateString.'fokkerUbn(LOCATIE): '.$locationOfBirthUbn.' => '.$updatedLocationOfBirthUbn.', ';
            } else {
                $updateString = $updateString.'fokkerUbn(LOCATIE): '.$locationOfBirthUbn.' => '.$updatedLocationOfBirthUbn.'(bestaat niet in database) , ';
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
        if($updatedPedigreeRegisterId == null && $pedigreeRegisterId != null) {
            $animal->setPedigreeRegister(null);
            $updateString = $updateString.'stamboek: '.$oldPedigreeRegisterAbbreviation.' => LEEG, ';
        } elseif($updatedPedigreeRegisterId != null && ($pedigreeRegisterId != $updatedPedigreeRegisterId)) {
            $pedigreeRegister = $this->getManager()->getRepository(PedigreeRegister::class)->find($updatedPedigreeRegisterId);
            if($pedigreeRegister) {
                $animal->setPedigreeRegister($pedigreeRegister);
                $updateString = $updateString.'stamboek: '.$oldPedigreeRegisterAbbreviation.' => '.$pedigreeRegister->getAbbreviation().', ';
            } else {
                $updateString = $updateString.'stamboek: met id '.$oldPedigreeRegisterAbbreviation.' => '.$updatedPedigreeRegisterId.' niet in database, ';
            }
        }

        $updateString = rtrim($updateString, ', ');

        if(trim($updateString) != '') {
            $log = ActionLogWriter::updateAnimalDetailsAdminEnvironment($this->getManager(), $this->getUser(), $updateString);
            $this->getManager()->persist($animal);
            $this->getManager()->flush();
        }

        return $animal;
    }



    private function clearActionLogMessage()
    {
        $this->actionLogMessage = '';
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
        }
    }


    private function saveActionLogMessage()
    {
        ActionLogWriter::editAnimalDetails($this->getManager(), $this->getAccountOwner($this->request),
                                           $this->getUser(), $this->actionLogMessage,true);
    }
}