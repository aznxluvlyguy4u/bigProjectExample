<?php


namespace AppBundle\Service;


use AppBundle\Cache\GeneDiversityUpdater;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Ram;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Output\AnimalDetailsOutput;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\DateUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Validation\AdminValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AnimalDetailsUpdaterService extends ControllerServiceBase
{
    const LOG_EMPTY = 'LEEG';

    const ERROR_ULN_ALREADY_EXISTS = 'Het opgegeven nieuwe uln is al in gebruik bij een ander dier';
    const ERROR_LOCATION_NOT_FOUND = 'Er is geen locatie gevonden met ubn: ';

    /* Surrogate mother error messages */
    const SURROGATE_MOTHER_NO_EWE_FOUND_FOR_GIVEN_ID = 'SURROGATE_MOTHER_NO_EWE_FOUND_FOR_GIVEN_ID';
    const SURROGATE_MOTHER_IS_SAME_AS_CHILD = 'SURROGATE_MOTHER_IS_SAME_AS_CHILD';
    const SURROGATE_MOTHER_IS_YOUNGER_THAN_CHILD = 'SURROGATE_MOTHER_IS_YOUNGER_THAN_CHILD';

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

    /** @var array */
    private $errors;

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
        $this->errors = [];

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

        return ResultUtil::successResult(
            $this->animalDetailsOutput->getForUserEnvironment(
                $animal,
                $user,
                $this->getSelectedLocation($request)
            )
        );
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
        if($content->containsKey(JsonInputConstant::COLLAR)) {
            $collar = $content->get(JsonInputConstant::COLLAR);
            $newCollarNumber = StringUtil::convertEmptyStringToNull(ArrayUtil::get(JsonInputConstant::NUMBER, $collar));
            $newCollarColor = StringUtil::convertEmptyStringToNull(ArrayUtil::get(JsonInputConstant::COLOR, $collar));

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

        if ($content->containsKey(JsonInputConstant::PREDICATE_DETAILS)) {
            $predicateContent = $content->get(JsonInputConstant::PREDICATE_DETAILS);

            $newPredicate = StringUtil::convertEmptyStringToNull(ArrayUtil::get(JsonInputConstant::TYPE, $predicateContent));
            $newPredicateScore = ArrayUtil::get(JsonInputConstant::SCORE, $predicateContent);
            $oldPredicate = $animal->getPredicate();
            $oldPredicateScore = $animal->getPredicateScore();

            // TODO VALIDATE PREDICATE INPUT

            if ($oldPredicate != $newPredicate) {
                $animal->setPredicate($newPredicate);
                $animal->setPreviousPredicate($oldPredicate);
                $animal->setPredicateUpdatedAt(new \DateTime());
                $anyValueWasUpdated = true;
                $this->updateActionLogMessage('predikaat', $oldPredicate, $newPredicate);
            }

            if ($oldPredicateScore != $newPredicateScore) {
                $animal->setPredicateScore($newPredicateScore);
                $animal->setPreviousPredicateScore($oldPredicateScore);
                $animal->setPredicateUpdatedAt(new \DateTime());
                $anyValueWasUpdated = true;
                $this->updateActionLogMessage('predikaat score', $oldPredicateScore, $newPredicateScore);
            }
        }


        $newBlindnessFactor = StringUtil::convertEmptyStringToNull($content->get(JsonInputConstant::BLINDNESS_FACTOR));
        $oldBlindnessFactor = $animal->getBlindnessFactor();

        // TODO VALIDATE BLINDNESS FACTOR INPUT

        if ($oldBlindnessFactor != $newBlindnessFactor) {
            $animal->setBlindnessFactor($newBlindnessFactor);
            $anyValueWasUpdated = true;
            $this->updateActionLogMessage('blindfactor', $oldBlindnessFactor, $newBlindnessFactor);
        }

        $newBirthProcess = StringUtil::convertEmptyStringToNull($content->get(JsonInputConstant::BIRTH_PROGRESS));
        $oldBirthProcess = $animal->getBirthProgress();

        // TODO VALIDATE BIRTH PROCESS INPUT

        if ($oldBirthProcess != $newBirthProcess) {
            $animal->setBirthProgress($newBirthProcess);
            $anyValueWasUpdated = true;
            $this->updateActionLogMessage('geboorteproces', $oldBirthProcess, $newBirthProcess);
        }

        $newRearing = StringUtil::convertEmptyStringToNull($content->get(JsonInputConstant::REARING));

        if ($newRearing === 'LAMBAR') {
            $newSurrogateId = null;
            $newLambar = true;
        } elseif (ctype_digit($newRearing) || is_int($newRearing)) {
            $newSurrogateId = intval($newRearing);
            $newLambar = false;
        } else {
            $newSurrogateId = null;
            $newLambar = false;
        }

        $oldSurrogateId = $animal->getSurrogate() ? $animal->getSurrogate()->getId() : null;
        $oldLambar = $animal->getLambar();

        if ($oldSurrogateId !== $newSurrogateId) {

            $newSurrogate = $this->getValidatedSurrogateInput($animal, $newSurrogateId);
            if ($newSurrogate != null || ($oldSurrogateId != null && $newSurrogateId == null)) {
                $animal->setSurrogate($newSurrogate);
                $anyValueWasUpdated = true;
                $this->updateActionLogMessage('pleegmoeder', $oldSurrogateId, $newSurrogateId);
            }
        }

        if (!$this->hasSurrogateInputError() && $oldLambar != $newLambar) {
            $animal->setLambar($newLambar);
            $anyValueWasUpdated = true;
            $this->updateActionLogMessage('lambar', $oldLambar, $newLambar);
        }


        $this->checkForValidationErrors();

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


    private function checkForValidationErrors()
    {
        if (empty($this->errors)) {
            return;
        }

        $errorMessage = '';
        $prefix = '';
        foreach ($this->errors as $error => $errorValue) {
            $errorMessage = $prefix . $this->translator->trans($error);
            if ($errorValue !== 0) {
                $errorMessage .= ': '.$errorValue;
            }
            $prefix = ', ';
        }
        $errorMessage .= '.';

        throw new BadRequestHttpException($errorMessage);
    }


    private function getValidatedSurrogateInput(Animal $animal, ?int $newSurrogateId): ?Ewe
    {
        if ($newSurrogateId === null) {
            return null;
        }

        /** @var Ewe $newSurrogate */
        $newSurrogate = $this->getManager()->getRepository(Ewe::class)->find($newSurrogateId);

        $isValidSurrogateInput = true;

        if (!$newSurrogate) {
            $this->errors[self::SURROGATE_MOTHER_NO_EWE_FOUND_FOR_GIVEN_ID] = $newSurrogateId;
            $isValidSurrogateInput = false;
        }

        if ($newSurrogateId === $animal->getId()) {
            $this->errors[self::SURROGATE_MOTHER_IS_SAME_AS_CHILD] = $newSurrogateId;
            $isValidSurrogateInput = false;
        }

        if (TimeUtil::isDate1BeforeDate2($newSurrogate->getDateOfBirth(), $animal->getDateOfBirth())) {
            $this->errors[self::SURROGATE_MOTHER_IS_YOUNGER_THAN_CHILD] = $newSurrogate->getDateOfBirthString();
            $isValidSurrogateInput = false;
        }

        return $isValidSurrogateInput ? $newSurrogate : null;
    }

    private function hasSurrogateInputError(): bool
    {
        return key_exists(self::SURROGATE_MOTHER_IS_SAME_AS_CHILD, $this->errors) ||
            key_exists(self::SURROGATE_MOTHER_IS_YOUNGER_THAN_CHILD, $this->errors) ||
            key_exists(self::SURROGATE_MOTHER_NO_EWE_FOUND_FOR_GIVEN_ID, $this->errors);
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
