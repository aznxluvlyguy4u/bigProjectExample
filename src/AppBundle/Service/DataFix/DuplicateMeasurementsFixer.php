<?php


namespace AppBundle\Service\DataFix;


use AppBundle\Entity\BodyFat;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Exterior;
use AppBundle\Entity\Measurement;
use AppBundle\Entity\MuscleThickness;
use AppBundle\Entity\ScanMeasurementInterface;
use AppBundle\Entity\Weight;
use AppBundle\model\measurements\BodyFatData;
use AppBundle\model\measurements\ExteriorData;
use AppBundle\model\measurements\MeasurementData;
use AppBundle\model\measurements\MuscleThicknessData;
use AppBundle\model\measurements\WeightData;
use AppBundle\Util\ClassUtil;

class DuplicateMeasurementsFixer extends DuplicateFixerBase
{
    const PRIORITY_WEIGHT_ALL_VALUES_ARE_ONE = 10;
    const PRIORITY_WEIGHT_IS_BIRTH_WEIGHT = 20;
    const PRIORITY_WEIGHT_LOG_DATE = 2;
    const PRIORITY_WEIGHT_HAS_INSPECTOR = 6;
    const PRIORITY_WEIGHT_MEASUREMENT_ID = 1;


    public function deactivateDuplicateMeasurements()
    {
        $this->logger->notice('Deleting duplicate measurements...');

        $this->deactivateDuplicateBodyFats();
        $this->deactivateDuplicateExteriors();
        $this->deactivateDuplicateMuscleThicknesses();
        $this->deactivateDuplicateWeights();
    }


    private static function getSortedLogDates(array $measurementGroup)
    {
        $logDates = array_map(function (MeasurementData $measurementData) {
            return $measurementData->logDate;
        }, $measurementGroup);

        // The younger logDates will have higher integer key values
        sort($logDates);

        return $logDates;
    }

    private static function getSortedIds(array $measurementGroup)
    {
        $ids = array_map(function (MeasurementData $measurementData) {
            return $measurementData->id;
        }, $measurementGroup);

        // The higher ids will have higher integer key values
        sort($ids);

        return $ids;
    }

    private static function getLogDataPriorityValue(\DateTime $measurementLogDate, $sortedLogDates): int
    {
        $logDataPriority = key(
            array_filter(
                $sortedLogDates,
                function ($logDate) use ($measurementLogDate) {
                    return $logDate == $measurementLogDate;
                })
        );

        return $logDataPriority * self::PRIORITY_WEIGHT_LOG_DATE;
    }

    private static function getIdPriorityValue(int $measurementId, $sortedIds): int
    {
        $idPriority = key(
            array_filter(
                $sortedIds,
                function ($id) use ($measurementId) {
                    return $id == $measurementId;
                })
        );

        return $idPriority * self::PRIORITY_WEIGHT_MEASUREMENT_ID;
    }


    private static function getMaxPriorityLevel($measurementDataGroup): int
    {
        return max(
            array_map(function (MeasurementData $measurementData) {
                return $measurementData->priorityLevel;
            }, $measurementDataGroup)
        );
    }


    private function deactivateMeasurement(?int $measurementId, Employee $automatedProcess, bool $flush = true)
    {
        if ($measurementId == null) {
            return;
        }

        /** @var Measurement $measurement */
        $measurement = $this->em->getRepository(Measurement::class)->find($measurementId);

        if ($measurement->isIsActive()) {
            $measurement->setDeleteDate(new \DateTime());
            $measurement->setDeletedBy($automatedProcess);
            $measurement->setIsActive(false);
        }

        switch (true) {
            case $measurement instanceof BodyFat:

                $fat1Id = $measurement->getFat1() ? $measurement->getFat1()->getId() : null;
                $fat2Id = $measurement->getFat2() ? $measurement->getFat2()->getId() : null;
                $fat3Id = $measurement->getFat3() ? $measurement->getFat3()->getId() : null;

                self::deactivateMeasurement($fat1Id, $automatedProcess, false);
                self::deactivateMeasurement($fat2Id, $automatedProcess, false);
                self::deactivateMeasurement($fat3Id, $automatedProcess, false);

                break;

            case $measurement instanceof Weight:
                $measurement->deactivateWeight();

                break;
            default:
                break;
        }

        if ($measurement instanceof ScanMeasurementInterface) {
            $measurement->setScanMeasurementSet(null);
        }

        $this->em->persist($measurement);

        if ($flush) {
            $this->em->flush();
        }
        $className = ClassUtil::getShortName($measurement);
        $this->logger->notice("Deleted $className measurement with id: $measurementId");
    }


    private function deactivateDuplicateBodyFats()
    {
        $automatedProcess = $this->em->getRepository(Employee::class)->getAutomatedProcess();


        $measurementsFixedCount = 0;

        $bodyFatsGroupedByAnimalAndDate = $this->em->getRepository(BodyFat::class)->getContradictingBodyFats();

        if (empty($bodyFatsGroupedByAnimalAndDate)) {
            $this->logger->notice('No duplicate body fats found');
            return;
        }


        /** @var BodyFatData[] $bodyFatGroup */
        foreach ($bodyFatsGroupedByAnimalAndDate as $bodyFatGroup)
        {

            $sortedLogDates = self::getSortedLogDates($bodyFatGroup);
            $sortedIds = self::getSortedIds($bodyFatGroup);

            $prioritizedBodyFatGroup = array_map(function (BodyFatData $bodyFatData) use ($sortedLogDates, $sortedIds) {
                $priorityLevel = 0;

                if ($bodyFatData->areAllValuesOne()) {
                    $priorityLevel -= self::PRIORITY_WEIGHT_ALL_VALUES_ARE_ONE;
                }

                $priorityLevel += self::getLogDataPriorityValue($bodyFatData->logDate, $sortedLogDates);

                if ($bodyFatData->hasInspector()) {
                    $priorityLevel += self::PRIORITY_WEIGHT_HAS_INSPECTOR;
                }

                $priorityLevel += self::getIdPriorityValue($bodyFatData->id, $sortedIds);

                $bodyFatData->priorityLevel = $priorityLevel;

                return $bodyFatData;
            }, $bodyFatGroup);


            $maxPriorityLevel = self::getMaxPriorityLevel($bodyFatGroup);

            /** @var BodyFatData $bodyFatData */
            foreach ($prioritizedBodyFatGroup as $bodyFatData)
            {
                if ($bodyFatData->priorityLevel == $maxPriorityLevel) {
                    $this->logger->notice('Keep body fat with id: '.$bodyFatData->id);
                    continue;
                }

                $this->deactivateMeasurement($bodyFatData->id, $automatedProcess);
                $measurementsFixedCount++;
            }
        }
        $this->logger->notice('Deactivated duplicate body fats: '.$measurementsFixedCount);
    }

    private function deactivateDuplicateMuscleThicknesses()
    {
        $automatedProcess = $this->em->getRepository(Employee::class)->getAutomatedProcess();

        $measurementsFixedCount = 0;
        $className = "muscle thicknesses";

        $muscleThicknessGroupedByAnimalAndDate = $this->em->getRepository(MuscleThickness::class)->getContradictingMuscleThicknesses();

        if (empty($muscleThicknessGroupedByAnimalAndDate)) {
            $this->logger->notice("No duplicate $className found");
            return;
        }


        /** @var MuscleThicknessData[] $muscleThicknessGroup */
        foreach ($muscleThicknessGroupedByAnimalAndDate as $muscleThicknessGroup)
        {
            $sortedLogDates = self::getSortedLogDates($muscleThicknessGroup);
            $sortedIds = self::getSortedIds($muscleThicknessGroup);

            $prioritizedMuscleThicknessGroup = array_map(function (MuscleThicknessData $muscleThicknessData) use ($sortedLogDates, $sortedIds) {
                $priorityLevel = 0;

                $priorityLevel += self::getLogDataPriorityValue($muscleThicknessData->logDate, $sortedLogDates);

                if ($muscleThicknessData->hasInspector()) {
                    $priorityLevel += self::PRIORITY_WEIGHT_HAS_INSPECTOR;
                }

                $priorityLevel += self::getIdPriorityValue($muscleThicknessData->id, $sortedIds);

                $muscleThicknessData->priorityLevel = $priorityLevel;

                return $muscleThicknessData;
            }, $muscleThicknessGroup);


            $maxPriorityLevel = self::getMaxPriorityLevel($muscleThicknessGroup);

            /** @var MuscleThicknessData $muscleThicknessData */
            foreach ($prioritizedMuscleThicknessGroup as $muscleThicknessData)
            {
                if ($muscleThicknessData->priorityLevel == $maxPriorityLevel) {
                    $this->logger->notice("Keep $className with id: ".$muscleThicknessData->id);
                    continue;
                }

                $this->deactivateMeasurement($muscleThicknessData->id, $automatedProcess);

                $measurementsFixedCount++;
                $this->logger->notice("Delete $className with id: ".$muscleThicknessData->id);
            }
        }
        $this->logger->notice("Deactivated duplicate $className: ".$measurementsFixedCount);
    }

    private function deactivateDuplicateExteriors()
    {
        $automatedProcess = $this->em->getRepository(Employee::class)->getAutomatedProcess();

        $measurementsFixedCount = 0;
        $className = "exterior";

        $exteriorsGroupedByAnimalAndDate = $this->em->getRepository(Exterior::class)->getContradictingExteriors();

        if (empty($exteriorsGroupedByAnimalAndDate)) {
            $this->logger->notice("No duplicate $className found");
            return;
        }


        /** @var ExteriorData[] $exteriorGroup */
        foreach ($exteriorsGroupedByAnimalAndDate as $exteriorGroup)
        {
            $sortedLogDates = self::getSortedLogDates($exteriorGroup);
            $sortedIds = self::getSortedIds($exteriorGroup);

            $prioritizedExteriorGroup = array_map(function (ExteriorData $exteriorData) use ($sortedLogDates, $sortedIds) {
                $priorityLevel = 0;

                $priorityLevel += self::getLogDataPriorityValue($exteriorData->logDate, $sortedLogDates);

                if ($exteriorData->hasInspector()) {
                    $priorityLevel += self::PRIORITY_WEIGHT_HAS_INSPECTOR;
                }

                $priorityLevel += self::getIdPriorityValue($exteriorData->id, $sortedIds);

                $exteriorData->priorityLevel = $priorityLevel;

                return $exteriorData;
            }, $exteriorGroup);


            $maxPriorityLevel = self::getMaxPriorityLevel($exteriorGroup);

            /** @var ExteriorData $exteriorData */
            foreach ($prioritizedExteriorGroup as $exteriorData)
            {
                if ($exteriorData->priorityLevel == $maxPriorityLevel) {
                    $this->logger->notice("Keep $className with id: ".$exteriorData->id);
                    continue;
                }

                $this->deactivateMeasurement($exteriorData->id, $automatedProcess);

                $measurementsFixedCount++;
                $this->logger->notice("Delete $className with id: ".$exteriorData->id);
            }
        }
        $this->logger->notice("Deactivated duplicate $className: ".$measurementsFixedCount);
    }

    private function deactivateDuplicateWeights()
    {
        $automatedProcess = $this->em->getRepository(Employee::class)->getAutomatedProcess();

        $measurementsFixedCount = 0;
        $className = "weights";

        $weightsGroupedByAnimalAndDate = $this->em->getRepository(Weight::class)->getContradictingWeights();

        if (empty($weightsGroupedByAnimalAndDate)) {
            $this->logger->notice("No duplicate $className found");
            return;
        }


        /** @var WeightData[] $weightGroup */
        foreach ($weightsGroupedByAnimalAndDate as $weightGroup)
        {
            $sortedLogDates = self::getSortedLogDates($weightGroup);
            $sortedIds = self::getSortedIds($weightGroup);

            $prioritizedWeightGroup = array_map(function (WeightData $weightData) use ($sortedLogDates, $sortedIds) {
                $priorityLevel = 0;

                $priorityLevel += self::getLogDataPriorityValue($weightData->logDate, $sortedLogDates);

                if ($weightData->hasInspector()) {
                    $priorityLevel += self::PRIORITY_WEIGHT_HAS_INSPECTOR;
                }

                if ($weightData->isBirthWeight) {
                    $priorityLevel += self::PRIORITY_WEIGHT_IS_BIRTH_WEIGHT;
                }

                $priorityLevel += self::getIdPriorityValue($weightData->id, $sortedIds);

                $weightData->priorityLevel = $priorityLevel;

                return $weightData;
            }, $weightGroup);


            $maxPriorityLevel = self::getMaxPriorityLevel($weightGroup);

            /** @var WeightData $weightData */
            foreach ($prioritizedWeightGroup as $weightData)
            {
                if ($weightData->priorityLevel == $maxPriorityLevel) {
                    $this->logger->notice("Keep $className with id: ".$weightData->id);
                    continue;
                }

                $this->deactivateMeasurement($weightData->id, $automatedProcess);

                $measurementsFixedCount++;
                $this->logger->notice("Delete $className with id: ".$weightData->id);
            }
        }
        $this->logger->notice("Deactivated duplicate $className: ".$measurementsFixedCount);
    }

}
