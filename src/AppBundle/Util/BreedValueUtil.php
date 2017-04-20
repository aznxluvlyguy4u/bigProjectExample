<?php

namespace AppBundle\Util;


use AppBundle\Component\Utils;
use AppBundle\Constant\BreedValueLabel;
use AppBundle\Constant\ReportFormat;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\BreedValueCoefficient;
use AppBundle\Entity\BreedValueCoefficientRepository;
use AppBundle\Entity\BreedValuesSet;
use AppBundle\Entity\BreedValuesSetRepository;
use AppBundle\Entity\GeneticBase;
use AppBundle\Entity\NormalDistribution;
use AppBundle\Entity\NormalDistributionRepository;
use AppBundle\Enumerator\BreedCodeType;
use AppBundle\Enumerator\BreedValueCoefficientType;
use AppBundle\Report\PedigreeCertificate;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

class BreedValueUtil
{
    const DEFAULT_AGE_NULL_FILLER = '-';
    const DEFAULT_GROWTH_NULL_FILLER = '-';
    const DEFAULT_WEIGHT_NULL_FILLER = '-';
    const DEFAULT_DECIMAL_SYMBOL = '.';

    //Scaling
    const LAMB_MEAT_INDEX_SCALE = 100; //This will be added to the lambMeatIndex value

    //Minimum accuracies for the calculation
    const MIN_BREED_VALUE_ACCURACIES_FOR_LAMB_MEAT_INDEX = 0.40;

    //If the following accuracy are lower, they are ignored in the PedigreeCertificate
    const MIN_LAMB_MEAT_INDEX_ACCURACY = 0.30;
    const MIN_BREED_VALUE_ACCURACY_PEDIGREE_REPORT = 0.30; //Valid Growth, MuscleThickness and Fat BreedValues should at least have this accuracy

    const DEFAULT_LAMB_MEAT_INDEX_ACCURACY_DECIMALS = 7;


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
                                          $ageNullFiller = self::DEFAULT_AGE_NULL_FILLER,
                                          $growthNullFiller = self::DEFAULT_GROWTH_NULL_FILLER,
                                          $weightNullFiller = self::DEFAULT_WEIGHT_NULL_FILLER,
                                          $decimalSymbol = self::DEFAULT_DECIMAL_SYMBOL)
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
            return self::formatAccuracyForDisplay($breedValueAccuracy);
        }
        return $breedValueAccuracy;
    }


    /**
     * @param float $breedValueAccuracy
     * @param bool $isInPercentages
     * @return float
     */
    public static function formatAccuracyForDisplay($breedValueAccuracy, $isInPercentages = true)
    {
        if($isInPercentages) {
            $factor = 100;
            $decimalPrecision = 0;
        } else {
            $factor = 1;
            $decimalPrecision = 2;
        }
        return  number_format($breedValueAccuracy*$factor, $decimalPrecision, ReportFormat::DECIMAL_CHAR, ReportFormat::THOUSANDS_SEP_CHAR);
    }


    /**
     * @param array $breedValues
     * @return array
     */
    public static function getFormattedBreedValues($breedValues)
    {
        $traits = new ArrayCollection();
        $traits->set(BreedValueLabel::GROWTH_ACCURACY, BreedValueLabel::GROWTH);
        $traits->set(BreedValueLabel::MUSCLE_THICKNESS_ACCURACY, BreedValueLabel::MUSCLE_THICKNESS);
        $traits->set(BreedValueLabel::FAT_ACCURACY, BreedValueLabel::FAT);
        //Add new breedValues here

        $decimalAccuracyLabels = new ArrayCollection();
        $decimalAccuracyLabels->set(BreedValueLabel::GROWTH_ACCURACY, PedigreeCertificate::GROWTH_DECIMAL_ACCURACY);
        $decimalAccuracyLabels->set(BreedValueLabel::MUSCLE_THICKNESS_ACCURACY, PedigreeCertificate::MUSCLE_THICKNESS_DECIMAL_ACCURACY);
        $decimalAccuracyLabels->set(BreedValueLabel::FAT_ACCURACY, PedigreeCertificate::FAT_DECIMAL_ACCURACY);
        //Add new decimal_accuracies here

        $factors = new ArrayCollection();
        $factors->set(BreedValueLabel::GROWTH_ACCURACY, ReportFormat::GROWTH_DISPLAY_FACTOR);
        $factors->set(BreedValueLabel::MUSCLE_THICKNESS_ACCURACY, ReportFormat::MUSCLE_THICKNESS_DISPLAY_FACTOR);
        $factors->set(BreedValueLabel::FAT_ACCURACY, ReportFormat::FAT_DISPLAY_FACTOR);
        
        $results = array();

        $accuracyLabels = $traits->getKeys();
        foreach ($accuracyLabels as $accuracyLabel) {
            $traitLabel = $traits->get($accuracyLabel);
            if($accuracyLabel == null) {
                $displayedString = PedigreeCertificate::EMPTY_BREED_VALUE;
            } else {
                $rawBreedValue = Utils::getNullCheckedArrayValue($traitLabel, $breedValues);
                if($rawBreedValue == null || $breedValues[$accuracyLabel] < self::MIN_BREED_VALUE_ACCURACY_PEDIGREE_REPORT) {
                    $displayedString = PedigreeCertificate::EMPTY_BREED_VALUE;
                } else {
                    $breedValue = round($rawBreedValue*$factors->get($accuracyLabel), $decimalAccuracyLabels->get($accuracyLabel));
                    $accuracy = BreedValueUtil::formatAccuracyForDisplay($breedValues[$accuracyLabel]);
                    $displayedString = NumberUtil::getPlusSignIfNumberIsPositive($breedValue).$breedValue.'/'.$accuracy;
                }
            }
            $results[$traitLabel] = $displayedString;
        }

        $traits = null; $decimalAccuracyLabels = null; $factors = null;
        
        return $results;
    }


    /**
     * @param $lambMeatIndex
     * @param $lambMeatIndexAccuracy
     * @param string $nullString
     * @return string
     */
    public static function getFormattedLamMeatIndexWithAccuracy($lambMeatIndex, $lambMeatIndexAccuracy, $nullString = PedigreeCertificate::EMPTY_INDEX_VALUE)
    {
        if($lambMeatIndex == null || $lambMeatIndexAccuracy == null || NumberUtil::isFloatZero($lambMeatIndexAccuracy)) {
            return $nullString;

        } elseif($lambMeatIndexAccuracy < self::MIN_LAMB_MEAT_INDEX_ACCURACY) {
            return $nullString;

        } else {
            $scaledLambMeatIndex = self::calculateScaledLamMeatIndex($lambMeatIndex);
            return round($scaledLambMeatIndex, PedigreeCertificate::LAMB_MEAT_INDEX_DECIMAL_ACCURACY).'/'.round($lambMeatIndexAccuracy*100);
        }
    }


    /**
     * @param float $lambMeatIndex
     * @return float
     */
    public static function calculateScaledLamMeatIndex($lambMeatIndex)
    {
        return $lambMeatIndex + self::LAMB_MEAT_INDEX_SCALE;
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
     * @param ObjectManager $em
     * @param string $generationDate
     * @param CommandUtil $cmdUtil
     */
    public static function generateAndPersistAllLambMeatIndicesAndTheirAccuracies(ObjectManager $em, $generationDate, $cmdUtil = null)
    {
        $year = TimeUtil::getYearFromDateTimeString($generationDate);
        /** @var GeneticBase $geneticBase */
        $geneticBase = $em->getRepository(GeneticBase::class)->findOneBy(['year' => $year]);
        
        /** @var BreedValueCoefficientRepository $breedValueCoefficientRepository */
        $breedValueCoefficientRepository = $em->getRepository(BreedValueCoefficient::class);
        
        $lambMeatIndexCoefficients = $breedValueCoefficientRepository->getLambMeatIndexCoefficients();
        $lambMeatIndexAccuracyCoefficients = $breedValueCoefficientRepository->getLambMeatIndexAccuracyCoefficients();

        $sql = "SELECT * FROM breed_values_set WHERE generation_date = '".$generationDate."'";
        $results = $em->getConnection()->query($sql)->fetchAll();
        
        if($cmdUtil != null ) { $cmdUtil->setStartTimeAndPrintIt(count($results), 1 , 'Generating lambMeatIndexValues'); }

        $countNewValues = 0;
        foreach ($results as $result) {
            $id = $result['id'];
            $muscleThickness = floatval($result['muscle_thickness']);
            $muscleThicknessReliability = floatval($result['muscle_thickness_reliability']);
            $growth = floatval($result['growth']);
            $growthReliability = floatval($result['growth_reliability']);
            $fat = floatval($result['fat']);
            $fatReliability = floatval($result['fat_reliability']);
//            $lambMeatIndex = floatval($result['lamb_meat_index']);
//            $lambMeatIndexAccuracy = floatval($result['lamb_meat_index_accuracy']);
//            $lambMeatIndex = floatval($result['lamb_meat_index_ranking']);

            if(!self::areLamMeatIndexValuesProcessedYet($result)) {

                $correctedBreedValues =
                    [
                        BreedValueLabel::MUSCLE_THICKNESS => $muscleThickness - $geneticBase->getMuscleThickness(),
                        BreedValueLabel::MUSCLE_THICKNESS_RELIABILITY => $muscleThicknessReliability,
                        BreedValueLabel::GROWTH => $growth - $geneticBase->getGrowth(),
                        BreedValueLabel::GROWTH_RELIABILITY => $growthReliability,
                        BreedValueLabel::FAT => $fat - $geneticBase->getFat(),
                        BreedValueLabel::FAT_RELIABILITY => $fatReliability,
                    ]
                ;

                $lambMeatIndex = self::getLambMeatIndex($correctedBreedValues, $lambMeatIndexCoefficients);
                if($lambMeatIndex == null) { $lambMeatIndex = 0.0; }

                $lambMeatIndexAccuracy = self::getLambMeatIndexAccuracy($correctedBreedValues, $lambMeatIndexAccuracyCoefficients);

                if(NullChecker::floatIsNotZero($lambMeatIndexAccuracy)) {
                    $sql = "UPDATE breed_values_set SET lamb_meat_index = ".$lambMeatIndex.", lamb_meat_index_accuracy = ".$lambMeatIndexAccuracy." WHERE id = ".$id;
                    $em->getConnection()->exec($sql);
                    $countNewValues++;
                }
            }
            
            if($cmdUtil != null ) { $cmdUtil->advanceProgressBar(1, 'Generating lambMeatIndexValues | records with new values: '.$countNewValues); }
        }
        if($cmdUtil != null ) { $cmdUtil->setEndTimeAndPrintFinalOverview(); }
    }


    /**
     * @param ObjectManager $em
     * @param string $generationDate
     * @param CommandUtil $cmdUtil
     * @param boolean $isIncludeOnlyAliveAnimals
     */
    public static function generateLamMeatIndexRanks(ObjectManager $em, $generationDate, $cmdUtil = null, $isIncludeOnlyAliveAnimals = true)
    {
        /** @var BreedValuesSetRepository $breedValuesSetRepository */
        $breedValuesSetRepository = $em->getRepository(BreedValuesSet::class);
        $breedValuesSetRepository->clearLambMeatIndexRanking();

        if($isIncludeOnlyAliveAnimals) {
            $sql = "SELECT b.* FROM breed_values_set b
                    INNER JOIN animal a ON a.id = b.animal_id
                    WHERE a.is_alive = TRUE
                      AND b.lamb_meat_index_accuracy > 0
                      AND b.generation_date = '".$generationDate."'
                    ORDER BY b.lamb_meat_index DESC";
        } else {
            $sql = "SELECT * FROM breed_values_set WHERE lamb_meat_index_accuracy > 0 AND generation_date = '".$generationDate."' ORDER BY lamb_meat_index DESC";
        }

        $results = $em->getConnection()->query($sql)->fetchAll();

        if($cmdUtil != null ) { $cmdUtil->setStartTimeAndPrintIt(count($results), 1 , 'Rank lambMeatIndexValues'); }

        $rank = 1;
        foreach ($results as $result) {
            $sql = "UPDATE breed_values_set SET lamb_meat_index_ranking = ".$rank." WHERE id = ".$result['id'];
            $em->getConnection()->exec($sql);

            $rank++;
            if($cmdUtil != null ) { $cmdUtil->advanceProgressBar(1); }
        }
        if($cmdUtil != null ) { $cmdUtil->setEndTimeAndPrintFinalOverview(); }
    }



    /**
     * @param array $sqlResult
     * @return bool
     */
    private static function areLamMeatIndexValuesProcessedYet($sqlResult)
    {
        $muscleThicknessReliability = $sqlResult['muscle_thickness_reliability'];
        $growthReliability = $sqlResult['growth_reliability'];
        $fatReliability = $sqlResult['fat_reliability'];
        $lambMeatIndexAccuracy = $sqlResult['lamb_meat_index_accuracy'];

        if(NumberUtil::isFloatZero($muscleThicknessReliability) ||
           NumberUtil::isFloatZero($growthReliability) ||
           NumberUtil::isFloatZero($fatReliability)) {

            return true;

        } else {
            return NullChecker::floatIsNotZero($lambMeatIndexAccuracy);
        }
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
            return !($muscleThicknessAccuracy >= self::MIN_BREED_VALUE_ACCURACIES_FOR_LAMB_MEAT_INDEX
                && $fatAccuracy  >= self::MIN_BREED_VALUE_ACCURACIES_FOR_LAMB_MEAT_INDEX
                && $growthAccuracy >= self::MIN_BREED_VALUE_ACCURACIES_FOR_LAMB_MEAT_INDEX);
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
    public static function calculateLambMeatIndexAccuracyCoefficient($lambMeatIndex, $lambMeatIndexGeneticVariance, $decimals = self::DEFAULT_LAMB_MEAT_INDEX_ACCURACY_DECIMALS)
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

        /** @var BreedValuesSetRepository $breedValuesSetRepository */
        $breedValuesSetRepository = $em->getRepository(BreedValuesSet::class);

        $year = TimeUtil::getYearFromDateTimeString($generationDate);
        $type = BreedValueCoefficientType::LAMB_MEAT_INDEX;

        foreach ([true, false] as $isIncludingOnlyAliveAnimals) {
            
            $lambMeatIndexValues = $breedValuesSetRepository->getLambMeatIndexValues($generationDate, $isIncludingOnlyAliveAnimals);

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