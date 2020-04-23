<?php


namespace AppBundle\Service;


use AppBundle\Cache\GeneDiversityUpdater;
use AppBundle\Cache\NLingCacher;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
use AppBundle\Entity\PedigreeRegister;
use AppBundle\Entity\Ram;
use AppBundle\Enumerator\QueryType;
use AppBundle\model\ParentIdsPair;
use AppBundle\Service\InbreedingCoefficient\InbreedingCoefficientUpdaterService;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\LitterUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Util\Translation;
use AppBundle\Util\Validator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\DBALException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AnimalDetailsBatchUpdaterService extends ControllerServiceBase
{
    /** @var string */
    private $currentActionLogMessage;
    /** @var string */
    private $currentAnimalIdLogPrefix;
    /** @var boolean */
    private $anyValueWasUpdated;

    /** @var boolean */
    private $anyAnimalWasRelocated;
    /** @var array|Location[] */
    private $locationsWithRelocations;

    /** @var boolean */
    private $anyCurrentAnimalValueWasUpdated;
    /** @var boolean */
    private $updateInbreedingCoefficient = false;
    /** @var array */
    private $animalsIdsForWhichInbreedingCoefficientShouldBeUpdated = [];
    /** @var Ram|Ewe[] */
    private $newParentsById;
    /** @var Location[] */
    private $retrievedLocationsByLocationId;
    /** @var PedigreeRegister[] */
    private $retrievedPedigreeRegistersById;
    /** @var array */
    private $parentIdsForWhichTheirAndTheirChildrenGeneticDiversityValuesShouldBeUpdated;
    /** @var array */
    private $parentValidationErrorSets;

    /** @var array */
    private $animalIdsWithUpdatedNLingValues;

    /** @var array */
    private $changedSurrogateMothers;

    /** @var InbreedingCoefficientUpdaterService */
    private $inbreedingCoefficientUpdaterService;

    /* Parent error messages */
    const ERROR_NOT_FOUND = 'ERROR_NOT_FOUND';
    const ERROR_INCORRECT_GENDER = 'ERROR_INCORRECT_GENDER';
    const ERROR_PARENT_IDENTICAL_TO_CHILD = 'ERROR_PARENT_IDENTICAL_TO_CHILD';
    const ERROR_PARENT_YOUNGER_THAN_CHILD = 'ERROR_PARENT_YOUNGER_THAN_CHILD';

    private $parentErrors = [
        Ram::class => [
            self::ERROR_NOT_FOUND => 'NO FATHER FOUND FOR GIVEN ULN',
            self::ERROR_INCORRECT_GENDER => 'ANIMAL FOUND FOR FATHER BUT IS NOT A RAM',
            self::ERROR_PARENT_IDENTICAL_TO_CHILD => 'FATHER CANNOT BE IDENTICAL TO THE CHILD',
            self::ERROR_PARENT_YOUNGER_THAN_CHILD => 'THE BIRTH DATE OF THE FATHER IS AFTER THAT OF THE CHILD',
        ],
        Ewe::class => [
            self::ERROR_NOT_FOUND => 'NO MOTHER FOUND FOR GIVEN ULN',
            self::ERROR_INCORRECT_GENDER => 'ANIMAL FOUND FOR MOTHER BUT IS NOT A RAM',
            self::ERROR_PARENT_IDENTICAL_TO_CHILD => 'MOTHER CANNOT BE IDENTICAL TO THE CHILD',
            self::ERROR_PARENT_YOUNGER_THAN_CHILD => 'THE BIRTH DATE OF THE MOTHER IS AFTER THAT OF THE CHILD',
        ]
    ];

    /**
     * @param InbreedingCoefficientUpdaterService $inbreedingCoefficientUpdaterService
     */
    public function setInbreedingCoefficientUpdaterService(InbreedingCoefficientUpdaterService $inbreedingCoefficientUpdaterService)
    {
        $this->inbreedingCoefficientUpdaterService = $inbreedingCoefficientUpdaterService;
    }

    /**
     * @param Request $request
     * @return \AppBundle\Component\HttpFoundation\JsonResponse
     */
    public function updateAnimalDetails(Request $request)
    {
        $content = RequestUtil::getContentAsArrayCollection($request);
        return $this->updateAnimalDetailsByArrayCollection($content);
    }


    /**
     * @param ArrayCollection $content
     * @return JsonResponse|true
     */
    public function updateAnimalDetailsByArrayCollection(ArrayCollection $content)
    {
        $animalsArray = $content->get(JsonInputConstant::ANIMALS);

        /** @var Animal[] $animalsWithNewValues */
        $animalsWithNewValues = $this->getBaseSerializer()->denormalizeToObject($animalsArray, Animal::class, true);

        $ids = [];
        foreach ($animalsWithNewValues as $animal) {
            if ($animal->getId()) {
                $ids[] = $animal->getId();
            } elseif ($animal->getId() !== null) {
                return ResultUtil::errorResult("Animal 'id' is missing", Response::HTTP_PRECONDITION_REQUIRED);
            }
        }

        $animalsWithNewValues = $this->cleanUpInputValues($animalsWithNewValues);

        $inputOnlyValidationResult = $this->validateForDuplicateValuesWithinRequestBody($animalsWithNewValues);
        if ($inputOnlyValidationResult instanceof JsonResponse) {
            return $inputOnlyValidationResult;
        }

        $inputValidationValidationResult = $this->validateFormat($animalsWithNewValues);
        if ($inputValidationValidationResult instanceof JsonResponse) {
            return $inputValidationValidationResult;
        }

        try {
            $currentAnimalsResult = $this->getManager()->getRepository(Animal::class)->findByIds($ids, true);

            $parentValidationResult = $this->validateAndRetrieveNewParents($animalsWithNewValues, $currentAnimalsResult);
            if ($parentValidationResult instanceof JsonResponse) {
                return $parentValidationResult;
            }

        } catch (\Exception $exception) {
            $this->logExceptionAsError($exception);
            return ResultUtil::badRequest();
        }

        $inputWithDatabaseValuesValidationResult = $this->validateInputWithDatabaseValues($animalsWithNewValues, $currentAnimalsResult);
        if ($inputWithDatabaseValuesValidationResult instanceof JsonResponse) {
            return $inputWithDatabaseValuesValidationResult;
        }


        $this->getManager()->getConnection()->beginTransaction();
        $this->getManager()->getConnection()->setAutoCommit(false);

        try {

            $this->updateInbreedingCoefficient = false;
            $this->animalsIdsForWhichInbreedingCoefficientShouldBeUpdated = [];

            $updateAnimalResults = $this->updateValues($animalsWithNewValues, $currentAnimalsResult);
            if ($updateAnimalResults instanceof JsonResponse) {
                $this->getManager()->getConnection()->rollBack();
                return $updateAnimalResults;
            }

            if ($this->anyValueWasUpdated) {
                $this->getManager()->flush();

                /* Update Inbreeding Coefficient */
                if ($this->updateInbreedingCoefficient) {

                    foreach ($this->animalsIdsForWhichInbreedingCoefficientShouldBeUpdated as $animalId) {
                        /** @var Animal $toBeUpdatedAnimal */
                        $toBeUpdatedAnimal = $updateAnimalResults[JsonInputConstant::UPDATED][$animalId];
                        if ($toBeUpdatedAnimal->hasBothParentIds()) {
                            $parentIdsPair = new ParentIdsPair(
                                $toBeUpdatedAnimal->getParentFatherId(),
                                $toBeUpdatedAnimal->getParentMotherId()
                            );

                            $this->inbreedingCoefficientUpdaterService->regenerateInbreedingCoefficients([$parentIdsPair]);
                            $this->inbreedingCoefficientUpdaterService->matchAnimalsAndLitters([$animalId], []);
                        }
                    }
                }

                $this->getManager()->getConnection()->commit();
            } else {
                $this->getManager()->getConnection()->rollBack();
            }

        } catch (\Exception $exception) {

            try {
                $this->getManager()->getConnection()->rollBack();
            } catch (\Exception $rollBackException) {
                $this->logExceptionAsError($rollBackException);
            }

            $this->logExceptionAsError($exception);

            $errorMessage =
                $this->translateUcFirstLower('SOMETHING WENT WRONG WHEN PERSISTING THE CHANGES') . '. ' .
                $this->translateUcFirstLower('THE CHANGES WERE NOT SAVED') . '. ';
            return ResultUtil::errorResult($errorMessage, Response::HTTP_INTERNAL_SERVER_ERROR);

        }

        $this->getManager()->getConnection()->setAutoCommit(true);

        $successfulUpdateOfSecondaryValues = true;
        $updateIndirectValuesResult = $this->updateIndirectSecondaryValues();
        if ($updateIndirectValuesResult instanceof JsonResponse) {
            $successfulUpdateOfSecondaryValues = false;
        }

        $successFullyUpdatedResultTableValues = true;
        if ($this->updateResultTableValues() instanceof JsonResponse) {
            $successFullyUpdatedResultTableValues = false;
        }

        $serializedUpdatedAnimalsOutput = AnimalService::getSerializedAnimalsInBatchEditFormat($this, $updateAnimalResults[JsonInputConstant::UPDATED])[JsonInputConstant::ANIMALS];
        $serializedNonUpdatedAnimalsOutput = AnimalService::getSerializedAnimalsInBatchEditFormat($this, $updateAnimalResults[JsonInputConstant::NOT_UPDATED])[JsonInputConstant::ANIMALS];

        return ResultUtil::successResult([
            JsonInputConstant::ANIMALS => [
                JsonInputConstant::UPDATED => $serializedUpdatedAnimalsOutput,
                JsonInputConstant::NOT_UPDATED => $serializedNonUpdatedAnimalsOutput,
            ],
            JsonInputConstant::SUCCESSFUL_UPDATE_SECONDARY_VALUES => $successfulUpdateOfSecondaryValues,
            JsonInputConstant::SUCCESSFUL_UPDATE_RESULT_TABLE_VALUES => $successFullyUpdatedResultTableValues,
        ]);
    }


    /**
     * @return JsonResponse|bool
     */
    private function updateResultTableValues()
    {
        if (!empty($this->animalIdsWithUpdatedNLingValues)) {
            try {
                NLingCacher::updateNLingValues($this->getConnection(), $this->animalIdsWithUpdatedNLingValues);
            } catch (\Exception $exception) {
                $this->logExceptionAsError($exception);
                return ResultUtil::errorResult('SOMETHING WENT WRONG WHILE UPDATING THE RESULT TABLE VALUES, BUT THE PRIMARY VALUES WERE CORRECTLY UPDATED', Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
        return true;
    }


    /**
     * @return JsonResponse|true
     */
    private function updateIndirectSecondaryValues()
    {
        try {
            $isBreedCodeUpdated = count($this->parentIdsForWhichTheirAndTheirChildrenGeneticDiversityValuesShouldBeUpdated) > 0;
            if($isBreedCodeUpdated) {
                //Update heterosis and recombination values of parent and children if breedCode of parent was changed
                GeneDiversityUpdater::updateByParentIds($this->getConnection(), $this->parentIdsForWhichTheirAndTheirChildrenGeneticDiversityValuesShouldBeUpdated);
            }
        } catch (\Exception $exception) {
            $this->logExceptionAsError($exception);
            return ResultUtil::errorResult('SOMETHING WENT WRONG WHILE UPDATING THE BREED CODES, BUT THE PRIMARY VALUES WERE CORRECTLY UPDATED', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return true;
    }


    /**
     * @param Animal[] $animalsWithNewValues
     * @return Animal[]
     */
    private function cleanUpInputValues(array $animalsWithNewValues = [])
    {
        foreach ($animalsWithNewValues as $key => $animalsWithNewValue) {
            $animalsWithNewValues[$key] = self::cleanUpAnimalInputValues($animalsWithNewValue);
        }

        return $animalsWithNewValues;
    }


    /**
     * @param Animal $animalsWithNewValue
     * @return Animal
     */
    public static function cleanUpAnimalInputValues(Animal $animalsWithNewValue): Animal
    {
        if ($animalsWithNewValue->getBirthProgress() === '') {
            $animalsWithNewValue->setBirthProgress(null);
        }

        if ($animalsWithNewValue->getBreedType() === '') {
            $animalsWithNewValue->setBreedType(null);
        }

        if ($animalsWithNewValue->getBreedCode() === '') {
            $animalsWithNewValue->setBreedCode(null);
        }

        if ($animalsWithNewValue->getBlindnessFactor() === '') {
            $animalsWithNewValue->setBlindnessFactor(null);
        }

        if ($animalsWithNewValue->getCollarColor() === '') {
            $animalsWithNewValue->setCollarColor(null);
        }

        if ($animalsWithNewValue->getCollarNumber() === '') {
            $animalsWithNewValue->setCollarNumber(null);
        }

        if ($animalsWithNewValue->getParentMotherId() === null) {
            $animalsWithNewValue->setParentMother(null);
        }

        if ($animalsWithNewValue->getParentFatherId() === null) {
            $animalsWithNewValue->setParentFather(null);
        }

        if ($animalsWithNewValue->getSurrogate() && $animalsWithNewValue->getSurrogate()->getId() === null) {
            $animalsWithNewValue->setSurrogate(null);
        }

        if ($animalsWithNewValue->getLocation() && $animalsWithNewValue->getLocation()->getLocationId() === null) {
            $animalsWithNewValue->setLocation(null);
        }

        if ($animalsWithNewValue->getLocationOfBirth() && $animalsWithNewValue->getLocationOfBirth()->getLocationId() === null) {
            $animalsWithNewValue->setLocationOfBirth(null);
        }

        if ($animalsWithNewValue->getPedigreeRegister() && $animalsWithNewValue->getPedigreeRegister()->getId() === null) {
            $animalsWithNewValue->setPedigreeRegister(null);
        }

        if ($animalsWithNewValue->getLitter()) {
            if ($animalsWithNewValue->getLitter()->getId() === null) {
                $animalsWithNewValue->setLitter(null);
            } else {
                if ($animalsWithNewValue->getLitter()->getAnimalMother()
                    && $animalsWithNewValue->getLitter()->getAnimalMother()->getId() === null) {
                    $animalsWithNewValue->getLitter()->setAnimalMother(null);
                }

                if ($animalsWithNewValue->getLitter()->getAnimalFather()
                    && $animalsWithNewValue->getLitter()->getAnimalFather()->getId() === null) {
                    $animalsWithNewValue->getLitter()->setAnimalFather(null);
                }
            }
        }

        if ($animalsWithNewValue->getPredicate() === '') {
            $animalsWithNewValue->setPredicate(null);
        }

        if ($animalsWithNewValue->getPredicateScore() === '' || $animalsWithNewValue->getPredicateScore() === 0) {
            $animalsWithNewValue->setPredicateScore(null);
        }

        if ($animalsWithNewValue->getNLing() === '') {
            $animalsWithNewValue->setNLing(null);
        }

        return $animalsWithNewValue;
    }


    /**
     * @param Animal[] $animalsWithNewValues
     * @param Animal[] $retrievedAnimals
     * @return JsonResponse
     * @throws \Exception
     */
    private function validateAndRetrieveNewParents(array $animalsWithNewValues, array $retrievedAnimals)
    {
        $this->newParentsById = [];

        $this->parentValidationErrorSets = [
            self::ERROR_PARENT_IDENTICAL_TO_CHILD => [],
            self::ERROR_NOT_FOUND => [],
            self::ERROR_INCORRECT_GENDER => [],
            self::ERROR_PARENT_YOUNGER_THAN_CHILD => [],
        ];

        foreach ($animalsWithNewValues as $animalsWithNewValue) {
            $animalId = $animalsWithNewValue->getId();
            /** @var Animal $retrievedAnimal */
            $retrievedAnimal = ArrayUtil::get($animalId, $retrievedAnimals);

            if ($retrievedAnimal === null) {
                return ResultUtil::errorResult('VALIDATE ANIMAL IDS AT BEGINNING OF CALL', Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            foreach ([Ram::class, Ewe::class] as $parentType)
            {
                $currentParent = $retrievedAnimal->getParent($parentType);
                $newParent = $animalsWithNewValue->getParent($parentType);

                $this->validateSingleParent($retrievedAnimal, $newParent, $currentParent, $parentType);
            }

            $this->validateSingleParent($retrievedAnimal, $animalsWithNewValue->getSurrogate(), $retrievedAnimal->getSurrogate(), Constant::SURROGATE_NAMESPACE);
        }

        $totalErrorMessage = '';
        $prefix = '';
        foreach ($this->parentValidationErrorSets as $typeKey => $errorSet) {
            if (count($errorSet) > 0) {

                foreach ($errorSet as $newParentId => $parentType) {

                    if ($parentType === Ram::class) {
                        $parentLabelId = 'father_id';
                    } elseif ($parentType === Ewe::class) {
                        $parentLabelId = 'mother_id';
                    } elseif ($parentType === Constant::SURROGATE_NAMESPACE) {
                        $parentLabelId = 'surrogate_id';
                    } else {
                        throw new \Exception('$parentType must be Ram::class or Ewe::class or surrogate');
                    }

                    switch ($typeKey) {
                        case self::ERROR_NOT_FOUND: $data = '['.$parentLabelId.': '.$newParentId.']'; break;
                        case self::ERROR_PARENT_IDENTICAL_TO_CHILD: $data = ''; break;
                        case self::ERROR_INCORRECT_GENDER: $data = '['.$parentLabelId.': '.$newParentId.']'; break;
                        case self::ERROR_PARENT_YOUNGER_THAN_CHILD: $data = ''; break;
                        default: $data = ''; break;
                    }

                    $errorMessage = $this->getParentErrorResponse($typeKey, $parentType, $data);

                    $totalErrorMessage .= $prefix . $errorMessage;
                    $prefix = ' ';
                }

            }
        }

        return $this->validationResult($totalErrorMessage);

    }


    /**
     * @param Animal $retrievedAnimal
     * @param Animal $serializedNewParent
     * @param Animal $retrievedCurrentParent
     * @param string $parentType
     */
    private function validateSingleParent($retrievedAnimal, $serializedNewParent, $retrievedCurrentParent, $parentType)
    {
        if ($this->hasParentChanged($retrievedCurrentParent, $serializedNewParent)) {
            if ($serializedNewParent) {

                $newParentId = $serializedNewParent->getId();
                if ($retrievedAnimal->getId() === $newParentId) {
                    $this->parentValidationErrorSets[self::ERROR_PARENT_IDENTICAL_TO_CHILD][$newParentId] = $parentType;
                    return;
                }

                if (key_exists($newParentId, $this->parentValidationErrorSets[self::ERROR_NOT_FOUND])) {
                    //Error is already processed
                    return;
                }

                $retrievedParent = ArrayUtil::get($newParentId, $this->newParentsById);
                if ($retrievedParent === null) {
                    $retrievedParent = $this->getManager()->getRepository(Animal::class)->find($newParentId);
                }

                if ($retrievedParent === null) {
                    $parentIdsWithoutFoundAnimals[$newParentId] = $parentType;
                    $this->parentValidationErrorSets[self::ERROR_NOT_FOUND][$newParentId] = $parentType;
                    return;
                }

                if (
                    ($parentType === Ram::class && !($retrievedParent instanceof Ram)) ||
                    ($parentType === Ewe::class && !($retrievedParent instanceof Ewe))
                ) {
                    $this->parentValidationErrorSets[self::ERROR_INCORRECT_GENDER][$newParentId] = $parentType;
                    return;
                }

                if ($retrievedAnimal->getDateOfBirth() && $retrievedParent->getDateOfBirth()) {
                    if ($retrievedAnimal->getDateOfBirth() < $retrievedParent->getDateOfBirth()) {
                        $this->parentValidationErrorSets[self::ERROR_PARENT_YOUNGER_THAN_CHILD][$newParentId] = $parentType;
                        return;
                    }
                }

                // Parent is valid!
                $this->newParentsById[$newParentId] = $retrievedParent;
            }
        }
    }


    /**
     * @param Animal[] $animalsWithNewValues
     * @param Animal[] $retrievedAnimals
     * @return array|JsonResponse
     * @throws DBALException
     */
    private function updateValues(array $animalsWithNewValues, array $retrievedAnimals)
    {
        $this->initializeUpdateValues();

        $updatedAnimals = [];
        $nonUpdatedAnimals = [];
        $this->animalIdsWithUpdatedNLingValues = [];
        $this->changedSurrogateMothers = [];

        foreach ($animalsWithNewValues as $animalsWithNewValue) {
            $animalId = $animalsWithNewValue->getId();
            $retrievedAnimal = ArrayUtil::get($animalId, $retrievedAnimals);

            if ($retrievedAnimal === null) {
                return ResultUtil::errorResult('VALIDATE ANIMAL IDS AT BEGINNING OF CALL', Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            try {
                $updatedAnimalResult = $this->updateValueSingleAnimal($animalsWithNewValue, $retrievedAnimal);

                if ($updatedAnimalResult instanceof JsonResponse) {
                    return $updatedAnimalResult;
                }

                /** @var Animal $retrievedAndUpdatedAnimal */
                $retrievedAndUpdatedAnimal = $updatedAnimalResult[JsonInputConstant::ANIMAL];

                if ($updatedAnimalResult[JsonInputConstant::UPDATED]) {
                    $updatedAnimals[$animalId] = $retrievedAndUpdatedAnimal;
                } else {
                    $nonUpdatedAnimals[$animalId] = $retrievedAndUpdatedAnimal;
                }

            } catch (\Exception $exception) {
                $this->logExceptionAsError($exception);
                return ResultUtil::internalServerError();
            }

        }

        foreach ($this->locationsWithRelocations as $locationsWithRelocation) {
            $this->clearLivestockCacheForLocation($locationsWithRelocation);
        }

        if (!empty($this->changedSurrogateMothers)) {
            LitterUtil::updateSuckleCountForLittersOrMotherIds($this->getConnection(), $this->changedSurrogateMothers);
        }

        return [
            JsonInputConstant::UPDATED => $updatedAnimals,
            JsonInputConstant::NOT_UPDATED => $nonUpdatedAnimals,
        ];
    }


    /**
     * @return string
     */
    private function getEmptyLabel()
    {
        return $this->translator->trans('EMPTY');
    }


    /**
     * @param string $ubn
     * @param string $locationId
     * @return string
     */
    private function getNoLocationFoundForUbnErrorMessage($ubn, $locationId)
    {
        return $this->translateUcFirstLower('NO LOCATION FOUND FOR UBN').': '.$ubn.' (locationId: '.$locationId.')';
    }


    /**
     * @param int $pedigreeRegisterId
     * @return string
     */
    private function getNoPedigreeRegisterFoundErrorMessage($pedigreeRegisterId)
    {
        return $this->translateUcFirstLower('NO PEDIGREE REGISTER FOUND WITH ID').': '.$pedigreeRegisterId;
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

        $ending = $data === '' ? '.': ': '.$data;

        return ResultUtil::errorResult(
            $this->translateUcFirstLower($this->parentErrors[$parentClazz][$key]).$ending,
            Response::HTTP_PRECONDITION_REQUIRED
        );
    }


    /**
     * @param Animal $animalsWithNewValue
     * @param Animal $retrievedAnimal
     * @return array|JsonResponse
     * @throws \Exception
     */
    private function updateValueSingleAnimal(Animal $animalsWithNewValue, Animal $retrievedAnimal)
    {
        $this->clearCurrentActionLogMessage();
        $this->extractCurrentAnimalIdData($retrievedAnimal);

        /* Update Parents */

        foreach ([Ram::class, Ewe::class] as $parentClazz)
        {
            $currentParent = $retrievedAnimal->getParent($parentClazz);
            $newParent = $animalsWithNewValue->getParent($parentClazz);

            if ($this->hasParentChanged($currentParent, $newParent)) {
                $this->updateInbreedingCoefficient = true;
                $this->animalsIdsForWhichInbreedingCoefficientShouldBeUpdated[] = $retrievedAnimal->getId();
                $ulnStringCurrentParent = $currentParent ? $currentParent->getUln() : $this->getEmptyLabel();
                if ($newParent && $newParent->getId()) {
                    /** @var Ram|Ewe $retrievedNewParent */
                    $retrievedNewParent = ArrayUtil::get($newParent->getId(), $this->newParentsById);
                    if ($retrievedNewParent === null) {
                        return ResultUtil::errorResult('RETRIEVE PARENTS DURING PARENT VALIDATION', Response::HTTP_INTERNAL_SERVER_ERROR);
                    }

                    $retrievedAnimal->setParent($retrievedNewParent);
                    $ulnStringNewParent = $retrievedNewParent->getUln();

                } else {
                    $ulnStringNewParent = $this->getEmptyLabel();
                    $retrievedAnimal->removeParent($parentClazz);
                }

                switch ($parentClazz) {
                    case Ram::class: $dutchParentType = $this->translateUcFirstLower('FATHER'); break;
                    case Ewe::class: $dutchParentType = $this->translateUcFirstLower('MOTHER'); break;
                    default:
                        return ResultUtil::errorResult('INVALID PARENT TYPE', Response::HTTP_INTERNAL_SERVER_ERROR);
                }

                $this->updateCurrentActionLogMessage($dutchParentType, $ulnStringCurrentParent, $ulnStringNewParent);
            }

        }


        /* Update Surrogate */

        $currentSurrogate = $retrievedAnimal->getSurrogate();
        $newSurrogate = $animalsWithNewValue->getSurrogate();

        if ($this->hasParentChanged($currentSurrogate, $newSurrogate)) {
            $this->changedSurrogateMothers[] = $currentSurrogate->getParentMotherId();
            $this->changedSurrogateMothers[] = $newSurrogate->getParentMotherId();
            $ulnStringCurrentSurrogate = $currentSurrogate ? $currentSurrogate->getUln() : $this->getEmptyLabel();
            if ($newSurrogate && $newSurrogate->getId()) {
                /** @var Ram|Ewe $retrievedNewParent */
                $retrievedNewSurrogate = ArrayUtil::get($newSurrogate->getId(), $this->newParentsById);
                if ($retrievedNewSurrogate === null) {
                    return ResultUtil::errorResult('RETRIEVE PARENTS DURING PARENT VALIDATION', Response::HTTP_INTERNAL_SERVER_ERROR);
                }

                $retrievedAnimal->setSurrogate($retrievedNewSurrogate);
                $ulnStringNewSurrogate = $retrievedNewSurrogate->getUln();

            } else {
                $ulnStringNewSurrogate = $this->getEmptyLabel();
                $retrievedAnimal->setSurrogate(null);
            }

            $surrogateLabel = $this->translateUcFirstLower('SURROGATE MOTHER');

            $this->updateCurrentActionLogMessage($surrogateLabel, $ulnStringCurrentSurrogate, $ulnStringNewSurrogate);
        }


        $newPedigreeCountryCode = StringUtil::convertEmptyStringToNull($animalsWithNewValue->getPedigreeCountryCode());
        $newPedigreeNumber = StringUtil::convertEmptyStringToNull($animalsWithNewValue->getPedigreeNumber());
        if($retrievedAnimal->getPedigreeCountryCode() !== $newPedigreeCountryCode ||
            $retrievedAnimal->getPedigreeNumber() !== $newPedigreeNumber
        ) {
            $oldStn = $retrievedAnimal->getPedigreeString();
            $retrievedAnimal->setPedigreeCountryCode($newPedigreeCountryCode);
            $retrievedAnimal->setPedigreeNumber($newPedigreeNumber);
            $this->updateCurrentActionLogMessage('stn', $oldStn, $animalsWithNewValue->getPedigreeString());
        }

        if($animalsWithNewValue->getUlnCountryCode() !== $retrievedAnimal->getUlnCountryCode() ||
            $animalsWithNewValue->getUlnNumber() !== $retrievedAnimal->getUlnNumber()
        ) {
            $oldUln = $retrievedAnimal->getUln();
            $retrievedAnimal->setUlnCountryCode($animalsWithNewValue->getUlnCountryCode());
            $retrievedAnimal->setUlnNumber($animalsWithNewValue->getUlnNumber());
            $retrievedAnimal->setAnimalOrderNumber(StringUtil::getLast5CharactersFromString($animalsWithNewValue->getUlnNumber()));
            $this->updateCurrentActionLogMessage('uln', $oldUln, $animalsWithNewValue->getUln());
        }

        $newNickName = StringUtil::convertEmptyStringToNull($animalsWithNewValue->getNickname());
        if($retrievedAnimal->getNickname() !== $newNickName) {
            $oldNickName = $retrievedAnimal->getNickname();
            $retrievedAnimal->setNickname($newNickName);
            $this->updateCurrentActionLogMessage('nickname', $oldNickName, $newNickName);
        }

        $newName = StringUtil::convertEmptyStringToNull($animalsWithNewValue->getName());
        if($retrievedAnimal->getName() !== $newName) {
            $oldName = $retrievedAnimal->getName();
            $retrievedAnimal->setName($newName);
            $this->updateCurrentActionLogMessage('aiind', $oldName, $newName);
        }

        $newNLing = StringUtil::convertEmptyStringToNull($animalsWithNewValue->getNLing());
        if($retrievedAnimal->getNLing() !== $newNLing) {
            $oldNLing = $retrievedAnimal->getNLing();
            $retrievedAnimal->setNLing($newNLing);
            $this->updateCurrentActionLogMessage('N-Ling backup value', $oldNLing, $newNLing);
            $this->animalIdsWithUpdatedNLingValues[] = $retrievedAnimal->getId();
        }

        $newBlindnessFactor = StringUtil::convertEmptyStringToNull($animalsWithNewValue->getBlindnessFactor());
        if($retrievedAnimal->getBlindnessFactor() !== $newBlindnessFactor) {
            $oldBlindnessFactor = $retrievedAnimal->getBlindnessFactor();
            $retrievedAnimal->setBlindnessFactor($newBlindnessFactor);
            $this->updateCurrentActionLogMessage('blindfactor', $oldBlindnessFactor, $newBlindnessFactor);
        }

        $newMyoMax = StringUtil::convertEmptyStringToNull($animalsWithNewValue->getMyoMax());
        $oldMyoMax = $retrievedAnimal->getMyoMax();
        if($oldMyoMax !== $newMyoMax) {
            $retrievedAnimal->setMyoMax($newMyoMax);
            $this->updateCurrentActionLogMessage('MyoMax', $oldMyoMax, $newMyoMax);
        }

        $newPredicate = StringUtil::convertEmptyStringToNull($animalsWithNewValue->getPredicate());
        $oldPredicate = $retrievedAnimal->getPredicate();
        if($oldPredicate !== $newPredicate) {
            $retrievedAnimal->setPredicate($newPredicate);
            $this->updateCurrentActionLogMessage('Predikaat', $oldPredicate, $newPredicate);
        }

        $newPredicateScore = StringUtil::convertEmptyStringToNull($animalsWithNewValue->getPredicateScore());
        $oldPredicateScore = $retrievedAnimal->getPredicateScore();
        if($oldPredicateScore !== $newPredicateScore) {
            $retrievedAnimal->setPredicateScore($newPredicateScore);
            $this->updateCurrentActionLogMessage('PredikaatScore', $oldPredicateScore, $newPredicateScore);
        }

        $newCollarColar = StringUtil::convertEmptyStringToNull($animalsWithNewValue->getCollarColor());
        $newCollarNumber = StringUtil::convertEmptyStringToNull($animalsWithNewValue->getCollarNumber());
        if($retrievedAnimal->getCollarColor() !== $newCollarColar ||
            $retrievedAnimal->getCollarNumber() !== $newCollarNumber
        ) {
            $oldCollar = $retrievedAnimal->getCollarColorAndNumber();
            $retrievedAnimal->setCollarColor($newCollarColar);
            $retrievedAnimal->setCollarNumber($newCollarNumber);
            $this->updateCurrentActionLogMessage('halsband', $oldCollar, $animalsWithNewValue->getCollarColorAndNumber());
        }

        $breedCodeWasUpdated = false;
        $newBreedCode = StringUtil::convertEmptyStringToNull($animalsWithNewValue->getBreedCode());
        if($retrievedAnimal->getBreedCode() !== $newBreedCode) {
            $oldBreedCode = $retrievedAnimal->getBreedCode();
            $retrievedAnimal->setBreedCode($newBreedCode);
            $this->updateCurrentActionLogMessage('rascode', $oldBreedCode, $newBreedCode);
            $breedCodeWasUpdated = true;
            $this->parentIdsForWhichTheirAndTheirChildrenGeneticDiversityValuesShouldBeUpdated[] = $retrievedAnimal->getId();
        }

        $newScrapieGenotype = StringUtil::convertEmptyStringToNull($animalsWithNewValue->getScrapieGenotype());
        if($retrievedAnimal->getScrapieGenotype() !== $newScrapieGenotype) {
            $oldScrapieGenotype = $retrievedAnimal->getScrapieGenotype();
            $retrievedAnimal->setScrapieGenotype($newScrapieGenotype);
            $this->updateCurrentActionLogMessage('scrapieGenotype', $oldScrapieGenotype, $newScrapieGenotype);
        }


        if(is_string($animalsWithNewValue->getDateOfBirth())) {
            $updatedDateOfBirth = TimeUtil::getDayOfDateTime(new \DateTime($animalsWithNewValue->getDateOfBirth()));
            $animalsWithNewValue->setDateOfBirth($updatedDateOfBirth);
        } elseif ($animalsWithNewValue->getDateOfBirth() == null) {
            $updatedDateOfBirth = null;
        } else {
            $updatedDateOfBirth = $animalsWithNewValue->getDateOfBirth();
        }

        if($updatedDateOfBirth !== $retrievedAnimal->getDateOfBirth()) {
            $oldDateOfBirthString = $retrievedAnimal->getDateOfBirthString();
            $retrievedAnimal->setDateOfBirth($updatedDateOfBirth);
            $this->updateCurrentActionLogMessage('geboorteDatum', $oldDateOfBirthString, $retrievedAnimal->getDateOfBirthString());
        }


        if(is_string($animalsWithNewValue->getDateOfDeath())) {
            $updatedDateOfDeath = TimeUtil::getDayOfDateTime(new \DateTime($animalsWithNewValue->getDateOfDeath()));
            $animalsWithNewValue->setDateOfDeath($updatedDateOfDeath);
        } elseif ($animalsWithNewValue->getDateOfDeath() == null) {
            $updatedDateOfDeath = null;
        } else {
            $updatedDateOfDeath = $animalsWithNewValue->getDateOfDeath();
        }

        if($updatedDateOfDeath !== $retrievedAnimal->getDateOfDeath()) {
            $oldDateOfDeathString = $retrievedAnimal->getDateOfDeathString();
            $retrievedAnimal->setDateOfDeath($updatedDateOfDeath);
            $this->updateCurrentActionLogMessage('sterfteDatum', $oldDateOfDeathString, $retrievedAnimal->getDateOfDeathString());
        }


        if($animalsWithNewValue->getIsAlive() !== $retrievedAnimal->getIsAlive()) {
            $oldIsAliveString = StringUtil::getBooleanAsString($retrievedAnimal->getIsAlive());
            $retrievedAnimal->setIsAlive($animalsWithNewValue->getIsAlive());
            $this->updateCurrentActionLogMessage('isLevendStatus', $oldIsAliveString, StringUtil::getBooleanAsString($animalsWithNewValue->getIsAlive()));
        }


        if($animalsWithNewValue->getLambar() !== $retrievedAnimal->getLambar()) {
            $oldLambarString = StringUtil::getBooleanAsString($retrievedAnimal->getLambar());
            $retrievedAnimal->setLambar($animalsWithNewValue->getLambar());
            $this->updateCurrentActionLogMessage('lambar', $oldLambarString, StringUtil::getBooleanAsString($animalsWithNewValue->getLambar()));
        }


        $newNote = StringUtil::convertEmptyStringToNull($animalsWithNewValue->getNote());
        if($retrievedAnimal->getNote() !== $newNote) {
            $oldNote = $retrievedAnimal->getNote();
            $retrievedAnimal->setNote($newNote);
            $this->updateCurrentActionLogMessage('notitie', $oldNote, $newNote);
        }


        $newBirthProgress = StringUtil::convertEmptyStringToNull($animalsWithNewValue->getBirthProgress());
        $oldBirthProgress = $retrievedAnimal->getBirthProgress();
        if($oldBirthProgress !== $newBirthProgress) {
            $retrievedAnimal->setBirthProgress($newBirthProgress);
            $this->updateCurrentActionLogMessage('geboorteverloop', $oldBirthProgress, $newBirthProgress);
        }


        $updatedBreedType = $animalsWithNewValue->getBreedType() == '' || $animalsWithNewValue->getBreedType() == null ? null
            : Translation::getEnglish($animalsWithNewValue->getBreedType());
        if($updatedBreedType !== $retrievedAnimal->getBreedType()) {
            $oldBreedType = $retrievedAnimal->getBreedType();
            $retrievedAnimal->setBreedType($updatedBreedType);
            $this->updateCurrentActionLogMessage('rasStatus', $oldBreedType, $updatedBreedType);
        }


        $newUbnOfBirthNumberOnly = StringUtil::convertEmptyStringToNull($animalsWithNewValue->getUbnOfBirth());
        if($retrievedAnimal->getUbnOfBirth() !== $newUbnOfBirthNumberOnly) {
            $oldUbnOfBirth = $retrievedAnimal->getUbnOfBirth();
            $retrievedAnimal->setUbnOfBirth($newUbnOfBirthNumberOnly);
            $this->updateCurrentActionLogMessage('fokkerUbn(alleen nummer)', $oldUbnOfBirth, $newUbnOfBirthNumberOnly);
        }



        $actionLogLabelLocation = 'ubn';
        $currentLocation = $retrievedAnimal->getLocation();
        $newLocation = $animalsWithNewValue->getLocation();
        $locationAction = self::locationUpdateActionByLocationIdCheck($currentLocation, $newLocation);
        switch ($locationAction) {

            case QueryType::DELETE:
                $this->updateCurrentActionLogMessage($actionLogLabelLocation, $currentLocation->getUbn(), $this->getEmptyLabel());
                $retrievedAnimal->setLocation(null);
                $this->anyAnimalWasRelocated = true;
                break;

            case QueryType::UPDATE:
                $ubnNewLocation = $newLocation->getUbn();
                $newLocationId = $newLocation->getLocationId();
                $newLocation = $this->getLocationByLocationId($newLocationId);

                if ($newLocation === null) {
                    return ResultUtil::errorResult($this->getNoLocationFoundForUbnErrorMessage($ubnNewLocation, $newLocationId), Response::HTTP_PRECONDITION_REQUIRED);
                }

                $this->addToLocationsWithRelocation($newLocation);
                if ($currentLocation !== null) {
                    $this->addToLocationsWithRelocation($currentLocation);
                }

                $ubnCurrentLocation = $currentLocation ? $currentLocation->getUbn() : $this->getEmptyLabel();

                if ($currentLocation && $currentLocation->getUbn() === $ubnNewLocation &&
                    $currentLocation->getLocationId() !== $newLocationId) {

                    $this->updateCurrentActionLogMessage($actionLogLabelLocation.'(locationId)',
                        $ubnCurrentLocation.'('.$currentLocation->getLocationId().')',
                        $ubnNewLocation.'('.$newLocationId.')');

                } else {
                    $this->updateCurrentActionLogMessage($actionLogLabelLocation, $ubnCurrentLocation, $ubnNewLocation);
                }

                $retrievedAnimal->setLocation($newLocation);
                $this->anyAnimalWasRelocated = true;
                break;

            default:
                break;
        }

        $this->removeTransferStateIfNotNullUpdate($retrievedAnimal);




        $actionLogLabelLocationOfBirth = 'fokkerUbn[LOCATIE]';
        $currentLocationOfBirth = $retrievedAnimal->getLocationOfBirth();
        $newLocationOfBirth = $animalsWithNewValue->getLocationOfBirth();
        $locationOfBirthAction = self::locationUpdateActionByLocationIdCheck($currentLocationOfBirth, $newLocationOfBirth);
        switch ($locationOfBirthAction) {

            case QueryType::DELETE:
                $this->updateCurrentActionLogMessage($actionLogLabelLocationOfBirth, $currentLocationOfBirth->getUbn(), $this->getEmptyLabel());
                $retrievedAnimal->setLocationOfBirth(null);
                break;

            case QueryType::UPDATE:
                $ubnNewLocationOfBirth = $newLocationOfBirth->getUbn();
                $newLocationOfBirthId = $newLocationOfBirth->getLocationId();
                $newLocationOfBirth = $this->getLocationByLocationId($newLocationOfBirthId);

                if ($newLocationOfBirth === null) {
                    return ResultUtil::errorResult($this->getNoLocationFoundForUbnErrorMessage($ubnNewLocationOfBirth, $newLocationOfBirthId), Response::HTTP_PRECONDITION_REQUIRED);
                }

                $ubnCurrentLocationOfBirth = $currentLocationOfBirth ? $currentLocationOfBirth->getUbn() : $this->getEmptyLabel();

                if ($currentLocationOfBirth && $currentLocationOfBirth->getUbn() === $ubnNewLocationOfBirth &&
                    $currentLocationOfBirth->getLocationId() !== $newLocationOfBirthId) {

                    $this->updateCurrentActionLogMessage($actionLogLabelLocationOfBirth.'(locationId)',
                        $ubnCurrentLocationOfBirth.'('.$currentLocationOfBirth->getLocationId().')',
                        $ubnNewLocationOfBirth.'('.$newLocationOfBirthId.')');

                } else {
                    $this->updateCurrentActionLogMessage($actionLogLabelLocationOfBirth, $ubnCurrentLocationOfBirth, $ubnNewLocationOfBirth);
                }

                $retrievedAnimal->setLocationOfBirth($newLocationOfBirth);
                break;

            default:
                break;
        }


        $actionLogLabelPedigreeRegister = 'stamboek';
        $currentPedigreeRegister = $retrievedAnimal->getPedigreeRegister();
        $currentPedigreeRegisterAbbreviation = $currentPedigreeRegister ? $currentPedigreeRegister->getAbbreviation() : $this->getEmptyLabel();
        $newPedigreeRegister = $animalsWithNewValue->getPedigreeRegister();
        $pedigreeRegisterAction = self::objectUpdateActionByIdCheck($currentPedigreeRegister, $newPedigreeRegister);
        switch ($pedigreeRegisterAction) {

            case QueryType::DELETE:
                $this->updateCurrentActionLogMessage($actionLogLabelPedigreeRegister, $currentPedigreeRegisterAbbreviation, $this->getEmptyLabel());
                $retrievedAnimal->setPedigreeRegister(null);
                break;

            case QueryType::UPDATE:
                $newPedigreeRegisterId = $newPedigreeRegister->getId();
                $newPedigreeRegister = $this->getPedigreeRegisterById($newPedigreeRegisterId);
                $newPedigreeRegisterAbbreviation = $newPedigreeRegister->getAbbreviation();

                if ($newPedigreeRegister === null) {
                    return ResultUtil::errorResult($this->getNoPedigreeRegisterFoundErrorMessage($newPedigreeRegisterId), Response::HTTP_PRECONDITION_REQUIRED);
                }

                $this->updateCurrentActionLogMessage($actionLogLabelPedigreeRegister, $currentPedigreeRegisterAbbreviation, $newPedigreeRegisterAbbreviation);

                $retrievedAnimal->setPedigreeRegister($newPedigreeRegister);
                break;

            default:
                break;
        }


        $isUpdated = false;
        if($this->anyCurrentAnimalValueWasUpdated) {
            $this->closeCurrentActionLogMessage();
            $this->getManager()->persist($retrievedAnimal);
            $isUpdated = true;
        }

        return [
            JsonInputConstant::ANIMAL => $retrievedAnimal,
            JsonInputConstant::UPDATED => $isUpdated,
            JsonInputConstant::BREED_CODE_UPDATED => $breedCodeWasUpdated,
        ];
    }


    private function removeTransferStateIfNotNullUpdate(Animal &$retrievedAnimal) {
        if ($retrievedAnimal->getTransferState() !== null) {
            $this->updateCurrentActionLogMessage('transferState',
                $retrievedAnimal->getTransferState(),
                null);
            $retrievedAnimal->setTransferState(null);
            $this->anyAnimalWasRelocated = true;
        }
    }


    /**
     * @param Location $location
     * @return bool
     */
    private static function isSerializedLocationNotNull($location)
    {
        if ($location) {
            return is_string($location->getLocationId());
        }
        return false;
    }


    /**
     * @param Location $currentLocation
     * @param Location $newLocation
     * @return null|string
     */
    private static function locationUpdateActionByLocationIdCheck($currentLocation, $newLocation)
    {
        if (self::isSerializedLocationNotNull($newLocation)) {
            if (self::isSerializedLocationNotNull($currentLocation)) {
                if ($newLocation->getLocationId() !== $currentLocation->getLocationId()) {
                    return QueryType::UPDATE;
                }

            } else {
                return QueryType::UPDATE;
            }

        } else {
            if (self::isSerializedLocationNotNull($currentLocation)) {
                return QueryType::DELETE;
            }
        }

        return null;
    }


    /**
     * @param PedigreeRegister $object
     * @return bool
     */
    private static function isSerializedObjectWithIdNotNull($object)
    {
        if ($object) {
            return is_int($object->getId()) || ctype_digit($object->getId());
        }
        return false;
    }


    /**
     * @param PedigreeRegister $currentObject
     * @param PedigreeRegister $newObject
     * @return null|string
     */
    private static function objectUpdateActionByIdCheck($currentObject, $newObject)
    {
        if (self::isSerializedObjectWithIdNotNull($newObject)) {
            if (self::isSerializedObjectWithIdNotNull($currentObject)) {
                if ($newObject->getId() !== $currentObject->getId()) {
                    return QueryType::UPDATE;
                }

            } else {
                return QueryType::UPDATE;
            }

        } else {
            if (self::isSerializedObjectWithIdNotNull($currentObject)) {
                return QueryType::DELETE;
            }
        }

        return null;
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
                $hasParentChanged = $currentParent->getId() !== $newParent->getId();
            } else {
                $hasParentChanged = true;
            }

        } else {
            $hasParentChanged = $currentParent !== null;
        }

        return $hasParentChanged;
    }


    private function initializeUpdateValues()
    {
        $this->parentIdsForWhichTheirAndTheirChildrenGeneticDiversityValuesShouldBeUpdated = [];
        $this->anyValueWasUpdated = false;
        $this->anyAnimalWasRelocated = false;
        $this->locationsWithRelocations = [];
        $this->clearCurrentActionLogMessage();
    }


    private function clearCurrentActionLogMessage()
    {
        $this->currentActionLogMessage = '';
        $this->anyCurrentAnimalValueWasUpdated = false;
    }

    private function addToLocationsWithRelocation(Location $location) {
        $this->locationsWithRelocations[$location->getId()] = $location;
    }

    /**
     * @param Animal $animal
     */
    private function extractCurrentAnimalIdData(Animal $animal)
    {
        $this->currentAnimalIdLogPrefix = self::getAnimalEditLogPrefix($animal);
    }


    private function closeCurrentActionLogMessage()
    {
        if ($this->anyCurrentAnimalValueWasUpdated) {
            ActionLogWriter::updateAnimalDetailsAdminEnvironment($this->getManager(), $this->getUser(), $this->currentAnimalIdLogPrefix .$this->currentActionLogMessage);
        }
        $this->clearCurrentActionLogMessage();
    }


    /**
     * @param Animal $animal
     * @return string
     */
    public static function getAnimalEditLogPrefix(Animal $animal): string {
        return 'animal[id: '.$animal->getId() . ', uln: ' . $animal->getUln().']: ';
    }


    /**
     * @param $type
     * @param $oldValue
     * @param $newValue
     */
    private function updateCurrentActionLogMessage($type, $oldValue, $newValue)
    {
        if ($oldValue !== $newValue) {
            $oldValue = $oldValue == '' || $oldValue == null ? $this->getEmptyLabel() : $oldValue;
            $newValue = $newValue == '' || $newValue == null ? $this->getEmptyLabel() : $newValue;

            $prefix = $this->currentActionLogMessage === '' ? '' : ', ';
            $this->currentActionLogMessage .= $prefix . $type . ': '.$oldValue.' => '.$newValue;
            $this->anyCurrentAnimalValueWasUpdated = true;
            $this->anyValueWasUpdated = true;
        }
    }


    /**
     * @param string $locationId
     * @return Location
     */
    private function getLocationByLocationId($locationId)
    {
        if ($this->retrievedLocationsByLocationId === null) {
            $this->retrievedLocationsByLocationId = [];
        }

        if (!key_exists($locationId, $this->retrievedLocationsByLocationId)) {
            $this->retrievedLocationsByLocationId[$locationId] = $this->getManager()->getRepository(Location::class)->findOneBy(['locationId' => $locationId]);
        }

        return $this->retrievedLocationsByLocationId[$locationId];
    }


    /**
     * @param string $pedigreeRegisterId
     * @return PedigreeRegister
     */
    private function getPedigreeRegisterById($pedigreeRegisterId)
    {
        if ($this->retrievedPedigreeRegistersById === null) {
            $this->retrievedPedigreeRegistersById = [];
        }

        if (!key_exists($pedigreeRegisterId, $this->retrievedPedigreeRegistersById)) {
            $this->retrievedPedigreeRegistersById[$pedigreeRegisterId] = $this->getManager()->getRepository(PedigreeRegister::class)->find($pedigreeRegisterId);
        }

        return $this->retrievedPedigreeRegistersById[$pedigreeRegisterId];
    }


    /**
     * @param Animal[] $animalsWithNewValues
     * @return JsonResponse|bool
     */
    private function validateForDuplicateValuesWithinRequestBody(array $animalsWithNewValues = [])
    {
        $foundValuesSets = [
            JsonInputConstant::ID => [],
            JsonInputConstant::ULN => [],
            JsonInputConstant::STN => [],
        ];

        $duplicateValuesSets = [
            JsonInputConstant::ID => [],
            JsonInputConstant::ULN => [],
            JsonInputConstant::STN => [],
        ];

        foreach ($animalsWithNewValues as $animalsWithNewValue) {
            $values = [
                JsonInputConstant::ID => $animalsWithNewValue->getId(),
                JsonInputConstant::ULN => $animalsWithNewValue->getUln(),
                JsonInputConstant::STN => $animalsWithNewValue->getPedigreeString(),
            ];

            foreach (array_keys($values) as $typeKey) {
                $value = $values[$typeKey];
                if ($value === null) {
                    continue;
                }

                if (key_exists($value, $foundValuesSets[$typeKey])) {
                    $duplicateValuesSets[$typeKey][$value] = $value;
                } else {
                    $foundValuesSets[$typeKey][$value] = $value;
                }
            }
        }

        $errorMessage = '';
        $prefix = '';
        foreach ($duplicateValuesSets as $typeKey => $duplicateValues) {
            if (count($duplicateValues) > 0) {

                switch ($typeKey) {
                    case JsonInputConstant::ULN: $errorMessageKey = 'THE FOLLOWING ULNS WERE INSERTED MULTIPLE TIMES'; break;
                    case JsonInputConstant::STN: $errorMessageKey = 'THE FOLLOWING PEDIGREE NUMBERS WERE INSERTED MULTIPLE TIMES'; break;
                    case JsonInputConstant::ID: $errorMessageKey = 'THE FOLLOWING IDS WERE INSERTED MULTIPLE TIMES'; break;
                    default: $errorMessageKey = null; break;
                }
                if ($errorMessageKey === null) {
                    continue;
                }

                $errorMessage .= $prefix . $this->translateUcFirstLower($errorMessageKey) . ': '.implode(', ', $duplicateValues).'.';
                $prefix = ' ';
            }
        }

        return $this->validationResult($errorMessage);
    }


    /**
     * @param Animal[] $animalsWithNewValues
     * @return JsonResponse|bool
     */
    private function validateFormat(array $animalsWithNewValues = [])
    {
        $incorrectFormatSets = [
            JsonInputConstant::UBN => [],
            JsonInputConstant::BREED_TYPE => [],
            JsonInputConstant::BLINDNESS_FACTOR => [],
            JsonInputConstant::BIRTH_PROGRESS => [],
            JsonInputConstant::N_LING => [],
            JsonInputConstant::N_LING_VALUE => [],
        ];

        foreach ($animalsWithNewValues as $animalsWithNewValue) {
            $animalId = $animalsWithNewValue->getId();
            if ($animalsWithNewValue->getUbn() !== null) {
                if (!Validator::hasValidUbnFormat($animalsWithNewValue->getUbn())) {
                    $incorrectFormatSets[JsonInputConstant::UBN][$animalId] = $animalsWithNewValue->getUbn();
                }
            }

            if (!Validator::hasValidBreedType($animalsWithNewValue->getBreedType(), true)) {
                $incorrectFormatSets[JsonInputConstant::BREED_TYPE][$animalId] = $animalsWithNewValue->getBreedType();
            }

            if (!Validator::hasValidBlindnessFactorType($animalsWithNewValue->getBlindnessFactor(), true)) {
                $incorrectFormatSets[JsonInputConstant::BLINDNESS_FACTOR][$animalId] = $animalsWithNewValue->getBlindnessFactor();
            }

            if (!Validator::hasValidBirthProgressType($this->getManager(), $animalsWithNewValue->getBirthProgress(), true)) {
                $incorrectFormatSets[JsonInputConstant::BIRTH_PROGRESS][$animalId] = $animalsWithNewValue->getBirthProgress();
            }

            if ($animalsWithNewValue->getNLing() !== null && !is_int($animalsWithNewValue->getNLing()) && !ctype_digit($animalsWithNewValue->getNLing())) {
                $incorrectFormatSets[JsonInputConstant::N_LING][$animalId] = $animalsWithNewValue->getNLing();
            } elseif ($animalsWithNewValue->getNLing() < Animal::MIN_N_LING_VALUE
                || Animal::MAX_N_LING_VALUE < $animalsWithNewValue->getNLing()) {
                $incorrectFormatSets[JsonInputConstant::N_LING_VALUE][$animalId] = $animalsWithNewValue->getNLing();
            }
        }

        $errorMessage = '';
        $prefix = '';
        foreach ($incorrectFormatSets as $typeKey => $incorrectFormatSet) {
            if (count($incorrectFormatSet) > 0) {
                switch ($typeKey) {
                    case JsonInputConstant::UBN: $errorMessageKey = 'THE FOLLOWING UBNS HAVE AN INCORRECT FORMAT'; break;
                    case JsonInputConstant::BREED_TYPE: $errorMessageKey = 'THE FOLLOWING BREED TYPES HAVE AN INCORRECT FORMAT'; break;
                    case JsonInputConstant::BLINDNESS_FACTOR: $errorMessageKey = 'THE FOLLOWING BLINDNESS FACTORS HAVE AN INCORRECT FORMAT'; break;
                    case JsonInputConstant::BIRTH_PROGRESS: $errorMessageKey = 'THE FOLLOWING BIRTH PROGESSES HAVE AN INCORRECT FORMAT'; break;
                    case JsonInputConstant::N_LING: $errorMessageKey = 'THE FOLLOWING N LINGS HAVE AN INCORRECT FORMAT'; break;
                    case JsonInputConstant::N_LING_VALUE: $errorMessageKey = 'THE FOLLOWING N LINGS SHOULD HAVE A VALUE BETWEEN '.Animal::MIN_N_LING_VALUE.' AND '.Animal::MAX_N_LING_VALUE; break;
                    default: $errorMessageKey = null; break;
                }
                if ($errorMessageKey === null) {
                    continue;
                }

                $errorMessage .= $prefix . $this->translateUcFirstLower($errorMessageKey) . ': '.implode(', ', $incorrectFormatSet).'.';
                $prefix = ' ';
            }
        }

        return $this->validationResult($errorMessage);
    }


    /**
     * @param Animal[] $animalsWithNewValues
     * @param Animal[] $currentAnimalsResult
     * @return JsonResponse|bool
     */
    private function validateInputWithDatabaseValues(array $animalsWithNewValues, array $currentAnimalsResult)
    {
        $newUlnsByAnimalId = [];
        $newStnsByAnimalId = [];

        $newUlnsWithInvalidFormatByAnimalId = [];
        $newStnsWithInvalidFormatByAnimalId = [];

        $idsNotFound = [];
        /** @var  $animalsWithNewValue */
        foreach ($animalsWithNewValues as $animalsWithNewValue)
        {
            $animalId = $animalsWithNewValue->getId();
            if (!key_exists($animalId, $currentAnimalsResult)) {
                $idsNotFound[$animalId] = $animalId;
            }

            $currentAnimal = $currentAnimalsResult[$animalId];

            $newUln = $animalsWithNewValue->getUln();
            if ($currentAnimal->getUln() !== $newUln) {
                if (Validator::verifyUlnFormat($newUln, false)) {
                    $newUlnsByAnimalId[$animalId] = $newUln;
                } else {
                    $newUlnsWithInvalidFormatByAnimalId[$animalId] = $newUln;
                }
            }

            $newStn = $animalsWithNewValue->getPedigreeString();
            if ($currentAnimal->getPedigreeString() !== $newStn) {
                if ($newStn !== '' && $newStn !== null) {
                    if (Validator::verifyPedigreeCountryCodeAndNumberFormat($newStn, false)) {
                        $newStnsByAnimalId[$animalId] = $newStn;
                    } else {
                        $newStnsWithInvalidFormatByAnimalId[$animalId] = $newStn;
                    }
                }
            }
        }


        $errorMessage = '';
        $prefix = '';
        if (count($idsNotFound) > 0) {
            $errorMessage .= $prefix . $this->translateUcFirstLower('THE FOLLOWING IDS WERE NOT FOUND IN THE DATABASE') . ': '.implode(', ', $idsNotFound).'.';
            $prefix = ' ';
        }

        if (count($newUlnsWithInvalidFormatByAnimalId) > 0) {
            $errorMessage .= $prefix . $this->translateUcFirstLower('THE FOLLOWING ULNS HAVE AN INCORRECT FORMAT') . ': '.ArrayUtil::implode($newUlnsWithInvalidFormatByAnimalId).'.';
            $prefix = ' ';
        }

        if (count($newStnsWithInvalidFormatByAnimalId) > 0) {
            $errorMessage .= $prefix . $this->translateUcFirstLower('THE FOLLOWING STNS HAVE AN INCORRECT FORMAT') . ': '.ArrayUtil::implode($newStnsWithInvalidFormatByAnimalId).'.';
            $prefix = ' ';
        }


        $validateNewUlnsResult = $this->validateNewUlns($newUlnsByAnimalId, $currentAnimalsResult);
        if ($validateNewUlnsResult instanceof JsonResponse) {
            return $validateNewUlnsResult;
        } elseif (is_string($validateNewUlnsResult)) {
            $errorMessage .= $prefix . $validateNewUlnsResult;
            $prefix = ' ';
        }


        $validateNewStnsResult = $this->validateNewStns($newStnsByAnimalId, $currentAnimalsResult);
        if ($validateNewStnsResult instanceof JsonResponse) {
            return $validateNewStnsResult;
        } elseif (is_string($validateNewStnsResult)) {
            $errorMessage .= $prefix . $validateNewStnsResult;
            $prefix = ' ';
        }


        return $this->validationResult($errorMessage);
    }


    /**
     * @param array $newUlnsByAnimalId
     * @param Animal[] $currentAnimalsResult
     * @return bool|string|JsonResponse
     */
    private function validateNewUlns(array $newUlnsByAnimalId = [], array $currentAnimalsResult = [])
    {
        if (count($newUlnsByAnimalId) === 0) {
            return true;
        }

        try {
            $duplicateCountByDuplicateUlns = $this->getManager()->getRepository(Animal::class)->getDuplicateCountsByUln($newUlnsByAnimalId);

            if (count($duplicateCountByDuplicateUlns) > 0) {
                $errorMessage = $this->translateUcFirstLower('THE FOLLOWING NEW ULNS ALREADY BELONG TO MORE THAN ONE ANIMAL').': ';
                $prefix = '';
                foreach ($duplicateCountByDuplicateUlns as $uln => $duplicateCount) {
                    $errorMessage .= $prefix . $uln . '['.$duplicateCount.'x]';
                    $prefix = ', ';
                }
                return ResultUtil::errorResult($errorMessage, Response::HTTP_BAD_REQUEST);
            }

        } catch (\Exception $exception) {
            $this->logExceptionAsError($exception);
            return ResultUtil::errorResult('EXISTING DUPLICATE ULNS CHECK ERROR', Response::HTTP_INTERNAL_SERVER_ERROR);
        }


        try {
            $newAnimalIdsByNewUln = $this->returnOnlyAlreadyExistingAnimalIdByUln($newUlnsByAnimalId);

            $currentAnimalIdsBynewUln = array_flip($newUlnsByAnimalId);
        } catch (\Exception $exception) {
            $this->logExceptionAsError($exception);
            return ResultUtil::errorResult('CURRENT ANIMALS UNABLE TO BE RETRIEVED BY CURRENT NEW ULNS', Response::HTTP_INTERNAL_SERVER_ERROR);
        }


        /*
         * Only allow using ulns of already existing animals if the two animals swap both their ulns at the same time.
         *
         * Verify if following allowed situation is occuring if animal A new uln is already in current use by another animal:
         *
         * Animal A - currentId_animalA    :   newUln_animalB = currentUln_animalA
         *
         * Animal B - currentId_animalB    :   newUln_animalA = currentUln_animalB
         *
         */

        $validUlns = [];
        $invalidUlns = [];

        foreach ($newAnimalIdsByNewUln as $newUlnAnimalA_currentUlnAnimalB => $currentIdAnimalB) {
            if (key_exists($newUlnAnimalA_currentUlnAnimalB, $validUlns)) {
                continue;
            }

            try {
                /**
                 * @var Animal $currentAnimalA
                 * @var Animal $currentAnimalB
                 */
                $currentAnimalB = ArrayUtil::get($currentIdAnimalB, $currentAnimalsResult);

                $currentIdAnimalA = ArrayUtil::get($newUlnAnimalA_currentUlnAnimalB, $currentAnimalIdsBynewUln);
                $currentAnimalA = $currentIdAnimalA === null ? null : ArrayUtil::get($currentIdAnimalA, $currentAnimalsResult);

            } catch (\Exception $exception) {
                $this->logExceptionAsError($exception);
                return ResultUtil::errorResult('CURRENT ANIMALS WERE NOT PROPERLY RETRIEVED', Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            if ($currentAnimalA && $currentAnimalB) {

                $newUlnAnimalB = ArrayUtil::get($currentIdAnimalB, $newUlnsByAnimalId);

                if ($newUlnAnimalB) {
                    $currentUln_animalA = $currentAnimalA->getUln();

                    if ($currentUln_animalA === $newUlnAnimalB) {
                        // Ulns are properly swapped
                        $validUlns[$newUlnAnimalB] = $newUlnAnimalB;
                        $validUlns[$newUlnAnimalA_currentUlnAnimalB] = $newUlnAnimalA_currentUlnAnimalB;
                        continue;
                    }
                }

            }

            $invalidUlns[$newUlnAnimalA_currentUlnAnimalB] = $newUlnAnimalA_currentUlnAnimalB;
        }

        if (count($invalidUlns) === 0) {
            return true;
        }

        $errorMessage =
            $this->translateUcFirstLower('THE FOLLOWING ULNS ARE ALREADY IN USE BY OTHER ANIMALS')
            .': '.implode(', ', $invalidUlns) . '. '.
            $this->translateUcFirstLower('USING USED ULNS IS ONLY ALLOWED IF THE ULN IS ONLY USED BY ONE ANIMAL AND IF ULNS ARE SIMULTANEOUSLY SWAPPED BETWEEN TWO ANIMALS') .'.';

        return $errorMessage;
    }


    /**
     * @param array $setOfUniqueUlns
     * @return array
     * @throws DBALException
     */
    private function returnOnlyAlreadyExistingAnimalIdByUln(array $setOfUniqueUlns = [])
    {
        $sql = "SELECT id, CONCAT(uln_country_code, uln_number) as uln 
                FROM animal 
                WHERE CONCAT(uln_country_code, uln_number) 
                  IN (".SqlUtil::getFilterListString($setOfUniqueUlns, true).")";
        $results = $this->getConnection()->query($sql)->fetchAll();
        return SqlUtil::groupSqlResultsOfKey1ByKey2('id', 'uln', $results,true, false);
    }


    /**
     * @param array $newStnsByAnimalId
     * @param Animal[] $currentAnimalsResult
     * @return bool|string|JsonResponse
     */
    private function validateNewStns(array $newStnsByAnimalId = [], array $currentAnimalsResult = [])
    {
        if (count($newStnsByAnimalId) === 0) {
            return true;
        }

        try {
            $duplicateCountByDuplicateStns = $this->getManager()->getRepository(Animal::class)->getDuplicateCountsByStn($newStnsByAnimalId);

            if (count($duplicateCountByDuplicateStns) > 0) {
                $errorMessage = $this->translateUcFirstLower('THE FOLLOWING NEW PEDIGREE NUMBERS ALREADY BELONG TO MORE THAN ONE ANIMAL').': ';
                $prefix = '';
                foreach ($duplicateCountByDuplicateStns as $stn => $duplicateCount) {
                    $errorMessage .= $prefix . $stn . '['.$duplicateCount.'x]';
                    $prefix = ', ';
                }
                return ResultUtil::errorResult($errorMessage, Response::HTTP_BAD_REQUEST);
            }

        } catch (\Exception $exception) {
            $this->logExceptionAsError($exception);
            return ResultUtil::errorResult('EXISTING DUPLICATE STNS CHECK ERROR', Response::HTTP_INTERNAL_SERVER_ERROR);
        }


        try {
            $currentAnimalIdsByNewStn = $this->returnOnlyAlreadyExistingAnimalIdByStn($newStnsByAnimalId);
        } catch (\Exception $exception) {
            $this->logExceptionAsError($exception);
            return ResultUtil::errorResult('CURRENT ANIMALS UNABLE TO BE RETRIEVED BY CURRENT NEW STNS', Response::HTTP_INTERNAL_SERVER_ERROR);
        }


        /*
         * New stn is invalid if it is already in use by another animal
         */

        $invalidNewStnsByCurrentAnimalId = [];

        foreach ($currentAnimalIdsByNewStn as $newStnAnimalA_currentStnAnimalB => $currentIdAnimalA) {

            try {
                /**
                 * @var Animal $currentAnimalA
                 */
                $currentAnimalA = ArrayUtil::get($currentIdAnimalA, $currentAnimalsResult);
            } catch (\Exception $exception) {
                $this->logExceptionAsError($exception);
                return ResultUtil::errorResult('CURRENT ANIMALS WERE NOT PROPERLY RETRIEVED', Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $isInvalid = false;
            if ($currentAnimalA) {
                if ($currentAnimalA->getPedigreeString() !== $newStnAnimalA_currentStnAnimalB) {
                    $isInvalid = true;
                }
            } else {
                $isInvalid = true;
            }

            if ($isInvalid) {
                $invalidNewStnsByCurrentAnimalId[$currentIdAnimalA] = $newStnAnimalA_currentStnAnimalB;
            }
        }

        if (count($invalidNewStnsByCurrentAnimalId) === 0) {
            return true;
        }

        $errorMessage =
            $this->translateUcFirstLower('THE FOLLOWING STNS ARE ALREADY IN USE BY OTHER ANIMALS')
            .': '.implode(', ', $invalidNewStnsByCurrentAnimalId) . '. '
        ;
        return $errorMessage;
    }


    /**
     * @param array $setOfUniqueStns
     * @return array
     * @throws DBALException
     */
    private function returnOnlyAlreadyExistingAnimalIdByStn(array $setOfUniqueStns = [])
    {
        $sql = "SELECT id, CONCAT(pedigree_country_code, pedigree_number) as stn 
                FROM animal 
                WHERE CONCAT(pedigree_country_code, pedigree_number) 
                  IN (".SqlUtil::getFilterListString($setOfUniqueStns, true).")";
        $results = $this->getConnection()->query($sql)->fetchAll();
        return SqlUtil::groupSqlResultsOfKey1ByKey2('id', 'stn', $results,true, false);
    }


    /**
     * @param string $errorMessage
     * @return JsonResponse|bool
     */
    private function validationResult($errorMessage = '')
    {
        return $errorMessage !== '' ? ResultUtil::errorResult($errorMessage, Response::HTTP_PRECONDITION_REQUIRED) : true;
    }
}
