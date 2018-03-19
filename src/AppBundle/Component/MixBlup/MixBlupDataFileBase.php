<?php


namespace AppBundle\Component\MixBlup;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\MaxLength;
use AppBundle\Constant\MeasurementConstant;
use AppBundle\Enumerator\BreedCodeType;
use AppBundle\Setting\MixBlupSetting;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\BreedCodeUtil;
use AppBundle\Util\DsvWriterUtil;
use AppBundle\Util\Translation;
use Doctrine\DBAL\Connection;

/**
 * Class MixBlupDataFileBase
 * @package AppBundle\MixBlup
 */
class MixBlupDataFileBase
{
    const DECIMAL_SEPARATOR_SYMBOL = '.';
    const THOUSAND_SEPARATOR_SYMBOL = '';


    /**
     * @param $value
     * @param $decimals
     * @return string
     */
    protected static function numberFormat($value, $decimals)
    {
        return number_format(
            $value,
            $decimals,
            self::DECIMAL_SEPARATOR_SYMBOL,
            self::THOUSAND_SEPARATOR_SYMBOL
        );
    }


    /**
     * @param string $animalIdKey
     * @return string
     */
    protected static function getErrorLogAnimalPedigreeFilter($animalIdKey)
    {
        return MixBlupSetting::FILTER_OUT_ANIMALS_WHO_ARE_THEIR_OWN_ASCENDANTS ? " AND $animalIdKey NOT IN (SELECT animal_id FROM error_log_animal_pedigree) " : " ";
    }


    /**
     * @param $gender
     * @return string
     */
    public static function translateGender($gender)
    {
        return Translation::getGenderInDutch($gender);
    }


    /**
     * @param $data
     * @param string $key
     * @return string
     */
    protected static function getTranslatedGenderFromType($data, $key = JsonInputConstant::TYPE)
    {
        return self::translateGender(ArrayUtil::get($key, $data, Translation::NL_NEUTER));
    }


    /**
     * @param $translatedGender
     * @return bool
     */
    protected static function isValidTranslatedGender($translatedGender)
    {
        return $translatedGender == Translation::NL_RAM || $translatedGender == Translation::NL_EWE;
    }


    /**
     * @param array $data
     * @param string $key
     * @return bool|string
     */
    protected static function parseBreedCode($data, $key = JsonInputConstant::BREED_CODE)
    {
        $breedCode = ArrayUtil::get($key, $data);
        if($breedCode == null) { return false; }

        $breedCodeParts = BreedCodeUtil::getBreedCodeAs8PartsFromBreedCodeString($breedCode);
        $isValidBreedCode = BreedCodeUtil::verifySumOf8PartBreedCodeParts($breedCodeParts);

        if(!$isValidBreedCode) { return false; }

        return
            self::format_TE_DT_DK_breedCodePart($breedCodeParts).
            self::formatBreedCodePart(BreedCodeType::CF, $breedCodeParts).
            self::formatBreedCodePart(BreedCodeType::BM, $breedCodeParts).
            self::formatBreedCodePart(BreedCodeType::SW, $breedCodeParts).
            self::formatBreedCodePart(BreedCodeType::NH, $breedCodeParts).
            self::formatBreedCodePart(BreedCodeType::FL, $breedCodeParts).
            self::formatBreedCodePart(BreedCodeType::HD, $breedCodeParts).
            self::formatOVBreedCodeParts($breedCodeParts);
    }


    /**
     * @return string
     */
    protected static function getBlankBreedCodes()
    {
        $fullBreedCodeBy8Parts = DsvWriterUtil::pad(8, MaxLength::BREED_CODE_PART_BY_8_PARTS);
        $blankBreedCode = DsvWriterUtil::pad(0, MaxLength::BREED_CODE_PART_BY_8_PARTS);

        return
            $blankBreedCode. //TE
            $blankBreedCode. //CF
            $blankBreedCode. //BM
            $blankBreedCode. //SW
            $blankBreedCode. //NH
            $blankBreedCode. //FL
            $blankBreedCode. //HD
            $fullBreedCodeBy8Parts; //OV
    }


    /**
     * @param string $breedCodeType
     * @param array $breedCodeParts
     * @return string
     */
    protected static function formatBreedCodePart($breedCodeType, $breedCodeParts)
    {
        $breedCodeValueToWrite = ArrayUtil::get($breedCodeType, $breedCodeParts, 0);
        return DsvWriterUtil::pad($breedCodeValueToWrite, MaxLength::BREED_CODE_PART_BY_8_PARTS);
    }




    /**
     * @param $breedCodeParts
     * @return string
     */
    protected static function format_TE_DT_DK_breedCodePart($breedCodeParts)
    {
        $breedCodeValueToWrite =
            ArrayUtil::get(BreedCodeType::TE, $breedCodeParts, 0) +
            ArrayUtil::get(BreedCodeType::BT, $breedCodeParts, 0) +
            ArrayUtil::get(BreedCodeType::DK, $breedCodeParts, 0)
        ;
        return DsvWriterUtil::pad($breedCodeValueToWrite, MaxLength::BREED_CODE_PART_BY_8_PARTS);
    }


    /**
     * @param array $breedCodeParts
     * @return int
     */
    private static function formatOVBreedCodeParts($breedCodeParts)
    {
        $sumOfValues = 0;
        foreach ($breedCodeParts as $breedCodeType => $breedCodeValue)
        {
            if(
                $breedCodeType != BreedCodeType::TE &&
                $breedCodeType != BreedCodeType::BT && //treated as TE
                $breedCodeType != BreedCodeType::DK && //treated as TE
                $breedCodeType != BreedCodeType::CF &&
                $breedCodeType != BreedCodeType::BM &&
                $breedCodeType != BreedCodeType::SW &&
                $breedCodeType != BreedCodeType::NH &&
                $breedCodeType != BreedCodeType::FL &&
                $breedCodeType != BreedCodeType::HD
            ) {
                $sumOfValues += $breedCodeValue;
            }
        }
        return DsvWriterUtil::pad($sumOfValues, MaxLength::BREED_CODE_PART_BY_8_PARTS);
    }


    /**
     * @param array $data
     * @param string $key
     * @param bool $useColumnPadding
     * @param string $nullReplacement
     * @return string
     */
    protected static function getFormattedBooleanValueAsIntegerStringFromData($data, $key, $useColumnPadding = true, $nullReplacement = MixBlupInstructionFileBase::MISSING_REPLACEMENT)
    {
        $boolVal = ArrayUtil::get($key, $data, null);
        if (is_bool($boolVal)) {
            $data[$key] = $boolVal ? '1' : '0';
        }

        return DsvWriterUtil::getFormattedValueFromArray($data, 3, $key, $useColumnPadding, $nullReplacement);
    }


    /**
     * @param array $data
     * @param int $columnWidth
     * @param string $key
     * @param bool $useColumnPadding
     * @param string $nullReplacement
     * @return string
     */
    protected static function getFormattedValueFromData($data, $columnWidth, $key, $useColumnPadding = true, $nullReplacement = MixBlupInstructionFileBase::MISSING_REPLACEMENT)
    {
        return DsvWriterUtil::getFormattedValueFromArray($data, $columnWidth, $key, $useColumnPadding, $nullReplacement);
    }


    /**
     * @param $data
     * @param string $key
     * @param bool $useColumnPadding
     * @return string
     */
    protected static function getFormattedNsfoInspectorCode($data, $key = JsonInputConstant::INSPECTOR_CODE, $useColumnPadding = true)
    {
        return self::getFormattedValueFromData($data, MaxLength::NSFO_INSPECTOR, $key, $useColumnPadding);
    }


    /**
     * @param $data
     * @param string $key
     * @param bool $useColumnPadding
     * @return string
     */
    public static function getFormattedLinearNsfoInspectorCode($data, $key = JsonInputConstant::LINEAR_INSPECTOR_CODE, $useColumnPadding = true)
    {
        return self::getFormattedValueFromData($data, MaxLength::NSFO_INSPECTOR, $key, $useColumnPadding);
    }


    /**
     * Input should already be null checked
     *
     * @param $data
     * @param string $key
     * @param boolean $useColumnPadding
     * @return string
     */
    protected static function getFormattedAnimalId($data, $key = JsonInputConstant::ANIMAL_ID, $useColumnPadding = true)
    {
        return self::getFormattedValueFromData($data, MaxLength::ANIMAL_ID, $key, $useColumnPadding);
    }


    /**
     * Input should already be null checked
     * 
     * @param $data
     * @param string $key
     * @param boolean $useColumnPadding
     * @return string
     */
    protected static function getFormattedMotherId($data, $key = JsonInputConstant::MOTHER_ID, $useColumnPadding = true)
    {
        return self::getFormattedValueFromData($data, MaxLength::ANIMAL_ID, $key, $useColumnPadding, MixBlupInstructionFileBase::CONSTANT_MISSING_PARENT_REPLACEMENT);
    }


    /**
     * Input should already be null checked
     * 
     * @param $data
     * @param string $key
     * @param boolean $useColumnPadding
     * @return string
     */
    protected static function getFormattedFatherId($data, $key = JsonInputConstant::FATHER_ID, $useColumnPadding = true)
    {
        return self::getFormattedValueFromData($data, MaxLength::ANIMAL_ID, $key, $useColumnPadding);
    }
    
    
    /**
     * Input should already be null checked
     *
     * @param $data
     * @param string $key
     * @param boolean $useColumnPadding
     * @return string
     */
    protected static function getFormattedUln($data, $key = JsonInputConstant::ULN, $useColumnPadding = true)
    {
        return self::getFormattedValueFromData($data, MaxLength::ULN, $key, $useColumnPadding);
    }


    /**
     * Input should already be null checked
     *
     * @param $data
     * @param string $key
     * @param boolean $useColumnPadding
     * @return string
     */
    protected static function getFormattedUlnMother($data, $key = JsonInputConstant::ULN_MOTHER, $useColumnPadding = true)
    {
        return self::getFormattedValueFromData($data, MaxLength::ULN, $key, $useColumnPadding);
    }


    /**
     * @param array $data
     * @param int $columnWidth
     * @param string $key
     * @return string
     */
    protected static function getFormattedYearAndUbnOfBirth($data, $columnWidth, $key = JsonInputConstant::YEAR_AND_UBN_OF_BIRTH)
    {
        return self::getFormattedValueFromData($data, $columnWidth, $key, true);
    }


    /**
     * @param array $data
     * @param string $ageKey
     * @return string
     */
    protected static function getFormattedAge($data, $ageKey)
    {
        return self::getFormattedValueFromData($data, MaxLength::AGE, $ageKey, true);
    }


    /**
     * @param array $data
     * @param string $key
     * @return string
     */
    protected static function getFormattedFat($data, $key)
    {
        return self::getFormattedValueFromData($data, MaxLength::FAT, $key, true);
    }


    /**
     * @param array $data
     * @param string $key
     * @return string
     */
    protected static function getFormattedMuscleThickness($data, $key = JsonInputConstant::MUSCLE_THICKNESS)
    {
        return self::getFormattedValueFromData($data, MaxLength::MUSCLE_THICKNESS, $key, true);
    }


    /**
     * @param array $data
     * @param string $key
     * @return string
     */
    protected static function getFormattedTailLength($data, $key = JsonInputConstant::TAIL_LENGTH)
    {
        $tailLengthValue = $data[$key];
        if($tailLengthValue < MeasurementConstant::TAIL_LENGTH_MIN || MeasurementConstant::TAIL_LENGTH_MAX < $tailLengthValue) {
            $data[$key] = null;
        }

        return self::getFormattedValueFromData($data, MaxLength::TAIL_LENGTH, $key, true);
    }


    /**
     * @param array $data
     * @param string $key
     * @return string
     */
    protected static function getFormattedWeight($data, $key = JsonInputConstant::WEIGHT)
    {
        return self::getFormattedValueFromData($data, MaxLength::WEIGHT, $key, true);
    }


    /** @return string */
    protected static function getFormattedBlankAge() { return self::getFormattedAge([null], 0); }
    /** @return string */
    protected static function getFormattedBlankFat() { return self::getFormattedFat([null], 0); }
    /** @return string */
    protected static function getFormattedBlankMuscleThickness() { return self::getFormattedMuscleThickness([null], 0); }
    /** @return string */
    protected static function getFormattedBlankTailLength() { return self::getFormattedTailLength([null], 0); }
    /** @return string */
    protected static function getFormattedBlankWeight() { return self::getFormattedWeight([null], 0); }


    /**
     * Note! Gender/Type should already be filtered to only contain 'Ram' or 'Ewe'
     *
     * @param $data
     * @param string $key
     * @param bool $useColumnPadding
     * @return string
     */
    public static function getFormattedGenderFromType($data, $key = JsonInputConstant::TYPE, $useColumnPadding = true)
    {
        $gender = self::translateGender($data[$key]);
        return DsvWriterUtil::pad($gender, MaxLength::VALID_GENDER, $useColumnPadding);
    }


    /**
     * @param array $data
     * @param string $key
     * @return string
     */
    protected static function getFormattedLitterGroup($data, $key = JsonInputConstant::LITTER_GROUP)
    {
        return self::getFormattedValueFromData($data, MaxLength::LITTER_GROUP, $key, true, MixBlupInstructionFileBase::LITTER_NULL_REPLACEMENT);
    }


    /**
     * @param array $data
     * @param string $key
     * @return string
     */
    protected static function getFormattedNLing($data, $key = JsonInputConstant::N_LING)
    {
        $nLingValue = $data[$key];
        if($nLingValue < MeasurementConstant::N_LING_MIN || MeasurementConstant::N_LING_MAX < $nLingValue) {
            $data[$key] = null;
        }

        return self::getFormattedValueFromData($data, MaxLength::N_LING, $key, true);
    }


    /**
     * @param array $data
     * @param string $key
     * @return string
     */
    protected static function getFormattedSuckleCount($data, $key = JsonInputConstant::SUCKLE_COUNT)
    {
        return self::getFormattedValueFromData($data, MaxLength::SUCKLE_COUNT, $key, true);
    }


    /**
     * @param $data
     * @param string $key
     * @return mixed|null
     */
    public static function getFormattedUbnOfBirthWithoutPadding($data, $key = JsonInputConstant::UBN_OF_BIRTH)
    {
        $ubnOfBirth = ArrayUtil::get($key, $data);
        return MixBlupInputFileValidator::getValidatedUbnOfBirth($ubnOfBirth);
    }


    /**
     * @param array $data
     * @param int $columnWidth
     * @param string $key
     * @param bool $useColumnPadding
     * @param string|int $nullReplacement
     * @return string
     */
    protected static function getFormattedGeneVarianceFromData($data, $columnWidth, $key, $useColumnPadding = true, $nullReplacement = MixBlupInstructionFileBase::GENE_DIVERSITY_MISSING_REPLACEMENT)
    {
        $value = ArrayUtil::get($key, $data, $nullReplacement);
        if($value != $nullReplacement) {
            $value = round($value, MixBlupSetting::HETEROSIS_AND_RECOMBINATION_ROUNDING_ACCURACY);
        }
        return DsvWriterUtil::pad($value, $columnWidth, $useColumnPadding);
    }


    /**
     * @param array $data
     * @return string
     */
    protected static function getFormattedHeterosis($data)
    {
        return self::getFormattedGeneVarianceFromData($data, MaxLength::HETEROSIS_AND_RECOMBINATION, JsonInputConstant::HETEROSIS);
    }


    /**
     * @param array $data
     * @return string
     */
    protected static function getFormattedRecombination($data)
    {
        return self::getFormattedGeneVarianceFromData($data, MaxLength::HETEROSIS_AND_RECOMBINATION, JsonInputConstant::RECOMBINATION);
    }


    /**
     * @param array $data
     * @return string
     */
    protected static function getFormattedStillbornCount($data)
    {
        $stillBornCount = ArrayUtil::get(JsonInputConstant::TOTAL_STILLBORN_COUNT, $data, MixBlupInstructionFileBase::MISSING_REPLACEMENT);
        return DsvWriterUtil::pad($stillBornCount, MaxLength::N_LING, true);
    }


    /**
     * @param boolean $value
     * @return int
     */
    protected static function formatMixBlupBoolean($value)
    {
        $formattedValue = $value ? MixBlupSetting::TRUE_RECORD_VALUE : MixBlupSetting::FALSE_RECORD_VALUE;
        return DsvWriterUtil::pad($formattedValue, MaxLength::BOOL_AS_INT, true);
    }


    /**
     * @param Connection $conn
     * @return array
     */
    protected static function dynamicColumnWidths(Connection $conn)
    {
        $columns = [
            'ubn' => 'location',
        ];

        $maxColumnWidths = DsvWriterUtil::maxStringLenghts($conn, $columns);

        return [
            "year_and_ubn_of_birth" => MaxLength::YEAR+strlen('_')+$maxColumnWidths['ubn'],
        ];
    }
}