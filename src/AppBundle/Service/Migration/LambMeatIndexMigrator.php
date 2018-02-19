<?php


namespace AppBundle\Service\Migration;


use AppBundle\Constant\BreedValueTypeConstant;
use AppBundle\Entity\BreedIndexCoefficient;

class LambMeatIndexMigrator extends IndexMigratorBase implements IndexMigratorInterface
{
    public function migrate()
    {
        $startDate = new \DateTime('2016-01-01');

        $lambMeatIndexType = $this->getLambMeatIndexType();
        $breedIndexCoefficients = $this->getBreedIndexCoefficients($lambMeatIndexType);

        $growthBreedValueType = $this->getBreedValueType(BreedValueTypeConstant::GROWTH);
        $fatThicknessBreedValueType = $this->getBreedValueType(BreedValueTypeConstant::FAT_THICKNESS_3);
        $muscleThicknessBreedValueType = $this->getBreedValueType(BreedValueTypeConstant::MUSCLE_THICKNESS);

        $createNewGrowthBreedIndexCoefficient = true;
        $createNewFatBreedIndexCoefficient = true;
        $createNewMuscleThicknessBreedIndexCoefficient = true;

        $growthBreedIndexCoefficient = (new BreedIndexCoefficient())
            ->setBreedIndexType($lambMeatIndexType)
            ->setBreedValueType($growthBreedValueType)
            ->setStartDate($startDate)
            ->setC(200)
            ->setVar(0.0004839)
            ->setT(23.42076)
        ;

        $fatThicknessBreedIndexCoefficient = (new BreedIndexCoefficient())
            ->setBreedIndexType($lambMeatIndexType)
            ->setBreedValueType($fatThicknessBreedValueType)
            ->setStartDate($startDate)
            ->setC(-6.34)
            ->setVar(0.157)
            ->setT(6.3107092)
        ;

        $muscleThicknessBreedIndexCoefficient = (new BreedIndexCoefficient())
            ->setBreedIndexType($lambMeatIndexType)
            ->setBreedValueType($muscleThicknessBreedValueType)
            ->setStartDate($startDate)
            ->setC(3.7)
            ->setVar(1.848)
            ->setT(25.29912)
        ;

        $updateCount = 0;
        $insertCount = 0;

        /** @var BreedIndexCoefficient $breedIndexCoefficient */
        foreach ($breedIndexCoefficients as $breedIndexCoefficient)
        {
            switch ($breedIndexCoefficient->getBreedValueType()->getNl())
            {
                case BreedValueTypeConstant::GROWTH:
                    $createNewGrowthBreedIndexCoefficient = false;
                    $updatedValues = $this->updateValues($breedIndexCoefficient, $growthBreedIndexCoefficient);
                    if($updatedValues) { $updateCount++; }
                    break;

                case BreedValueTypeConstant::FAT_THICKNESS_3:
                    $createNewFatBreedIndexCoefficient = false;
                    $updatedValues = $this->updateValues($breedIndexCoefficient, $fatThicknessBreedIndexCoefficient);
                    if($updatedValues) { $updateCount++; }
                    break;

                case BreedValueTypeConstant::MUSCLE_THICKNESS:
                    $createNewMuscleThicknessBreedIndexCoefficient = false;
                    $updatedValues = $this->updateValues($breedIndexCoefficient, $muscleThicknessBreedIndexCoefficient);
                    if($updatedValues) { $updateCount++; }
                    break;
            }
        }

        if($createNewGrowthBreedIndexCoefficient) {
            $this->getManager()->persist($growthBreedIndexCoefficient);
            $insertCount++;
        }

        if($createNewFatBreedIndexCoefficient) {
            $this->getManager()->persist($fatThicknessBreedIndexCoefficient);
            $insertCount++;
        }

        if($createNewMuscleThicknessBreedIndexCoefficient) {
            $this->getManager()->persist($muscleThicknessBreedIndexCoefficient);
            $insertCount++;
        }

        if($insertCount > 0 || $updateCount > 0) {
            $this->getManager()->flush();
        }

        $this->getLogger()->notice('LambMeatIndex insert|update: '.$insertCount.'|'.$updateCount);
    }


}