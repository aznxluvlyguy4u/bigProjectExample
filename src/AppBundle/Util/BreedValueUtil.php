<?php

namespace AppBundle\Util;


use AppBundle\Component\BreedGrading\BreedFormat;
use AppBundle\Component\Utils;
use AppBundle\Constant\BreedValueLabel;
use AppBundle\Entity\LambMeatBreedIndex;
use AppBundle\Entity\LambMeatBreedIndexRepository;
use AppBundle\Entity\NormalDistribution;
use AppBundle\Entity\NormalDistributionRepository;
use AppBundle\Enumerator\BreedValueCoefficientType;
use Doctrine\Common\Persistence\ObjectManager;

class BreedValueUtil
{

    /**
     * @param float $weightOnThatMoment
     * @param int $ageInDays
     * @param int|string $ageNullFiller
     * @param int|string $growthNullFiller
     * @param int|string $weightNullFiller
     * @param string $decimalSymbol
     * @return float
     */
    public static function getGrowthValue($weightOnThatMoment, $ageInDays,
                                          $ageNullFiller = BreedFormat::DEFAULT_AGE_NULL_FILLER,
                                          $growthNullFiller = BreedFormat::DEFAULT_GROWTH_NULL_FILLER,
                                          $weightNullFiller = BreedFormat::DEFAULT_WEIGHT_NULL_FILLER,
                                          $decimalSymbol = BreedFormat::DEFAULT_DECIMAL_SYMBOL)
    {
        if($weightOnThatMoment == null || $weightOnThatMoment == 0 || $weightOnThatMoment == $weightNullFiller
            || $ageInDays == null || $ageInDays == 0 || $ageInDays == $ageNullFiller) {
            return $growthNullFiller;
        } else {
            return number_format($weightOnThatMoment / $ageInDays, 5, $decimalSymbol, '');
        }
    }


    /**
     * @param float $breedValueReliability
     * @param bool $isFormatted
     * @return float
     */
    public static function getAccuracyFromReliability($breedValueReliability, $isFormatted = false)
    {
        $breedValueAccuracy = sqrt($breedValueReliability);

        if($isFormatted) {
            return BreedFormat::formatAccuracyForDisplay($breedValueAccuracy);
        }
        return $breedValueAccuracy;
    }


    /**
     * @param array $correctedBreedValues
     * @param array $lambMeatIndexCoefficients
     * @return float
     */
    public static function getLambMeatIndex($correctedBreedValues, $lambMeatIndexCoefficients)
    {
        $muscleThicknessAccuracy = sqrt(Utils::getNullCheckedArrayValue(BreedValueLabel::MUSCLE_THICKNESS_RELIABILITY, $correctedBreedValues));
        $fatAccuracy = sqrt(Utils::getNullCheckedArrayValue(BreedValueLabel::FAT_RELIABILITY, $correctedBreedValues));
        $growthAccuracy = sqrt(Utils::getNullCheckedArrayValue(BreedValueLabel::GROWTH_RELIABILITY, $correctedBreedValues));

        //Only calculate the LambMeatIndex if all values are not null
        if(self::areLambMeatIndexInputAccuraciesIncorrect($muscleThicknessAccuracy, $growthAccuracy, $fatAccuracy, $lambMeatIndexCoefficients)) {
            return null;
        }

        $muscleThicknessCorrectedBreedValue = Utils::getNullCheckedArrayValue(BreedValueLabel::MUSCLE_THICKNESS, $correctedBreedValues);
        $growthCorrectedBreedValueInKgPerDay = Utils::getNullCheckedArrayValue(BreedValueLabel::GROWTH, $correctedBreedValues);
        $fatCorrectedBreedValue = Utils::getNullCheckedArrayValue(BreedValueLabel::FAT, $correctedBreedValues);

        $muscleThicknessCoefficient = Utils::getNullCheckedArrayValue(BreedValueLabel::MUSCLE_THICKNESS, $lambMeatIndexCoefficients);
        $growthCoefficient = Utils::getNullCheckedArrayValue(BreedValueLabel::GROWTH, $lambMeatIndexCoefficients);
        $fatCoefficient = Utils::getNullCheckedArrayValue(BreedValueLabel::FAT, $lambMeatIndexCoefficients);

        return $muscleThicknessCoefficient * $muscleThicknessCorrectedBreedValue
             + $growthCoefficient * $growthCorrectedBreedValueInKgPerDay
             + $fatCoefficient * $fatCorrectedBreedValue;
    }


    /**
     * @param array $correctedBreedValues
     * @param array $lambMeatIndexAccuracyCoefficients
     * @return float|null
     */
    public static function getLambMeatIndexReliability($correctedBreedValues, $lambMeatIndexAccuracyCoefficients)
    {
        $muscleThicknessReliability = Utils::getNullCheckedArrayValue(BreedValueLabel::MUSCLE_THICKNESS_RELIABILITY, $correctedBreedValues);
        $fatReliability = Utils::getNullCheckedArrayValue(BreedValueLabel::FAT_RELIABILITY, $correctedBreedValues);
        $growthReliability = Utils::getNullCheckedArrayValue(BreedValueLabel::GROWTH_RELIABILITY, $correctedBreedValues);

        //Only calculate the LambMeatIndex if all values are not null
        if(self::areLambMeatIndexInputReliabilitiesIncorrect($muscleThicknessReliability, $growthReliability, $fatReliability, $lambMeatIndexAccuracyCoefficients)) {
            return null;
        }

        $muscleThicknessAccuracyCoefficient = Utils::getNullCheckedArrayValue(BreedValueLabel::MUSCLE_THICKNESS, $lambMeatIndexAccuracyCoefficients);
        $growthAccuracyCoefficient = Utils::getNullCheckedArrayValue(BreedValueLabel::GROWTH, $lambMeatIndexAccuracyCoefficients);
        $fatAccuracyCoefficient = Utils::getNullCheckedArrayValue(BreedValueLabel::FAT, $lambMeatIndexAccuracyCoefficients);

        return 1.0 - ((
            $muscleThicknessAccuracyCoefficient * (1-$muscleThicknessReliability)
            + $growthAccuracyCoefficient * (1-$growthReliability)
            + $fatAccuracyCoefficient * (1-$fatReliability)
        ) / ($muscleThicknessAccuracyCoefficient + $growthAccuracyCoefficient + $fatAccuracyCoefficient));
    }


    /**
     * @param array $breedValues
     * @param array $lambMeatIndexCoefficients
     * @return float|null
     */
    public static function getLambMeatIndexAccuracy($breedValues, $lambMeatIndexCoefficients)
    {
        $lambMeatIndexReliability = self::getLambMeatIndexReliability($breedValues, $lambMeatIndexCoefficients);
        if($lambMeatIndexReliability == null) { return null; }

        return sqrt($lambMeatIndexReliability);
    }


    /**
     * @param float $muscleThicknessAccuracy
     * @param float $growthAccuracy
     * @param float $fatAccuracy
     * @param $lambMeatIndexCoefficients
     * @return bool
     */
    private static function areLambMeatIndexInputAccuraciesIncorrect($muscleThicknessAccuracy, $growthAccuracy, $fatAccuracy, $lambMeatIndexCoefficients)
    {
        $allValuesAreNotNull = $muscleThicknessAccuracy != null && $fatAccuracy != null && $growthAccuracy != null && $lambMeatIndexCoefficients != null;
        
        //First do a null check
        if($allValuesAreNotNull) {
            return !($muscleThicknessAccuracy >= BreedFormat::MIN_BREED_VALUE_ACCURACIES_FOR_LAMB_MEAT_INDEX
                && $fatAccuracy  >= BreedFormat::MIN_BREED_VALUE_ACCURACIES_FOR_LAMB_MEAT_INDEX
                && $growthAccuracy >= BreedFormat::MIN_BREED_VALUE_ACCURACIES_FOR_LAMB_MEAT_INDEX);
        } else {
            return true;
        }
    }


    /**
     * @param float $muscleThicknessReliability
     * @param float $growthReliability
     * @param float $fatReliability
     * @param $lambMeatIndexCoefficients
     * @return bool
     */
    private static function areLambMeatIndexInputReliabilitiesIncorrect($muscleThicknessReliability, $growthReliability, $fatReliability, $lambMeatIndexCoefficients)
    {
        if($muscleThicknessReliability == null || $growthReliability == null || $fatReliability == null || $lambMeatIndexCoefficients == null) {
            return true;
        } else {
            return self::areLambMeatIndexInputAccuraciesIncorrect(pow($muscleThicknessReliability, 2),
                pow($growthReliability, 2),
                pow($fatReliability, 2),
                $lambMeatIndexCoefficients);
        }
    }


    /**
     * @param float $lambMeatIndex
     * @param float $lambMeatIndexGeneticVariance
     * @param int $decimals
     * @return float
     */
    public static function calculateLambMeatIndexAccuracyCoefficient($lambMeatIndex, $lambMeatIndexGeneticVariance, $decimals = BreedFormat::DEFAULT_LAMB_MEAT_INDEX_ACCURACY_DECIMALS)
    {
        return round((pow($lambMeatIndex, 2)) * $lambMeatIndexGeneticVariance, $decimals);
    }


    /**
     * @param ObjectManager $em
     * @param $generationDate
     */
    public static function persistLambMeatIndexMeanAndStandardDeviation(ObjectManager $em, $generationDate)
    {
        /** @var NormalDistributionRepository $normalDistributionRepository */
        $normalDistributionRepository = $em->getRepository(NormalDistribution::class);

        /** @var LambMeatBreedIndexRepository $lambMeatBreedIndexRepository */
        $lambMeatBreedIndexRepository = $em->getRepository(LambMeatBreedIndex::class);

        $year = TimeUtil::getYearFromDateTimeString($generationDate);
        $type = BreedValueCoefficientType::LAMB_MEAT_INDEX;

        foreach ([true, false] as $isIncludingOnlyAliveAnimals) {
            
            $lambMeatIndexValues = $lambMeatBreedIndexRepository->getLambMeatIndexValues($generationDate, $isIncludingOnlyAliveAnimals); //TODO function still needs to be implemented

            $mean = array_sum($lambMeatIndexValues) / count($lambMeatIndexValues);
            $standardDeviation = MathUtil::standardDeviation($lambMeatIndexValues, $mean);

            $normalDistribution = $normalDistributionRepository->findOneBy(['year' => $year, 'type' => $type, 'isIncludingOnlyAliveAnimals' => $isIncludingOnlyAliveAnimals]);

            if($normalDistribution instanceof NormalDistribution) {
                /** @var NormalDistribution $normalDistribution */

                //Update values if necessary
                if(!NumberUtil::areFloatsEqual($normalDistribution->getMean(), $mean) || !NumberUtil::areFloatsEqual($normalDistribution->getStandardDeviation(), $standardDeviation)) {
                    $normalDistribution->setMean($mean);
                    $normalDistribution->setStandardDeviation($standardDeviation);
                    $normalDistribution->setLogDate(new \DateTime());

                    $em->persist($normalDistribution);
                    $em->flush();
                }
            } else {
                //Create a new entry
                $normalDistributionRepository->persistFromValues($type, $year, $mean, $standardDeviation, $isIncludingOnlyAliveAnimals);
            }
        }
    }
}