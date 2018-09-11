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
use AppBundle\Validation\AdminValidator;
use Doctrine\Common\Collections\ArrayCollection;
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

    /** @var AnimalDetailsBatchUpdaterService */
    private $animalDetailsBatchUpdater;
    /** @var AnimalDetailsOutput */
    private $animalDetailsOutput;

    /**
     * @required
     *
     * @param AnimalDetailsBatchUpdaterService $animalDetailsBatchUpdater
     */
    public function setAnimalDetailsBatchUpdater(AnimalDetailsBatchUpdaterService $animalDetailsBatchUpdater)
    {
        $this->animalDetailsBatchUpdater = $animalDetailsBatchUpdater;
    }


    /**
     * @required
     *
     * @param AnimalDetailsOutput $animalDetailsOutput
     */
    public function setAnimalDetailsOutput(AnimalDetailsOutput $animalDetailsOutput)
    {
        $this->animalDetailsOutput = $animalDetailsOutput;
    }


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

        return ResultUtil::successResult($this->animalDetailsOutput->getForUserEnvironment($animal, $user));
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
            $newCollarNumber = StringUtil::convertEmptyStringToNull(ArrayUtil::get('number',$collar));
            $newCollarColor = StringUtil::convertEmptyStringToNull(ArrayUtil::get('color',$collar));

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
     */
    private function updateAsAdmin($animal, Collection $content)
    {
        $this->clearActionLogMessage();

        $animalArray = $content->get(JsonInputConstant::ANIMAL);
        if($animalArray == null || !$animal instanceof Animal) {
            return null;
        }

        $content = new ArrayCollection();
        $content->set(JsonInputConstant::ANIMALS, [$animalArray]);

        $updateResults = $this->animalDetailsBatchUpdater->updateAnimalDetailsByArrayCollection($content);
        if ($updateResults instanceof JsonResponse) {
            return $updateResults;
        }

        $animals = $updateResults[JsonInputConstant::ANIMALS];

        if (count($animals[JsonInputConstant::UPDATED]) > 0) {
            return array_pop($animals[JsonInputConstant::UPDATED]);
        }

        if (count($animals[JsonInputConstant::NOT_UPDATED]) > 0) {
            return array_pop($animals[JsonInputConstant::NOT_UPDATED]);
        }

        return ResultUtil::errorResult('SOMETHING WENT WRONG', Response::HTTP_INTERNAL_SERVER_ERROR);
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

}