<?php


namespace AppBundle\MixBlup;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\MaxLength;
use AppBundle\Enumerator\BreedCodeType;
use AppBundle\Setting\MixBlupSetting;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\BreedCodeUtil;
use AppBundle\Util\CsvWriterUtil;
use AppBundle\Util\Translation;
use Doctrine\DBAL\Connection;

/**
 * Class MixBlupDataFileBase
 * @package AppBundle\MixBlup
 */
class MixBlupDataFileBase
{
    /**
     * @param $gender
     * @return string
     */
    protected static function translateGender($gender)
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
            self::formatBreedCodePart(BreedCodeType::TE, $breedCodeParts).
            self::formatBreedCodePart(BreedCodeType::CF, $breedCodeParts).
            self::formatBreedCodePart(BreedCodeType::BM, $breedCodeParts).
            self::formatBreedCodePart(BreedCodeType::SW, $breedCodeParts).
            self::formatBreedCodePart(BreedCodeType::NH, $breedCodeParts).
            self::formatBreedCodePart(BreedCodeType::FL, $breedCodeParts).
            self::formatBreedCodePart(BreedCodeType::HD, $breedCodeParts).
            self::formatOVBreedCodeParts($breedCodeParts);
    }


    /**
     * @param string $breedCodeType
     * @param array $breedCodeParts
     * @return string
     */
    private static function formatBreedCodePart($breedCodeType, $breedCodeParts)
    {
        $breedCodeValueToWrite = ArrayUtil::get($breedCodeType, $breedCodeParts, 0);
        return CsvWriterUtil::pad($breedCodeValueToWrite, MaxLength::BREED_CODE_PART_BY_8_PARTS);
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
        return CsvWriterUtil::pad($sumOfValues, MaxLength::BREED_CODE_PART_BY_8_PARTS);
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
        return CsvWriterUtil::getFormattedValueFromArray($data, $columnWidth, $key, $useColumnPadding, $nullReplacement);
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
     * Note! Gender/Type should already be filtered to only contain 'Ram' or 'Ewe'
     *
     * @param $data
     * @param string $key
     * @param bool $useColumnPadding
     * @return string
     */
    protected static function getFormattedGenderFromType($data, $key = JsonInputConstant::TYPE, $useColumnPadding = true)
    {
        $gender = self::translateGender($data[$key]);
        return CsvWriterUtil::pad($gender, MaxLength::VALID_GENDER, $useColumnPadding);
    }


    /**
     * @param array $data
     * @param string $key
     * @return string
     */
    protected static function getFormattedLitterGroup($data, $key = JsonInputConstant::LITTER_GROUP)
    {
        return self::getFormattedValueFromData($data, MaxLength::LITTER_GROUP, $key, true);
    }


    /**
     * @param array $data
     * @param string $key
     * @return string
     */
    protected static function getFormattedNLing($data, $key = JsonInputConstant::N_LING)
    {
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
    public static function getUbnOfBirthAsLastColumnValue($data, $key = JsonInputConstant::UBN_OF_BIRTH)
    {
        return ArrayUtil::get($key, $data, MixBlupInstructionFileBase::MISSING_REPLACEMENT);
    }


    /**
     * @param array $data
     * @param int $columnWidth
     * @param string $key
     * @param bool $useColumnPadding
     * @param string $nullReplacement
     * @return string
     */
    protected static function getFormattedGeneVarianceFromData($data, $columnWidth, $key, $useColumnPadding = true, $nullReplacement = MixBlupInstructionFileBase::MISSING_REPLACEMENT)
    {
        $value = ArrayUtil::get($key, $data, $nullReplacement);
        if($value != $nullReplacement) {
            $value = round($value, MixBlupSetting::HETEROSIS_AND_RECOMBINATION_ROUNDING_ACCURACY);
        }
        return CsvWriterUtil::pad($value, $columnWidth, $useColumnPadding);
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
     * @param Connection $conn
     * @return array
     */
    protected static function dynamicColumnWidths(Connection $conn)
    {
        $columns = [
            'ubn' => 'location',
        ];

        $maxColumnWidths = CsvWriterUtil::maxStringLenghts($conn, $columns);

        return [
            "year_and_ubn_of_birth" => MaxLength::YEAR+strlen('_')+$maxColumnWidths['ubn'],
        ];
    }
}