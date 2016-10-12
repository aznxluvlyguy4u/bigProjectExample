<?php

namespace AppBundle\Util;


use AppBundle\Component\Utils;
use AppBundle\Constant\BreedValueLabel;
use AppBundle\Constant\ReportFormat;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\BreedValueCoefficient;
use AppBundle\Entity\BreedValueCoefficientRepository;
use AppBundle\Entity\GeneticBase;
use AppBundle\Enumerator\BreedCodeType;
use AppBundle\Report\PedigreeCertificate;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

class BreedValueUtil
{
    const DEFAULT_AGE_NULL_FILLER = '-';
    const DEFAULT_GROWTH_NULL_FILLER = '-';
    const DEFAULT_WEIGHT_NULL_FILLER = '-';
    const MOTHER = 'mother';
    const FATHER = 'father';
    const HETEROSIS = 'heterosis';
    const RECOMBINATION = 'recombination';
    const EIGHT_PART_DENOMINATOR = 64;
    const HUNDRED_PART_DENOMINATOR = 10000;
    const IS_IGNORE_INCOMPLETE_CODES = true;
    const DEFAULT_DECIMAL_SYMBOL = '.';

    //Scaling
    const LAMB_MEAT_INDEX_SCALE = 100; //This will be added to the lambMeatIndex value

    //Minimum accuracies
    const MIN_BREED_VALUE_ACCURACIES_FOR_LAMB_MEAT_INDEX = 0.40;
    const MIN_LAMB_MEAT_INDEX_ACCURACY = 0.0;

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
     * If the breedCodes are by parts of 8, the denominator should be 64.
     * If the breedCodes are by parts of 100, the denominator should be 10.000.
     *
     * @param ObjectManager $em
     * @param int $animalId
     * @param int $roundingAccuracy
     * @return float
     */
    public static function getHeterosisAndRecombinationBy8Parts(ObjectManager $em, $animalId, $roundingAccuracy = null)
    {
        $parentBreedCodes = self::getBreedCodesValuesOfParents($em, $animalId);

        //Null checks, null checks everywhere
        if($parentBreedCodes == null) { return null; }

        $valuesFather = Utils::getNullCheckedArrayValue(self::FATHER, $parentBreedCodes);
        $valuesMother = Utils::getNullCheckedArrayValue(self::MOTHER, $parentBreedCodes);

        if($valuesFather == null || $valuesMother == null) { return null; }
        if(count($valuesFather) == 0 || count($valuesMother) == 0 ) { return null; }

        $codesFather = array_keys($valuesFather);
        $codesMother = array_keys($valuesMother);


        if(self::IS_IGNORE_INCOMPLETE_CODES) {
            $hasIncompleteCodes = self::hasUnknownBreedCodes($valuesMother)
                               || self::hasUnknownBreedCodes($valuesFather);
            if($hasIncompleteCodes) {
                return null;
            }
        }


        /* Calculate values */
        $heterosisSum = 0.0;
        $recombinationSum = 0.0;

        //Calculate heterosis
        foreach ($codesMother as $codeMother) {
            foreach ($codesFather as $codeFather) {
                if($codeFather != $codeMother) {
                    $heterosisSum += $valuesMother[$codeMother] * $valuesFather[$codeFather];
                }
            }
        }

        //Calculate recombination
        $recombinationSum += Utils::getPermutationProduct($valuesFather);
        $recombinationSum += Utils::getPermutationProduct($valuesMother);

        $heterosisValue = (float)$heterosisSum/self::EIGHT_PART_DENOMINATOR;
        $recombinationValue = (float)$recombinationSum/self::EIGHT_PART_DENOMINATOR;
        
        if($roundingAccuracy != null) {
            $heterosisValue = round($heterosisValue, $roundingAccuracy);
            $recombinationValue = round($recombinationValue, $roundingAccuracy);
        }

        return [
            self::HETEROSIS => $heterosisValue,
            self::RECOMBINATION => $recombinationValue
        ];
    }


    /**
     * @param ObjectManager $em
     * @param int $animalId
     * @return array|null
     */
    private static function getBreedCodesValuesOfParents(ObjectManager $em, $animalId)
    {
        $sql = "SELECT parent_father_id as father, parent_mother_id as mother FROM animal WHERE id = '".$animalId."'";
        $result = $em->getConnection()->query($sql)->fetch();
        $motherId = $result[self::MOTHER];
        $fatherId = $result[self::FATHER];

        //Null check parents
        if($motherId == null || $fatherId == null) {
            return null;
        }

        $valuesMother = self::getBreedCodes($em, $motherId);
        $valuesFather = self::getBreedCodes($em, $fatherId);

        //Null check breedCodes
        if(count($valuesMother) == null || count($valuesFather) == null) {
            return null;
        }

        return [self::MOTHER => $valuesMother, self::FATHER => $valuesFather];
    }


    /**
     * @param ObjectManager $em
     * @param $animalId
     * @return array
     */
    public static function getBreedCodes(ObjectManager $em, $animalId)
    {
        $codes = array();

        $sql = "SELECT breed_code.name as name, breed_code.value as value FROM breed_code INNER JOIN breed_codes ON breed_code.breed_codes_id = breed_codes.id WHERE breed_codes.animal_id = '".$animalId."'";
        $results = $em->getConnection()->query($sql)->fetchAll();

        foreach ($results as $result) {
            $codes[$result['name']] = $result['value'];
        }

        return $codes;
    }


    /**
     * @param array $breedCodeArray
     * @return bool
     */
    public static function hasUnknownBreedCodes($breedCodeArray)
    {
        $unknownCodeExists = array_key_exists(BreedCodeType::NN, $breedCodeArray) || array_key_exists(BreedCodeType::OV, $breedCodeArray);
        if($unknownCodeExists) {
            return true;
        } else {
            $sum = 0;
            foreach ($breedCodeArray as $item) {
                $sum += $item;
            }
            $isValuesIncomplete = $sum < 8;
            if($isValuesIncomplete) {
                return true;
            }
        }

        return false;
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
                if($rawBreedValue == null) {
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
            return NumberUtil::getPlusSignIfNumberIsPositive($scaledLambMeatIndex).round($scaledLambMeatIndex, PedigreeCertificate::LAMB_MEAT_INDEX_DECIMAL_ACCURACY).'/'.round($lambMeatIndexAccuracy*100);
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
        $muscleThicknessAccuracy = Utils::getNullCheckedArrayValue(BreedValueLabel::MUSCLE_THICKNESS_ACCURACY, $correctedBreedValues);
        $fatAccuracy = Utils::getNullCheckedArrayValue(BreedValueLabel::FAT_ACCURACY, $correctedBreedValues);
        $growthAccuracy = Utils::getNullCheckedArrayValue(BreedValueLabel::GROWTH_ACCURACY, $correctedBreedValues);

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
     */
    public static function generateLamMeatIndexRanks(ObjectManager $em, $generationDate, $cmdUtil = null)
    {
        $sql = "SELECT * FROM breed_values_set WHERE lamb_meat_index_accuracy > 0 AND generation_date = '".$generationDate."' ORDER BY lamb_meat_index DESC";
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
        $allValuesAreNotNull = NumberUtil::isFloatZero($muscleThicknessAccuracy) || NumberUtil::isFloatZero($fatAccuracy) || NumberUtil::isFloatZero($growthAccuracy) || $lambMeatIndexCoefficients == null;
        
        //First do a null check
        if($allValuesAreNotNull) {
            return $muscleThicknessAccuracy >= self::MIN_BREED_VALUE_ACCURACIES_FOR_LAMB_MEAT_INDEX
                && $fatAccuracy  >= self::MIN_BREED_VALUE_ACCURACIES_FOR_LAMB_MEAT_INDEX
                && $lambMeatIndexCoefficients >= self::MIN_BREED_VALUE_ACCURACIES_FOR_LAMB_MEAT_INDEX;
        } else {
            return false;
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
         return self::areLambMeatIndexInputAccuraciesIncorrect($muscleThicknessReliability ** 2,
                                                        $growthReliability ** 2,
                                                        $fatReliability ** 2,
                                                        $lambMeatIndexCoefficients);
    }

    /**
     * @param int $indexRank
     * @param int $totalIndexRankedAnimals
     * @return float|int
     */
    public static function getStarValue($indexRank, $totalIndexRankedAnimals)
    {
        if(NullChecker::numberIsNull($indexRank) || NullChecker::numberIsNull($totalIndexRankedAnimals)) {
            return 0;
        }

        $rankPercentage = floor((floatval($totalIndexRankedAnimals) - floatval($indexRank))/floatval($totalIndexRankedAnimals) * 100);

        if($rankPercentage >= ReportFormat::STAR_SCORE_5_MIN_PERCENTAGE) {
            return 5;

        } elseif($rankPercentage >= ReportFormat::STAR_SCORE_4_AND_HALF_MIN_PERCENTAGE) {
            return 4.5;

        } elseif($rankPercentage >= ReportFormat::STAR_SCORE_4_MIN_PERCENTAGE) {
            return 4;

        } elseif($rankPercentage >= ReportFormat::STAR_SCORE_3_AND_HALF_MIN_PERCENTAGE) {
            return 3.5;

        } elseif($rankPercentage >= ReportFormat::STAR_SCORE_3_MIN_PERCENTAGE) {
            return 3;

        } elseif($rankPercentage >= ReportFormat::STAR_SCORE_2_AND_HALF_MIN_PERCENTAGE) {
            return 2.5;

        } elseif($rankPercentage >= ReportFormat::STAR_SCORE_2_MIN_PERCENTAGE) {
            return 2;

        } elseif($rankPercentage >= ReportFormat::STAR_SCORE_1_MIN_PERCENTAGE) {
            return 1;
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
        return round(($lambMeatIndex ** 2) * $lambMeatIndexGeneticVariance, $decimals);
    }
}