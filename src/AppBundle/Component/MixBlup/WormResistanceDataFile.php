<?php


namespace AppBundle\Component\MixBlup;


use AppBundle\Constant\JsonInputConstant;
use AppBundle\Enumerator\GenderType;
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

    private static $epgFormattedNullFiller;
    private static $sIgaFormattedNullFiller;
    private static $nzIgaFormattedNullFiller;

    /**
     * @inheritDoc
     */
    static function generateDataFile(Connection $conn)
    {
        $dynamicColumnWidths = self::dynamicColumnWidths($conn);
        $yearAndUbnDynamicColumnWidth = $dynamicColumnWidths[JsonInputConstant::YEAR_AND_UBN_OF_BIRTH];

        $results = [];
        foreach ($conn->query(self::getSqlQueryForBaseValues())->fetchAll() as $data) {
            $parsedBreedCode = self::parseBreedCode($data);
            if (!$parsedBreedCode) {
                continue;
            }

            if($data[JsonInputConstant::HETEROSIS] == null
                || $data[JsonInputConstant::RECOMBINATION] == null) {
                /*
                The empty heterosis and recombination values should be filled
                before generating the mixblup input files.
                */
                continue;
            }

            $formattedIsTreated = self::getFormattedIsTreated($data);
            $formattedSamplePeriod = self::getFormattedSamplePeriod($data);

            $formattedUln = MixBlupSetting::INCLUDE_ULNS ? self::getFormattedUln($data) : '';

            $recordBase =
                $formattedUln.
                self::getFormattedAnimalId($data).
                self::getFormattedGenderFromType($data).
                self::getFormattedYearAndUbnOfTreatment($data, $yearAndUbnDynamicColumnWidth).
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
                    $recordEnd
                ;

                $results[] = $record;

            } elseif(self::isLnFECNotNull($data)) {

                $record =
                    $recordBase.
                    $formattedLnFEC.
                    self::getFormattedSIgANullFiller().
                    self::getFormattedNZIgANullFiller().
                    $recordEnd
                ;

                $results[] = $record;

            }
        }

        self::$epgFormattedNullFiller = null;
        self::$sIgaFormattedNullFiller = null;
        self::$nzIgaFormattedNullFiller = null;

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
                 CONCAT(w.year,'_', w.treatment_ubn) as ".JsonInputConstant::YEAR_AND_UBN_OF_TREATMENT.",
                 a.".JsonInputConstant::BREED_CODE.",
                 a.".JsonInputConstant::UBN_OF_BIRTH.",
                 a.".JsonInputConstant::HETEROSIS.",
                 a.".JsonInputConstant::RECOMBINATION.",
                 w.".JsonInputConstant::TREATED_FOR_SAMPLES.",
                 ROUND(CAST(w.epg AS NUMERIC), ".self::EPG_DECIMALS.") as ".JsonInputConstant::EPG.",
                 ROUND(CAST(w.s_iga_glasgow AS NUMERIC), ".self::S_IGA_DECIMALS.") as ".JsonInputConstant::S_IGA_GLASGOW.",
                 ROUND(CAST( w.carla_iga_nz AS NUMERIC), ".self::CARLA_IGA_DECIMALS.") as ".JsonInputConstant::CARLA_IGA_NZ.",
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
                  AND w.year NOTNULL AND w.treatment_ubn NOTNULL
                  AND a.breed_code NOTNULL";
    }


    /**
     * @param array $data
     * @param int $columnWidth
     * @return string
     */
    private static function getFormattedYearAndUbnOfTreatment($data, $columnWidth)
    {
        return self::getFormattedYearAndUbnOfBirth($data, $columnWidth, JsonInputConstant::YEAR_AND_UBN_OF_TREATMENT);
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