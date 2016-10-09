<?php

namespace AppBundle\Util;


use AppBundle\Component\Utils;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\BreedValueCoefficient;
use AppBundle\Entity\BreedValueCoefficientRepository;
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
        return  round($breedValueAccuracy*$factor, $decimalPrecision);
    }


    /**
     * @param array $breedValues
     * @return array
     */
    public static function getFormattedBreedValues($breedValues)
    {
        $traits = new ArrayCollection();
        $traits->set(ReportLabel::GROWTH_ACCURACY, ReportLabel::GROWTH);
        $traits->set(ReportLabel::MUSCLE_THICKNESS_ACCURACY, ReportLabel::MUSCLE_THICKNESS);
        $traits->set(ReportLabel::FAT_ACCURACY, ReportLabel::FAT);
        //Add new breedValues here

        $decimalAccuracyLabels = new ArrayCollection();
        $decimalAccuracyLabels->set(ReportLabel::GROWTH_ACCURACY, PedigreeCertificate::GROWTH_DECIMAL_ACCURACY);
        $decimalAccuracyLabels->set(ReportLabel::MUSCLE_THICKNESS_ACCURACY, PedigreeCertificate::MUSCLE_THICKNESS_DECIMAL_ACCURACY);
        $decimalAccuracyLabels->set(ReportLabel::FAT_ACCURACY, PedigreeCertificate::FAT_DECIMAL_ACCURACY);
        //Add new decimal_accuracies here

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
                    $breedValue = round($rawBreedValue, $decimalAccuracyLabels->get($accuracyLabel));
                    $accuracy = BreedValueUtil::formatAccuracyForDisplay($breedValues[$accuracyLabel]);
                    $displayedString = NumberUtil::getPlusSignIfNumberIsPositive($breedValue).$breedValue.'/'.$accuracy;
                }
            }
            $results[$traitLabel] = $displayedString;
        }

        return $results;
    }




    /**
     * @param array $breedValues
     * @param array $lambMeatIndexCoefficients
     * @return float
     */
    public static function getLambMeatIndex($breedValues, $lambMeatIndexCoefficients)
    {
        $muscleThicknessAccuracy = Utils::getNullCheckedArrayValue(ReportLabel::MUSCLE_THICKNESS_ACCURACY, $breedValues);
        $fatAccuracy = Utils::getNullCheckedArrayValue(ReportLabel::FAT_ACCURACY, $breedValues);
        $growthAccuracy = Utils::getNullCheckedArrayValue(ReportLabel::GROWTH_ACCURACY, $breedValues);

        //Only calculate the LambMeatIndex if all values are not null
        if(NullChecker::floatIsNotZero($muscleThicknessAccuracy) || NullChecker::floatIsNotZero($fatAccuracy) || NullChecker::floatIsNotZero($growthAccuracy) || $lambMeatIndexCoefficients == null) {
            return null;
        }

        $muscleThicknessCorrectedBreedValue = Utils::getNullCheckedArrayValue(ReportLabel::MUSCLE_THICKNESS, $breedValues);
        $growthCorrectedBreedValue = Utils::getNullCheckedArrayValue(ReportLabel::GROWTH, $breedValues);
        $fatCorrectedBreedValue = Utils::getNullCheckedArrayValue(ReportLabel::FAT, $breedValues);

        $muscleThicknessCoefficient = Utils::getNullCheckedArrayValue(ReportLabel::MUSCLE_THICKNESS, $lambMeatIndexCoefficients);
        $growthCoefficient = Utils::getNullCheckedArrayValue(ReportLabel::GROWTH, $lambMeatIndexCoefficients);
        $fatCoefficient = Utils::getNullCheckedArrayValue(ReportLabel::FAT, $lambMeatIndexCoefficients);

        return $muscleThicknessCoefficient * $muscleThicknessCorrectedBreedValue
             + $growthCoefficient * $growthCorrectedBreedValue
             + $fatCoefficient * $fatCorrectedBreedValue;
    }


    /**
     * @param array $breedValues
     * @param array $lambMeatIndexCoefficients
     * @return float|null
     */
    public static function getLambMeatIndexReliability($breedValues, $lambMeatIndexCoefficients)
    {
        $muscleThicknessReliability = Utils::getNullCheckedArrayValue(ReportLabel::MUSCLE_THICKNESS_RELIABILITY, $breedValues);
        $fatReliability = Utils::getNullCheckedArrayValue(ReportLabel::FAT_RELIABILITY, $breedValues);
        $growthReliability = Utils::getNullCheckedArrayValue(ReportLabel::GROWTH_RELIABILITY, $breedValues);

        //Only calculate the LambMeatIndex if all values are not null
        if(NumberUtil::isFloatZero($muscleThicknessReliability) || NumberUtil::isFloatZero($fatReliability) || NumberUtil::isFloatZero($growthReliability) || $lambMeatIndexCoefficients == null) {
            return null;
        }

        $muscleThicknessCoefficientSquared = Utils::getNullCheckedArrayValue(ReportLabel::MUSCLE_THICKNESS, $lambMeatIndexCoefficients) ** 2;
        $growthCoefficientSquared = Utils::getNullCheckedArrayValue(ReportLabel::GROWTH, $lambMeatIndexCoefficients) ** 2;
        $fatCoefficientSquared = Utils::getNullCheckedArrayValue(ReportLabel::FAT, $lambMeatIndexCoefficients) ** 2;

        return 1.0 - ((
            $muscleThicknessCoefficientSquared * (1-$muscleThicknessReliability)
            + $growthCoefficientSquared * (1-$growthReliability)
            + $fatCoefficientSquared * (1-$fatReliability)
        ) / ($muscleThicknessCoefficientSquared + $growthCoefficientSquared + $fatCoefficientSquared));
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
        /** @var BreedValueCoefficientRepository $breedValueCoefficientRepository */
        $breedValueCoefficientRepository = $em->getRepository(BreedValueCoefficient::class);
        
        $lambMeatIndexCoefficients = $breedValueCoefficientRepository->getLambMeatIndexCoefficients();
        
        $sql = "SELECT * FROM breed_values_set WHERE generation_date = '".$generationDate."'";
        $results = $em->getConnection()->query($sql)->fetchAll();

        if($cmdUtil != null ) { $cmdUtil->setStartTimeAndPrintIt(count($results), 1 , 'Generating lamMeatIndexValues'); }

        $countNewValues = 0;
        foreach ($results as $result) {
            $id = $result['id'];
            $muscleThickness = floatval($result['muscle_thickness']);
            $muscleThicknessReliability = floatval($result['muscle_thickness_reliability']);
            $growth = floatval($result['growth']);
            $growthReliability = floatval($result['growth_reliability']);
            $fat = floatval($result['fat']);
            $fatReliability = floatval($result['fat_reliability']);
//            $lamMeatIndex = floatval($result['lam_meat_index']);
//            $lamMeatIndexAccuracy = floatval($result['lam_meat_index_accuracy']);
//            $lamMeatIndex = floatval($result['lam_meat_index_ranking']);

            if(!self::areLamMeatIndexValuesProcessedYet($result)) {

                $breedValues =
                    [
                        ReportLabel::MUSCLE_THICKNESS => $muscleThickness,
                        ReportLabel::MUSCLE_THICKNESS_RELIABILITY => $muscleThicknessReliability,
                        ReportLabel::GROWTH => $growth,
                        ReportLabel::GROWTH_RELIABILITY => $growthReliability,
                        ReportLabel::FAT => $fat,
                        ReportLabel::FAT_RELIABILITY => $fatReliability,
                    ]
                ;

                $lamMeatIndex = self::getLambMeatIndex($breedValues, $lambMeatIndexCoefficients);
                if($lamMeatIndex == null) { $lamMeatIndex = 0.0; }

                $lamMeatIndexAccuracy = self::getLambMeatIndexAccuracy($breedValues, $lambMeatIndexCoefficients);

                if(NullChecker::floatIsNotZero($lamMeatIndexAccuracy)) {
                    $sql = "UPDATE breed_values_set SET lam_meat_index = ".$lamMeatIndex.", lam_meat_index_accuracy = ".$lamMeatIndexAccuracy." WHERE id = ".$id;
                    $em->getConnection()->exec($sql);
                    $countNewValues++;
                }
            }

            if($cmdUtil != null ) { $cmdUtil->advanceProgressBar(1, 'Generating lamMeatIndexValues | records with new values: '.$countNewValues); }
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
        $sql = "SELECT * FROM breed_values_set WHERE lam_meat_index_accuracy > 0 AND generation_date = '".$generationDate."' ORDER BY lam_meat_index DESC";
        $results = $em->getConnection()->query($sql)->fetchAll();

        if($cmdUtil != null ) { $cmdUtil->setStartTimeAndPrintIt(count($results), 1 , 'Rank lamMeatIndexValues'); }

        $rank = 1;
        foreach ($results as $result) {
            $sql = "UPDATE breed_values_set SET lam_meat_index_ranking = ".$rank." WHERE id = ".$result['id'];
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
        $lamMeatIndexAccuracy = $sqlResult['lam_meat_index_accuracy'];

        if(NumberUtil::isFloatZero($muscleThicknessReliability) ||
           NumberUtil::isFloatZero($growthReliability) ||
           NumberUtil::isFloatZero($fatReliability)) {

            return true;

        } else {
            return NullChecker::floatIsNotZero($lamMeatIndexAccuracy);
        }
    }

}