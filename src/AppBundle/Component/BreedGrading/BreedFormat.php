<?php


namespace AppBundle\Component\BreedGrading;


use AppBundle\Constant\BreedIndexDiscriminatorTypeConstant;
use AppBundle\Constant\BreedValueTypeConstant;
use AppBundle\Constant\ReportFormat;
use AppBundle\Entity\NormalDistribution;
use AppBundle\Util\NumberUtil;

class BreedFormat
{
    const DEFAULT_AGE_NULL_FILLER = '-';
    const DEFAULT_GROWTH_NULL_FILLER = '-';
    const DEFAULT_WEIGHT_NULL_FILLER = '-';
    const DEFAULT_DECIMAL_SYMBOL = '.';

    //Display
    const EMPTY_INDEX_VALUE = '-';
    const EMPTY_INDEX_ACCURACY = '-';
    const EMPTY_BREED_VALUE = '-/-';
    const EMPTY_BREED_SINGLE_VALUE = '-';
    const INDEX_DECIMAL_ACCURACY = 0;

    //Scaling
    const LAMB_MEAT_INDEX_SCALE = 100; //This will be added to the lambMeatIndex value
    const INDEX_SCALE = 100; //This will be added to the default Index value

    const SIGA_BREED_VALUE_SCALE = 100; //This will be added to the corrected SiGA breed value
    const SIGA_STANDARD_DEVIATION_SCALE = 10; //1 Standard deviation is worth this much points

    //Minimum accuracies for the calculation
    const MIN_BREED_VALUE_ACCURACIES_FOR_LAMB_MEAT_INDEX = 0.40;

    //If the following accuracy are lower, they are ignored in the PedigreeCertificate
    const MIN_INDEX_ACCURACY = 0.30;
    const MIN_BREED_VALUE_ACCURACY_PEDIGREE_REPORT = 0.30; //Valid Growth, MuscleThickness and Fat BreedValues should at least have this accuracy

    const DEFAULT_LAMB_MEAT_INDEX_ACCURACY_DECIMALS = 7;

    const DEFAULT_DECIMAL_ACCURACY = 2;
    const FAT_DECIMAL_ACCURACY = 2;
    const MUSCLE_THICKNESS_DECIMAL_ACCURACY = 2;
    const GROWTH_DECIMAL_ACCURACY = 1;

    /**
     * @param float $index
     * @param float $accuracy
     * @return string
     */
    public static function getJoinedIndex($index, $accuracy)
    {
        return self::getFormattedIndexValue($index, $accuracy) . '/' . self::getFormattedIndexAccuracy($index, $accuracy);
    }


    /**
     * @param float $value
     * @param float $accuracy
     * @return bool
     */
    public static function isIndexEmpty($value, $accuracy): bool
    {
        return empty($value) || empty($accuracy) || $accuracy < self::MIN_INDEX_ACCURACY;
    }


    /**
     * @param float $value
     * @param float $accuracy
     * @return string
     */
    public static function getFormattedIndexValue($value, $accuracy): string
    {
        if (self::isIndexEmpty($value, $accuracy)) {
            return BreedFormat::EMPTY_INDEX_VALUE;
        }
        $scaledIndex = $value + self::INDEX_SCALE;
        return number_format($scaledIndex, BreedFormat::INDEX_DECIMAL_ACCURACY, ReportFormat::DECIMAL_CHAR, ReportFormat::THOUSANDS_SEP_CHAR);
    }


    /**
     * @param float $value
     * @param float $accuracy
     * @return string
     */
    public static function getFormattedIndexAccuracy($value, $accuracy): string
    {
        if (self::isIndexEmpty($value, $accuracy)) {
            return BreedFormat::EMPTY_INDEX_ACCURACY;
        }
        return round($accuracy*100);
    }


    /**
     * @param $correctedValue
     * @param $accuracy
     * @return string
     */
    public static function formatMuscleThicknessBreedValue($correctedValue, $accuracy)
    {
        return self::formatBreedValue($correctedValue, $accuracy, BreedValueTypeConstant::MUSCLE_THICKNESS);
    }


    /**
     * @param $correctedValue
     * @param $accuracy
     * @return string
     */
    public static function formatFatThickness3BreedValue($correctedValue, $accuracy)
    {
        return self::formatBreedValue($correctedValue, $accuracy, BreedValueTypeConstant::FAT_THICKNESS_3);
    }


    /**
     * @param $correctedValue
     * @param $accuracy
     * @return string
     */
    public static function formatGrowthBreedValue($correctedValue, $accuracy)
    {
        return self::formatBreedValue($correctedValue, $accuracy, BreedValueTypeConstant::GROWTH);
    }



    /**
     * @param $correctedValue
     * @param $accuracy
     * @param null $breedValueType
     * @return string
     */
    public static function formatBreedValue($correctedValue, $accuracy, $breedValueType = null)
    {
        if($accuracy == null || $correctedValue == null || $accuracy < BreedFormat::MIN_BREED_VALUE_ACCURACY_PEDIGREE_REPORT)
        { return BreedFormat::EMPTY_BREED_VALUE; }

        $breedValue = BreedFormat::formatBreedValueValue($correctedValue, $breedValueType);
        $accuracy = BreedFormat::formatAccuracyForDisplay($accuracy);
        return NumberUtil::getPlusSignIfNumberIsPositive($correctedValue).$breedValue.'/'.$accuracy;
    }


    /**
     * @param $correctedValue
     * @param $breedValueType
     * @return float
     */
    public static function formatBreedValueValue($correctedValue, $breedValueType = null)
    {
        switch ($breedValueType) {
            case BreedValueTypeConstant::MUSCLE_THICKNESS:
                $factor = ReportFormat::MUSCLE_THICKNESS_DISPLAY_FACTOR;
                $decimalAccuracy = BreedFormat::MUSCLE_THICKNESS_DECIMAL_ACCURACY;
                break;
            case BreedValueTypeConstant::FAT_THICKNESS_3:
                $factor = ReportFormat::FAT_DISPLAY_FACTOR;
                $decimalAccuracy = BreedFormat::FAT_DECIMAL_ACCURACY;
                break;
            case BreedValueTypeConstant::GROWTH:
                $factor = ReportFormat::GROWTH_DISPLAY_FACTOR;
                $decimalAccuracy = BreedFormat::GROWTH_DECIMAL_ACCURACY;
                break;
            default:
                $factor = ReportFormat::DEFAULT_DISPLAY_FACTOR;
                $decimalAccuracy = BreedFormat::DEFAULT_DECIMAL_ACCURACY;
                break;
        }

        return number_format($correctedValue*$factor, $decimalAccuracy, ReportFormat::DECIMAL_CHAR, ReportFormat::THOUSANDS_SEP_CHAR);
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
     * @param float $correctedBreedValue
     * @param float $accuracy
     * @param NormalDistribution $normalDistribution
     * @return string
     */
    public static function formatSiGAForDisplay($correctedBreedValue, $accuracy, NormalDistribution $normalDistribution)
    {
        if ($correctedBreedValue != null) {
            return self::EMPTY_BREED_VALUE;
        }

        return self::formatSiGAValueForDisplay($correctedBreedValue, $normalDistribution)
            .'/'.self::formatSiGAAccuracyForDisplay($accuracy);
    }


    /**
     * @param float $correctedBreedValue
     * @param NormalDistribution $normalDistribution
     * @return string
     */
    public static function formatSiGAValueForDisplay($correctedBreedValue, NormalDistribution $normalDistribution)
    {
        $deviation = $normalDistribution->getMean() - $correctedBreedValue;
        $standardDeviation = $normalDistribution->getStandardDeviation();

        // TODO fix this
        $diff = $deviation * (self::SIGA_STANDARD_DEVIATION_SCALE / $standardDeviation);

        $scaledSiGAValue = self::SIGA_BREED_VALUE_SCALE + $diff; // TODO fix this
        return number_format($scaledSiGAValue, BreedFormat::INDEX_DECIMAL_ACCURACY, ReportFormat::DECIMAL_CHAR, ReportFormat::THOUSANDS_SEP_CHAR);
    }


    /**
     * @param float $accuracy
     * @return float
     */
    public static function formatSiGAAccuracyForDisplay($accuracy)
    {
        return round($accuracy*100);
    }
}