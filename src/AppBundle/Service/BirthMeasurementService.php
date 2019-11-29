<?php

namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Controller\BirthMeasurementAPIControllerInterface;
use AppBundle\Entity\ActionLog;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Person;
use AppBundle\Entity\TailLength;
use AppBundle\Entity\Weight;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\UserActionType;
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
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

class BirthMeasurementService extends ControllerServiceBase implements BirthMeasurementAPIControllerInterface
{
    const MIN_BIRTH_WEIGHT = 0.0;
    const MAX_BIRTH_WEIGHT = 9.9;
    const MIN_TAIL_LENGTH = 0.0;
    const MAX_TAIL_LENGTH = 30;

    /**
     * @param Request $request
     * @param $animalId
     * @return JsonResponse
     * @throws \Exception
     */
    function editBirthMeasurements(Request $request, $animalId)
    {
        $actionBy = $this->getUser();
        AdminValidator::isAdmin($actionBy, AccessLevelType::ADMIN, true);

        $animal = $this->getAnimal($animalId);
        $content = $this->getContent($request);

        $this->getManager()->beginTransaction(); // suspend auto-commit
        try {
            $birthWeightUpdateMessage = $this->updateBirthWeight($actionBy, $animal, $animal->getLatestBirthWeight(), $content);
            $tailLengthUpdateMessage = $this->updateTailLength($actionBy, $animal, $animal->getLatestTailLength(), $content);
            if (!empty($birthWeightUpdateMessage) || !empty($tailLengthUpdateMessage)) {
                $this->logAction($actionBy, $animal, $birthWeightUpdateMessage, $tailLengthUpdateMessage);
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


    private function logAction(Person $actionBy, Animal $animal, $birthWeightUpdateMessage, $tailLengthUpdateMessage) {
        if (empty($birthWeightUpdateMessage) && empty($tailLengthUpdateMessage)) {
            return;
        }

        $description = AnimalDetailsBatchUpdaterService::getAnimalEditLogPrefix($animal);
        if (!empty($birthWeightUpdateMessage)) {
            $description .= $birthWeightUpdateMessage.'; ';
        }
        if (!empty($tailLengthUpdateMessage)) {
            $description .= $tailLengthUpdateMessage;
        }

        $log = new ActionLog(null, $actionBy, UserActionType::ADMIN_ANIMAL_EDIT,
            true, $description, true);
        $this->getManager()->persist($log);
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
     * @return string|null
     * @throws \Exception
     */
    private function updateBirthWeight(Person $actionBy, Animal $animal, ?Weight $currentBirthWeight,
                                       EditBirthMeasurementsJsonFormat $content): ?string
    {
        $isUpdated = false;
        $newBirthWeightValue = $content->getBirthWeight();

        $birth = $animal->getLatestBirth();
        $updateBirthValue = false;
        $emptyBirthValue = false;

        $oldValue = null;

        if ($currentBirthWeight instanceof Weight) {
            $oldValue = $currentBirthWeight->getWeight();
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

        return ($isUpdated) ? "Geboortegewicht "
            .($oldValue ?? AnimalDetailsUpdaterService::LOG_EMPTY).' => '
            .($newBirthWeightValue ?? AnimalDetailsUpdaterService::LOG_EMPTY) : null;
    }


    /**
     * @param Person $actionBy
     * @param Animal $animal
     * @param TailLength|null $currentTailLength
     * @param EditBirthMeasurementsJsonFormat $content
     * @return string|null
     * @throws \Exception
     */
    private function updateTailLength(Person $actionBy, Animal $animal, ?TailLength $currentTailLength,
                                      EditBirthMeasurementsJsonFormat $content): ?string
    {
        $isUpdated = false;
        $newTailLengthValue = $content->getTailLength();
        $birth = $animal->getLatestBirth();
        $updateBirthValue = false;
        $emptyBirthValue = false;

        $oldValue = null;

        if ($currentTailLength instanceof TailLength) {
            $oldValue = $currentTailLength->getLength();
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

        return ($isUpdated) ? "Staartlengte "
            .($oldValue ?? AnimalDetailsUpdaterService::LOG_EMPTY).' => '
            .($newTailLengthValue ?? AnimalDetailsUpdaterService::LOG_EMPTY) : null;
    }


    private function getAnimal($animalId): Animal {
        $animal = $this->getManager()->getRepository(Animal::class)->find(intval($animalId));
        if (!$animal || !($animal instanceof Animal)) { throw new NotFoundHttpException("No Animal Found with animalId: ".$animalId); }
        return $animal;
    }


    private function getContent(Request $request): EditBirthMeasurementsJsonFormat {
        $content = new EditBirthMeasurementsJsonFormat();
        $requestBody = RequestUtil::getContentAsArray($request);

        $birthWeight = $this->getFloatValueInput(JsonInputConstant::BIRTH_WEIGHT, $requestBody);
        $tailLength = $this->getFloatValueInput(JsonInputConstant::TAIL_LENGTH, $requestBody);

        $defaultResetMeasurementDate = false;
        $resetMeasurementDate = $requestBody->get(JsonInputConstant::RESET_MEASUREMENT_DATE_USING_DATE_OF_BIRTH)
            ?? $defaultResetMeasurementDate;
        $resetMeasurementDate = is_bool($resetMeasurementDate) ? $resetMeasurementDate : $defaultResetMeasurementDate;

        $this->validateBirthMeasurements($birthWeight, $tailLength);

        $content
            ->setBirthWeight($birthWeight)
            ->setTailLength($tailLength)
            ->setResetMeasurementDateUsingDateOfBirth($resetMeasurementDate)
        ;
        return $content;
    }


    private function validateBirthMeasurements($birthWeight, $tailLength) {
        $errorMessage = '';
        $errorSeparator = '';

        if ($birthWeight < self::MIN_BIRTH_WEIGHT || $birthWeight > self::MAX_BIRTH_WEIGHT) {
            $errorMessage .= $errorSeparator . $this->translator->trans(
                'INVALID BIRTH WEIGHT %minValue% %maxValue%',
                        [
                            '%minValue%' => self::MIN_BIRTH_WEIGHT,
                            '%maxValue%' => self::MAX_BIRTH_WEIGHT,
                        ]);
            $errorSeparator = '. ';
        }

        if ($tailLength < self::MIN_TAIL_LENGTH || $tailLength > self::MAX_TAIL_LENGTH) {
            $errorMessage .= $errorSeparator . $this->translator->trans(
                    'INVALID TAIL LENGTH %minValue% %maxValue%',
                    [
                        '%minValue%' => self::MIN_TAIL_LENGTH,
                        '%maxValue%' => self::MAX_TAIL_LENGTH,
                    ]);
        }

        if (!empty($errorMessage)) {
            throw new PreconditionFailedHttpException($errorMessage.'.');
        }
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