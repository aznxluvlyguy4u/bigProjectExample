<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
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
                    default: break;
                }
                $errorMessage .= $prefix . $this->translateUcFirstLower($errorMessageKey) . ': '.implode(', ', $duplicateValues).'.';
                $prefix = ' ';
            }
        }

        return $errorMessage !== '' ? ResultUtil::errorResult($errorMessage, Response::HTTP_PRECONDITION_REQUIRED) : true;
    }


    /**
     * @param Animal[] $animalsWithNewValues
     * @param Animal[] $currentAnimalsResult
     * @return JsonResponse|bool
     */
    private function validateInputWithDatabaseValues(array $animalsWithNewValues, array $currentAnimalsResult)
    {
        //TODO validate for duplicate ulns inside the database

        //TODO validate for duplicate stns inside the database

        $idsNotFound = [];
        /** @var  $animalsWithNewValue */
        foreach ($animalsWithNewValues as $animalsWithNewValue)
        {
            if (!key_exists($animalsWithNewValue->getId(), $currentAnimalsResult)) {
                $idsNotFound[$animalsWithNewValue->getId()] = $animalsWithNewValue->getId();
            }
        }

        $errorMessage = '';
        $prefix = '';
        if (count($idsNotFound) > 0) {
            $errorMessage .= $prefix . $this->translateUcFirstLower('THE FOLLOWING IDS WERE NOT FOUND IN THE DATABASE') . ': '.implode(', ', $idsNotFound).'.';
            $prefix = ' ';
        }

        return $errorMessage !== '' ? ResultUtil::errorResult($errorMessage, Response::HTTP_PRECONDITION_REQUIRED) : true;
    }
}