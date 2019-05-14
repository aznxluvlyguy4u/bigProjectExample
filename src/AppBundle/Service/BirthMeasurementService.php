<?php

namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Controller\BirthMeasurementAPIControllerInterface;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Person;
use AppBundle\Entity\TailLength;
use AppBundle\Entity\Weight;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\JsonFormat\EditBirthMeasurementsJsonFormat;
use AppBundle\Output\BirthMeasurements\BirthMeasurementsOutput;
use AppBundle\Output\BirthMeasurements\BirthWeightOutput;
use AppBundle\Output\BirthMeasurements\TailLengthOutput;
use AppBundle\Util\NumberUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BirthMeasurementService extends ControllerServiceBase implements BirthMeasurementAPIControllerInterface
{
    /**
     * @param Request $request
     * @param $animalId
     * @return JsonResponse
     * @throws \Exception
     */
    function editBirthMeasurements(Request $request, $animalId)
    {
        $actionBy = $this->getUser();
        $isAdmin = AdminValidator::isAdmin($actionBy, AccessLevelType::ADMIN);
        if (!$isAdmin) { throw AdminValidator::standardException(); }

        $animal = $this->getAnimal($animalId);
        $content = $this->getContent($request);

        $this->getManager()->beginTransaction(); // suspend auto-commit
        try {
            $isBirthWeightUpdated = $this->updateBirthWeight($actionBy, $animal, $animal->getLatestBirthWeight(), $content);
            $isTailLengthUpdated = $this->updateTailLength($actionBy, $animal, $animal->getLatestTailLength(), $content);
            if ($isBirthWeightUpdated || $isTailLengthUpdated) {
                $this->getManager()->flush();
            } else {
                $this->getManager()->clear();
            }
            $this->getManager()->commit();
        } catch (\Exception $exception) {
            $this->getManager()->rollback();
            throw $exception;
        }

        return $this->getResponse($animalId);
    }


    private function getResponse($animalId) {
        $animal = $this->getAnimal($animalId);
        $body = new BirthMeasurementsOutput();

        $birthWeight = $animal->getLatestBirthWeight();
        if ($birthWeight) {
            $body->setBirthWeight(new BirthWeightOutput($birthWeight));
        }

        $tailLength = $animal->getLatestTailLength();
        if ($tailLength) {
            $body->setTailLength(new TailLengthOutput($tailLength));
        }

        return ResultUtil::successResult($body);
    }


    /**
     * @param Person $actionBy
     * @param Animal $animal
     * @param Weight|null $currentBirthWeight
     * @param EditBirthMeasurementsJsonFormat $content
     * @return bool
     * @throws \Exception
     */
    private function updateBirthWeight(Person $actionBy, Animal $animal, ?Weight $currentBirthWeight,
                                       EditBirthMeasurementsJsonFormat $content): bool
    {
        $isUpdated = false;
        $newBirthWeightValue = $content->getBirthWeight();

        $birth = $animal->getLatestBirth();
        $updateBirthValue = false;
        $emptyBirthValue = false;

        if ($currentBirthWeight instanceof Weight) {
            if ($newBirthWeightValue === null) {
                $currentBirthWeight->setIsActive(false);
                $currentBirthWeight->setDeleteDate(new \DateTime());
                $currentBirthWeight->setDeletedBy($actionBy);
                $currentBirthWeight->setIsRevoked(!$currentBirthWeight->isIsActive());
                $currentBirthWeight->setActionBy($actionBy);
                $this->getManager()->persist($currentBirthWeight);
                $isUpdated = true;

                $updateBirthValue = true;
                $emptyBirthValue = true;

            } elseif (!NumberUtil::areFloatsEqual($newBirthWeightValue, $currentBirthWeight->getWeight())) {
                $currentBirthWeight->setWeight($newBirthWeightValue);
                $currentBirthWeight->setIsActive(true);
                if ($content->isResetMeasurementDateUsingDateOfBirth()) {
                    $currentBirthWeight->setMeasurementDate($animal->getDateOfBirth());
                }
                $currentBirthWeight->setEditDate(new \DateTime());
                $currentBirthWeight->setIsRevoked(!$currentBirthWeight->isIsActive());
                $currentBirthWeight->setActionBy($actionBy);
                $this->getManager()->persist($currentBirthWeight);
                $isUpdated = true;

                $updateBirthValue = true;
                $emptyBirthValue = false;
            }

        } elseif ($newBirthWeightValue !== null) {
            $measurementDate = $animal->getDateOfBirth();
            $newBirthWeight = new Weight();
            $newBirthWeight->setAnimal($animal);
            $newBirthWeight->setMeasurementDate($measurementDate);
            $newBirthWeight->setIsBirthWeight(true);
            $newBirthWeight->setWeight($newBirthWeightValue);
            $newBirthWeight->setActionBy($actionBy);
            $newBirthWeight->setAnimalIdAndDateByAnimalAndDateTime($animal,$measurementDate);
            $newBirthWeight->setIsActive(true);
            $newBirthWeight->setIsRevoked(false);
            $this->getManager()->persist($newBirthWeight);
            $isUpdated = true;

            $updateBirthValue = true;
            $emptyBirthValue = false;
        }

        if ($birth && $updateBirthValue && $emptyBirthValue) {
            $birth->setEmptyBirthWeight();
            $this->getManager()->persist($birth);
        } elseif ($birth && $updateBirthValue && !$emptyBirthValue) {
            $birth->setBirthWeight($newBirthWeightValue);
            $this->getManager()->persist($birth);
        }

        return $isUpdated;
    }


    /**
     * @param Person $actionBy
     * @param Animal $animal
     * @param TailLength|null $currentTailLength
     * @param EditBirthMeasurementsJsonFormat $content
     * @return bool
     * @throws \Exception
     */
    private function updateTailLength(Person $actionBy, Animal $animal, ?TailLength $currentTailLength,
                                      EditBirthMeasurementsJsonFormat $content): bool
    {
        $isUpdated = false;
        $newTailLengthValue = $content->getTailLength();
        $birth = $animal->getLatestBirth();
        $updateBirthValue = false;
        $emptyBirthValue = false;

        if ($currentTailLength instanceof TailLength) {
            if ($newTailLengthValue === null) {
                $currentTailLength->setIsActive(false);
                $currentTailLength->setDeleteDate(new \DateTime());
                $currentTailLength->setDeletedBy($actionBy);
                $currentTailLength->setActionBy($actionBy);
                $this->getManager()->persist($currentTailLength);
                $isUpdated = true;

                $updateBirthValue = true;
                $emptyBirthValue = true;

            } elseif (!NumberUtil::areFloatsEqual($newTailLengthValue, $currentTailLength->getLength())) {
                $currentTailLength->setLength($newTailLengthValue);
                $currentTailLength->setIsActive(true);
                if ($content->isResetMeasurementDateUsingDateOfBirth()) {
                    $currentTailLength->setMeasurementDate($animal->getDateOfBirth());
                }
                $currentTailLength->setEditDate(new \DateTime());
                $currentTailLength->setActionBy($actionBy);
                $this->getManager()->persist($currentTailLength);
                $isUpdated = true;

                $updateBirthValue = true;
                $emptyBirthValue = false;
            }

        } elseif ($newTailLengthValue !== null) {
            $measurementDate = $animal->getDateOfBirth();
            $newTailLength = new TailLength();
            $newTailLength->setAnimal($animal);
            $newTailLength->setMeasurementDate($measurementDate);
            $newTailLength->setLength($newTailLengthValue);
            $newTailLength->setActionBy($actionBy);
            $newTailLength->setAnimalIdAndDateByAnimalAndDateTime($animal,$measurementDate);
            $newTailLength->setIsActive(true);
            $this->getManager()->persist($newTailLength);
            $isUpdated = true;

            $updateBirthValue = true;
            $emptyBirthValue = false;
        }

        if ($birth && $updateBirthValue && $emptyBirthValue) {
            $birth->setEmptyBirthTailLength();
            $this->getManager()->persist($birth);
        } elseif ($birth && $updateBirthValue && !$emptyBirthValue) {
            $birth->setBirthTailLength($newTailLengthValue);
            $this->getManager()->persist($birth);
        }

        return $isUpdated;
    }


    private function getAnimal($animalId): Animal {
        $animal = $this->getManager()->getRepository(Animal::class)->find(intval($animalId));
        if (!$animal || !($animal instanceof Animal)) { throw new NotFoundHttpException("No Animal Found with animalId: ".$animalId); }
        return $animal;
    }


    private function getContent(Request $request): EditBirthMeasurementsJsonFormat {
        $content = new EditBirthMeasurementsJsonFormat();
        $requestBody = RequestUtil::getContentAsArray($request);

        $content
            ->setBirthWeight($this->getFloatValueInput(JsonInputConstant::BIRTH_WEIGHT, $requestBody))
            ->setTailLength($this->getFloatValueInput(JsonInputConstant::TAIL_LENGTH, $requestBody))
            ->setResetMeasurementDateUsingDateOfBirth(
                $requestBody->get(JsonInputConstant::RESET_MEASUREMENT_DATE_USING_DATE_OF_BIRTH) ?? false
            )
        ;
        return $content;
    }


    /**
     * @param string $key
     * @param ArrayCollection $content
     * @return float|null
     */
    private function getFloatValueInput($key, $content) {
        if (!$content->containsKey($key)) {
            return null;
        }

        $input = $content->get($key);

        if (!is_float($input)) {
            if (is_numeric($input)) {
                $input = floatval($input);
            } else {
                throw new BadRequestHttpException($key.' has invalid format. It must be a float or null');
            }
        }
        return $input;
    }

}