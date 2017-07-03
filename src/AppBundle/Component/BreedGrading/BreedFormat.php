<?php


namespace AppBundle\Component\BreedGrading;


use AppBundle\Constant\BreedIndexDiscriminatorTypeConstant;
use AppBundle\Constant\BreedValueTypeConstant;
use AppBundle\Constant\ReportFormat;
use AppBundle\Util\NumberUtil;

class BreedFormat
{
    const DEFAULT_AGE_NULL_FILLER = '-';
    const DEFAULT_GROWTH_NULL_FILLER = '-';
    const DEFAULT_WEIGHT_NULL_FILLER = '-';
    const DEFAULT_DECIMAL_SYMBOL = '.';

    //Display
    const EMPTY_BREED_VALUE = '-/-';
    const EMPTY_INDEX_VALUE = '-/-';
    const INDEX_DECIMAL_ACCURACY = 0;

    //Scaling
    const LAMB_MEAT_INDEX_SCALE = 100; //This will be added to the lambMeatIndex value
    const INDEX_SCALE = 100; //This will be added to the default Index value

    //Minimum accuracies for the calculation
    const MIN_BREED_VALUE_ACCURACIES_FOR_LAMB_MEAT_INDEX = 0.40;

    //If the following accuracy are lower, they are ignored in the PedigreeCertificate
    const MIN_INDEX_ACCURACY = 0.30;
    const MIN_LAMB_MEAT_INDEX_ACCURACY = 0.30;
    const MIN_BREED_VALUE_ACCURACY_PEDIGREE_REPORT = 0.30; //Valid Growth, MuscleThickness and Fat BreedValues should at least have this accuracy

    const DEFAULT_LAMB_MEAT_INDEX_ACCURACY_DECIMALS = 7;

    const DEFAULT_DECIMAL_ACCURACY = 2;
    const FAT_DECIMAL_ACCURACY = 2;
    const MUSCLE_THICKNESS_DECIMAL_ACCURACY = 2;
    const GROWTH_DECIMAL_ACCURACY = 1;


    /**
     * @param $index
     * @param $accuracy
     * @return string
     */
    public static function getJoinedLambMeatIndex($index, $accuracy)
    {
        return self::getJoinedIndex($index, $accuracy, BreedIndexDiscriminatorTypeConstant::LAMB_MEAT);
    }


    /**
     * @param $index
     * @param $accuracy
     * @return string
     */
    public static function getJoinedFertilityIndex($index, $accuracy)
    {
        return self::getJoinedIndex($index, $accuracy, BreedIndexDiscriminatorTypeConstant::FERTILITY);
    }


    /**
     * @param $index
     * @param $accuracy
     * @return string
     */
    public static function getJoinedExteriorIndex($index, $accuracy)
    {
        return self::getJoinedIndex($index, $accuracy, BreedIndexDiscriminatorTypeConstant::EXTERIOR);
    }


    /**
     * @param $index
     * @param $accuracy
     * @return string
     */
    public static function getJoinedWormResistanceIndex($index, $accuracy)
    {
        return self::getJoinedIndex($index, $accuracy, BreedIndexDiscriminatorTypeConstant::WORM_RESISTANCE);
    }


    /**
     * @param $index
     * @param $accuracy
     * @param $type
     * @param string $nullString
     * @return string
     */
    private static function getJoinedIndex($index, $accuracy, $type, $nullString = BreedFormat::EMPTY_INDEX_VALUE)
    {

        //1. Null filter
        if($index == null || $accuracy == null || NumberUtil::isFloatZero($accuracy)) {
            return $nullString;
        }

        //2. Value filters per type
        switch ($type) {
            case BreedIndexDiscriminatorTypeConstant::EXTERIOR:
                //TODO
                break;

            case BreedIndexDiscriminatorTypeConstant::FERTILITY:
                //TODO
                break;

            case BreedIndexDiscriminatorTypeConstant::LAMB_MEAT:
                return self::getJoinedIndexBase($index, $accuracy, BreedFormat::MIN_LAMB_MEAT_INDEX_ACCURACY,
                    BreedFormat::LAMB_MEAT_INDEX_SCALE, $nullString);

            case BreedIndexDiscriminatorTypeConstant::WORM_RESISTANCE:
                //TODO
                break;

            default:
                return self::getJoinedIndexBase($index, $accuracy, BreedFormat::MIN_INDEX_ACCURACY,
                    BreedFormat::INDEX_SCALE, $nullString);
        }

        return $nullString;
    }


    /**
     * @param $index
     * @param $accuracy
     * @param $minAccuracy
     * @param $scale
     * @param string $nullString
     * @return string
     */
    private static function getJoinedIndexBase($index, $accuracy, $minAccuracy, $scale, $nullString = BreedFormat::EMPTY_INDEX_VALUE)
    {
        if($accuracy < $minAccuracy) { return $nullString; }

        $scaledLambMeatIndex = $index + $scale;
        return number_format($scaledLambMeatIndex, BreedFormat::INDEX_DECIMAL_ACCURACY, ReportFormat::DECIMAL_CHAR, ReportFormat::THOUSANDS_SEP_CHAR).'/'.round($accuracy*100);
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
    private static function formatBreedValueValue($correctedValue, $breedValueType = null)
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


}