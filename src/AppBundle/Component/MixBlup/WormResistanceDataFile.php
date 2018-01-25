<?php


namespace AppBundle\Component\MixBlup;


use AppBundle\Constant\JsonInputConstant;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\MixblupNzClassEnum;
use AppBundle\Setting\MixBlupSetting;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\DsvWriterUtil;
use Doctrine\DBAL\Connection;

class WormResistanceDataFile extends MixBlupDataFileBase implements MixBlupDataFileInterface
{
    const IS_TREATED_DEFAULT_VALUE = '0';
    const SAMPLE_PERIOD_DEFAULT_VALUE = '1';

    const EPG_DECIMALS = 0;
    const S_IGA_DECIMALS = 5;
    const CARLA_IGA_DECIMALS = 5;

    const NZ_CLASS_COLUMN_WIDTH = 3;
    const NZ_IGA_COLUMN_WIDTH = 9;
    const NZ_S_IGA_COLUMN_WIDTH = 9;
    const LN_FEC_COLUMN_WIDTH = 5;

    private static $nzClassTranslationArray = [];

    private static $epgFormattedNullFiller;
    private static $sIgaFormattedNullFiller;
    private static $nzIgaFormattedNullFiller;
    private static $nzClassFormattedNullFiller;

    /**
     * @inheritDoc
     */
    static function generateDataFile(Connection $conn)
    {
        $dynamicColumnWidths = self::dynamicColumnWidths($conn);

        $results = [];
        foreach ($conn->query(self::getSqlQueryForBaseValues())->fetchAll() as $data) {
            $parsedBreedCode = self::parseBreedCode($data);
            if (!$parsedBreedCode) {
                continue;
            }

            $formattedIsTreated = self::getFormattedIsTreated($data);
            $formattedSamplePeriod = self::getFormattedSamplePeriod($data);

            $formattedUln = MixBlupSetting::INCLUDE_ULNS ? self::getFormattedUln($data) : '';

            $recordBase =
                $formattedUln.
                self::getFormattedAnimalId($data).
                self::getFormattedGenderFromType($data).
                self::getFormattedYearAndUbnOfBirth($data, $dynamicColumnWidths[JsonInputConstant::YEAR_AND_UBN_OF_BIRTH]).
                $parsedBreedCode.
                self::getFormattedHeterosis($data).
                self::getFormattedRecombination($data).
                $formattedIsTreated
            ;

            $recordEnd =
                $formattedSamplePeriod.
                self::getFormattedUbnOfBirthWithoutPadding($data)
            ;


            // Records divided up by traits (kenmerken). LnFEC=EPG, SIgA, NZIgA

            if(self::isSIgANotNull($data)) {

                 $record =
                    $recordBase.
                    self::getFormattedLnFECNullFiller().
                    self::getFormattedSIgA($data).
                    self::getFormattedNZIgANullFiller().
                    self::getFormattedNZClassNullFiller().
                    $recordEnd
                ;

                $results[] = $record;
            }


            $formattedLnFEC = self::getFormattedLnFEC($data); // includes null filler

            if(self::isNZIgANotNull($data)) {

                $record =
                    $recordBase.
                    $formattedLnFEC.
                    self::getFormattedSIgANullFiller().
                    self::getFormattedNZIgA($data).
                    self::getFormattedNZclass($data).
                    $recordEnd
                ;

                $results[] = $record;

            } elseif(self::isLnFECNotNull($data)) {

                $record =
                    $recordBase.
                    $formattedLnFEC.
                    self::getFormattedSIgANullFiller().
                    self::getFormattedNZIgANullFiller().
                    self::getFormattedNZClassNullFiller().
                    $recordEnd
                ;

                $results[] = $record;

            }
        }

        self::$epgFormattedNullFiller = null;
        self::$sIgaFormattedNullFiller = null;
        self::$nzIgaFormattedNullFiller = null;
        self::$nzClassFormattedNullFiller = null;

        return $results;
    }

    /**
     * @inheritDoc
     */
    static function getSqlQueryRelatedAnimals()
    {
        $returnValuesString = 'a.id as '.JsonInputConstant::ANIMAL_ID.', a.'.JsonInputConstant::TYPE;
        return self::getSqlQueryForBaseValues($returnValuesString). ' GROUP BY a.id, a.type';
    }

    /**
     * @param string $returnValuesString
     * @return string
     */
    private static function getSqlQueryForBaseValues($returnValuesString = null)
    {
        if($returnValuesString == null) {
            $returnValuesString =
                "a.id as ".JsonInputConstant::ANIMAL_ID.",
                 CONCAT(a.uln_country_code, a.uln_number) as ".JsonInputConstant::ULN.", a.".JsonInputConstant::TYPE.",
                 CONCAT(DATE_PART('year', a.date_of_birth),'_', a.ubn_of_birth) as ".JsonInputConstant::YEAR_AND_UBN_OF_BIRTH.",
                 a.".JsonInputConstant::BREED_CODE.",
                 a.".JsonInputConstant::UBN_OF_BIRTH.",
                 a.".JsonInputConstant::HETEROSIS.",
                 a.".JsonInputConstant::RECOMBINATION.",
                 w.".JsonInputConstant::TREATED_FOR_SAMPLES.",
                 ROUND(CAST(w.epg AS NUMERIC), ".self::EPG_DECIMALS.") as ".JsonInputConstant::EPG.",
                 ROUND(CAST(w.s_iga_glasgow AS NUMERIC), ".self::S_IGA_DECIMALS.") as ".JsonInputConstant::S_IGA_GLASGOW.",
                 ROUND(CAST( w.carla_iga_nz AS NUMERIC), ".self::CARLA_IGA_DECIMALS.") as ".JsonInputConstant::CARLA_IGA_NZ.",
                 w.".JsonInputConstant::CLASS_CARLA_IGA_NZ.",
                 w.".JsonInputConstant::SAMPLE_PERIOD.",
                 w.".JsonInputConstant::YEAR."";
        }

        return "SELECT
                  ".$returnValuesString."
                FROM animal a
                  INNER JOIN worm_resistance w ON a.id = w.animal_id
                WHERE 
                  ".self::getSqlBaseFilter()."
                  ".self::getErrorLogAnimalPedigreeFilter('a.id');
    }


    /**
     * @param string $yearOfMeasurement
     * @return string
     */
    private static function getSqlBaseFilter($yearOfMeasurement = 'w.year')
    {
        return "DATE_PART('year', NOW()) - $yearOfMeasurement <= ".MixBlupSetting::MEASUREMENTS_FROM_LAST_AMOUNT_OF_YEARS."
                  AND a.gender <> '".GenderType::NEUTER."'
                  AND a.date_of_birth NOTNULL AND a.ubn_of_birth NOTNULL
                  AND a.breed_code NOTNULL";
    }


    /**
     * @param array $data
     * @return string
     */
    private static function getFormattedIsTreated(array $data)
    {
        return self::getFormattedBooleanValueAsIntegerStringFromData(
            $data,
            JsonInputConstant::TREATED_FOR_SAMPLES,
            true,
            self::IS_TREATED_DEFAULT_VALUE
        );
    }


    /**
     * @param array $data
     * @return bool
     */
    private static function isLnFECNotNull(array $data)
    {
        return ArrayUtil::get(JsonInputConstant::EPG, $data) !== null;
    }


    /**
     * @param array $data
     * @return bool
     */
    private static function isSIgANotNull(array $data)
    {
        return ArrayUtil::get(JsonInputConstant::S_IGA_GLASGOW, $data) !== null;
    }


    /**
     * @param array $data
     * @return bool
     */
    private static function isNZIgANotNull(array $data)
    {
        return ArrayUtil::get(JsonInputConstant::CARLA_IGA_NZ, $data) !== null;
    }


    /**
     * @param array $data
     * @return string
     */
    private static function getFormattedLnFEC(array $data)
    {
        return self::getFormattedValueFromData(
            $data,
            self::LN_FEC_COLUMN_WIDTH,
            JsonInputConstant::EPG,
            true,
            MixBlupInstructionFileBase::MISSING_REPLACEMENT
        );
    }

    /**
     * @return string
     */
    private static function getFormattedLnFECNullFiller()
    {
        if (self::$epgFormattedNullFiller === null) {
            self::$epgFormattedNullFiller = DsvWriterUtil::pad(
                MixBlupInstructionFileBase::MISSING_REPLACEMENT,
                self::LN_FEC_COLUMN_WIDTH,
                true
            );
        }
        return self::$epgFormattedNullFiller;
    }


    /**
     * @param array $data
     * @return string
     */
    private static function getFormattedSIgA(array $data)
    {
        return self::getFormattedValueFromData(
            $data,
            self::NZ_S_IGA_COLUMN_WIDTH,
            JsonInputConstant::S_IGA_GLASGOW,
            true,
            MixBlupInstructionFileBase::MISSING_REPLACEMENT
        );
    }


    /**
     * @return string
     */
    private static function getFormattedSIgANullFiller()
    {
        if (self::$sIgaFormattedNullFiller === null) {
            self::$sIgaFormattedNullFiller = DsvWriterUtil::pad(
                MixBlupInstructionFileBase::MISSING_REPLACEMENT,
                self::NZ_S_IGA_COLUMN_WIDTH,
                true
            );
        }
        return self::$sIgaFormattedNullFiller;
    }


    /**
     * @param array $data
     * @return string
     */
    private static function getFormattedNZIgA(array $data)
    {
        return self::getFormattedValueFromData(
            $data,
            self::NZ_IGA_COLUMN_WIDTH,
            JsonInputConstant::CARLA_IGA_NZ,
            true,
            MixBlupInstructionFileBase::MISSING_REPLACEMENT
        );
    }


    /**
     * @return string
     */
    private static function getFormattedNZIgANullFiller()
    {
        if (self::$nzIgaFormattedNullFiller === null) {
            self::$nzIgaFormattedNullFiller = DsvWriterUtil::pad(
                MixBlupInstructionFileBase::MISSING_REPLACEMENT,
                self::NZ_IGA_COLUMN_WIDTH,
                true
            );
        }
        return self::$nzIgaFormattedNullFiller;
    }


    /**
     * @param array $data
     * @return string
     */
    private static function getFormattedNZclass(array $data)
    {
        $data = self::translateNZclassInData($data);

        return self::getFormattedValueFromData(
            $data,
            self::NZ_CLASS_COLUMN_WIDTH,
            JsonInputConstant::CLASS_CARLA_IGA_NZ,
            true,
            MixBlupInstructionFileBase::MISSING_REPLACEMENT
        );
    }


    /**
     * @return string
     */
    private static function getFormattedNZClassNullFiller()
    {
        if (self::$nzClassFormattedNullFiller === null) {
            self::$nzClassFormattedNullFiller = DsvWriterUtil::pad(
                MixBlupInstructionFileBase::MISSING_REPLACEMENT,
                self::NZ_CLASS_COLUMN_WIDTH,
                true
            );
        }
        return self::$nzClassFormattedNullFiller;
    }


    /**
     * @param array $data
     * @return array
     */
    private static function translateNZclassInData(array $data)
    {
        $classCarlaIgaNz = ArrayUtil::get(JsonInputConstant::CLASS_CARLA_IGA_NZ, $data);

        $data[JsonInputConstant::CLASS_CARLA_IGA_NZ] = self::translateNZclassDatabaseValueToMixblupValue($classCarlaIgaNz);

        return $data;
    }


    /**
     * @param string $classCarlaIgaNz
     * @return mixed|null
     */
    public static function translateNZclassDatabaseValueToMixblupValue($classCarlaIgaNz)
    {
        if (count(self::$nzClassTranslationArray) === 0) {
            self::$nzClassTranslationArray = array_flip(MixblupNzClassEnum::getConstants());
        }

        switch (strtoupper($classCarlaIgaNz)) {
            case MixblupNzClassEnum::NONE_DETECTED: return 0;
            case MixblupNzClassEnum::TRACE:         return 1;
            case MixblupNzClassEnum::LOW:           return 2;
            case MixblupNzClassEnum::MEDIUM:        return 3;
            case MixblupNzClassEnum::HIGH:          return 4;
            default:                                return MixBlupInstructionFileBase::MISSING_REPLACEMENT;
        }
    }


    /**
     * @param array $data
     * @return string
     */
    private static function getFormattedSamplePeriod(array $data)
    {
        return self::getFormattedValueFromData(
            $data,
            3,
            JsonInputConstant::SAMPLE_PERIOD,
            true,
            self::SAMPLE_PERIOD_DEFAULT_VALUE
        );
    }


}