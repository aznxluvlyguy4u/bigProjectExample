<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\Validator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AnimalDetailsBatchUpdaterService extends ControllerServiceBase
{
    /**
     * @param Request $request
     * @return \AppBundle\Component\HttpFoundation\JsonResponse
     */
    public function updateAnimalDetails(Request $request)
    {
        $content = RequestUtil::getContentAsArray($request);

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
        } catch (\Exception $exception) {
            return ResultUtil::errorResult('BAD REQUEST', Response::HTTP_BAD_REQUEST);
        }

        $inputWithDatabaseValuesValidationResult = $this->validateInputWithDatabaseValues($animalsWithNewValues, $currentAnimalsResult);
        if ($inputWithDatabaseValuesValidationResult instanceof JsonResponse) {
            return $inputWithDatabaseValuesValidationResult;
        }


        // TODO update changed values

        // TODO log changes


        $serializedAnimalsOutput = AnimalService::getSerializedAnimalsInBatchEditFormat($this, $currentAnimalsResult);

        return ResultUtil::successResult([
            JsonInputConstant::ANIMALS => $serializedAnimalsOutput[JsonInputConstant::ANIMALS]
        ]);
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
                    case JsonInputConstant::ULN: $errorMessageKey = 'THE FOLLOWING DUPLICATE ULNS WERE INSERTED'; break;
                    case JsonInputConstant::STN: $errorMessageKey = 'THE FOLLOWING DUPLICATE PEDIGREE NUMBERS WERE INSERTED'; break;
                    case JsonInputConstant::ID: $errorMessageKey = 'THE FOLLOWING DUPLICATE IDS WERE INSERTED'; break;
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
        }

        $errorMessage = '';
        $prefix = '';
        foreach ($incorrectFormatSets as $typeKey => $incorrectFormatSet) {
            if (count($incorrectFormatSet) > 0) {
                switch ($typeKey) {
                    case JsonInputConstant::UBN: $errorMessageKey = 'THE FOLLOWING UBNS HAVE AN INCORRECT FORMAT'; break;
                    case JsonInputConstant::BREED_TYPE: $errorMessageKey = 'THE FOLLOWING BREED TYPES HAVE AN INCORRECT FORMAT'; break;
                    case JsonInputConstant::BLINDNESS_FACTOR: $errorMessageKey = 'THE FOLLOWING BLINDNESS FACTORS HAVE AN INCORRECT FORMAT'; break;
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
                if (Validator::verifyPedigreeCountryCodeAndNumberFormat($newStn, false)) {
                    $newStnsByAnimalId[$animalId] = $newStn;
                } else {
                    $newStnsWithInvalidFormatByAnimalId[$animalId] = $newStn;
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

        $validationResult1 = $this->validationResult($errorMessage);
        if ($validationResult1 instanceof JsonResponse) {
            return $validationResult1;
        }


        //TODO validate for duplicate ulns inside the database



        //TODO validate for duplicate stns inside the database




        return $this->validationResult($errorMessage);
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