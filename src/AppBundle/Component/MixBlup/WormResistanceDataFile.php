<?php


namespace AppBundle\Component\MixBlup;


use AppBundle\Constant\JsonInputConstant;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Service\NormalDistributionService;
use AppBundle\Setting\MixBlupSetting;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\DsvWriterUtil;
use AppBundle\Util\NumberUtil;
use Doctrine\DBAL\Connection;

class WormResistanceDataFile extends MixBlupDataFileBase implements MixBlupDataFileInterface
{
    const IS_TREATED_DEFAULT_VALUE = '0';
    const SAMPLE_PERIOD_DEFAULT_VALUE = '1';

    const EPG_DECIMALS = 0;
    const LN_FEC_DECIMALS = 5;
    const S_IGA_DECIMALS = 5;
    const ODIN_BC_DECIMALS = 5;
    const CARLA_IGA_DECIMALS = 5;

    const NZ_CLASS_COLUMN_WIDTH = 3;
    const NZ_IGA_COLUMN_WIDTH = 9;
    const NZ_S_IGA_COLUMN_WIDTH = 9;
    const ODIN_BC_COLUMN_WIDTH = 9;
    const LN_FEC_COLUMN_WIDTH = 9;

    private static $lnFecFormattedNullFiller;
    private static $sIgaFormattedNullFiller;
    private static $odinBcFormattedNullFiller;
    private static $nzIgaFormattedNullFiller;

    /** @var float */
    private static $minOdinBC;
    /** @var float */
    private static $maxOdinBC;

    /**
     * @inheritDoc
     */
    static function generateDataFile(Connection $conn)
    {
        $dynamicColumnWidths = self::dynamicColumnWidths($conn);
        $yearAndUbnDynamicColumnWidth = $dynamicColumnWidths[JsonInputConstant::YEAR_AND_UBN_OF_BIRTH];

        $sqlValues = $conn->query(WormResistanceDataFile::getSqlQueryForBaseValues())->fetchAll();

        if (count($sqlValues) === 0) {
            return [];
        }

        self::initializeOdinBcBoundaryValues($sqlValues);

        $results = [];
        foreach ($sqlValues as $data) {
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
                self::getFormattedLitterGroup($data, MixBlupInstructionFileBase::MISSING_REPLACEMENT).
                self::getFormattedNLing($data).
                self::getFormattedStillbornCount($data).
                self::getFormattedFirstLitterAgeAndLastLitterOrdinal($data).
                self::getFormattedUbnOfBirthWithoutPadding($data)
            ;


            // Records divided up by traits (kenmerken). LnFEC=EPG, OdinBC, NZIgA. SIgA is replaced by OdinBC

            if(self::isSIgANotNull($data)) {

                 $record =
                    $recordBase.
                    self::getFormattedLnFECNullFiller().
                    //self::getFormattedSIgA($data).
                    self::getFormattedOdinBc($data).
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
                    //self::getFormattedSIgANullFiller().
                    self::getFormattedOdinBcNullFiller().
                    self::getFormattedNZIgA($data).
                    $recordEnd
                ;

                $results[] = $record;

            } elseif(self::isLnFECNotNull($data)) {

                $record =
                    $recordBase.
                    $formattedLnFEC.
                    //self::getFormattedSIgANullFiller().
                    self::getFormattedOdinBcNullFiller().
                    self::getFormattedNZIgANullFiller().
                    $recordEnd
                ;

                $results[] = $record;

            }
        }

        self::$lnFecFormattedNullFiller = null;
        self::$sIgaFormattedNullFiller = null;
        self::$odinBcFormattedNullFiller = null;
        self::$nzIgaFormattedNullFiller = null;

        return $results;
    }

    /**
     * @inheritDoc
     */
    static function getSqlQueryRelatedAnimals()
    {
        $returnValuesString = 'a.id as '.JsonInputConstant::ANIMAL_ID.', a.'.JsonInputConstant::TYPE;
        return self::getSqlQueryForBaseValues($returnValuesString, false). ' GROUP BY a.id, a.type';
    }

    /**
     * @param string $returnValuesString
     * @param boolean $includeLitterData
     * @return string
     */
    public static function getSqlQueryForBaseValues($returnValuesString = null, $includeLitterData = true)
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

        $litterDataJoin = '';
        $litterAlias = 'litter';

        if ($includeLitterData) {
            $litterDataJoin =
  " INNER JOIN animal_cache c ON c.animal_id = a.id
    LEFT JOIN (
    ".self::getJoinLatestLitterOnParentId(true)."
    )".$litterAlias." ON ".$litterAlias.".animal_id = w.animal_id";

            $returnValuesString .= ',
              c.'.JsonInputConstant::GAVE_BIRTH_AS_ONE_YEAR_OLD.',
              '.self::getLitterReturnValues($litterAlias);
        }

        return "SELECT
                  ".$returnValuesString."
                FROM animal a
                  INNER JOIN worm_resistance w ON a.id = w.animal_id
                 ".$litterDataJoin."
                WHERE 
                  ".self::getSqlBaseFilter()."
                  ".self::getErrorLogAnimalPedigreeFilter('a.id');
    }


    /**
     * @param string $litterAlias
     * @return string
     */
    private static function getLitterReturnValues($litterAlias)
    {
        return
            $litterAlias.'.'.JsonInputConstant::LITTER_ORDINAL." as ".JsonInputConstant::LITTER_ORDINAL.','.
            $litterAlias.'.'.JsonInputConstant::LITTER_GROUP." as ".JsonInputConstant::LITTER_GROUP.",
            $litterAlias.".JsonInputConstant::N_LING." as ".JsonInputConstant::N_LING.",
            $litterAlias.".JsonInputConstant::TOTAL_STILLBORN_COUNT." as ".JsonInputConstant::TOTAL_STILLBORN_COUNT
        ;
    }


    /**
     * @param string $litterAlias
     * @return string
     */
    private static function getNestedLitterReturnValues($litterAlias)
    {
        return
            $litterAlias.'.'.JsonInputConstant::LITTER_ORDINAL.','.
            "CONCAT(a.uln_country_code, a.uln_number,'_', LPAD(CAST($litterAlias.litter_ordinal AS TEXT), 2, '0')) as ".JsonInputConstant::LITTER_GROUP.",
                 $litterAlias.born_alive_count + $litterAlias.stillborn_count as ".JsonInputConstant::N_LING.",
                 $litterAlias.stillborn_count as ".JsonInputConstant::TOTAL_STILLBORN_COUNT
        ;
    }


    /**
     * @param boolean $isMother
     * @return string
     */
    private static function getJoinLatestLitterOnParentId($isMother)
    {
        $parentIdLabel = $isMother ? 'animal_mother_id' : 'animal_father_id';
        
        return
   "SELECT
      l.$parentIdLabel as ".JsonInputConstant::ANIMAL_ID.",
      ".self::getNestedLitterReturnValues('l')."
    FROM litter l
      INNER JOIN declare_nsfo_base b ON b.id = l.id
      INNER JOIN animal a ON l.$parentIdLabel = a.id
      INNER JOIN (
                   -- Find the latest litter before sampling date
                   -- or in same year if sampling date is missing
                   SELECT
                     l.$parentIdLabel, MAX(l.litter_date) as litter_date
                   FROM litter l
                     INNER JOIN declare_nsfo_base b ON b.id = l.id
                     INNER JOIN worm_resistance r ON r.animal_id = l.$parentIdLabel
                     INNER JOIN animal mom ON l.$parentIdLabel = mom.id
                   WHERE
                     (
                       (r.sampling_date NOTNULL AND l.litter_date <= r.sampling_date)
                       OR
                       (r.sampling_date ISNULL AND DATE_PART('year', l.litter_date) <= r.year)
                     )
                     AND ".self::getLitterStateFilter('b', 'l')."
                   GROUP BY l.$parentIdLabel
                 )g ON g.$parentIdLabel = l.$parentIdLabel AND g.litter_date = l.litter_date
    WHERE ".self::getLitterStateFilter('b', 'l');
    }


    /**
     * @param string $baseAlias
     * @param string $litterAlias
     * @return string
     */
    private static function getLitterStateFilter($baseAlias = 'b', $litterAlias = 'l')
    {
        return "  (
                            $baseAlias.request_state = '".RequestStateType::FINISHED."' OR 
                            $baseAlias.request_state = '".RequestStateType::FINISHED_WITH_WARNING."' OR 
                            $baseAlias.request_state = '".RequestStateType::IMPORTED."'
                          )
                     AND (
                            $litterAlias.status = '".RequestStateType::COMPLETED."' OR
                            $litterAlias.status = '".RequestStateType::IMPORTED."'
                          )";
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
        $value = ArrayUtil::get(JsonInputConstant::EPG, $data);

        if ($value) {
            $logValue = self::numberFormat(
                log(floatval($value)),
                self::LN_FEC_DECIMALS
            );
        } else {
            $logValue = MixBlupInstructionFileBase::MISSING_REPLACEMENT;
        }

        return DsvWriterUtil::pad($logValue, self::LN_FEC_COLUMN_WIDTH, true);
    }

    /**
     * @return string
     */
    private static function getFormattedLnFECNullFiller()
    {
        return self::getFormattedStaticVarNullFiller(self::$lnFecFormattedNullFiller, self::LN_FEC_COLUMN_WIDTH);
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
     * @param array $data
     * @return string
     */
    private static function getFormattedOdinBc(array $data)
    {
        $siga = ArrayUtil::get(JsonInputConstant::S_IGA_GLASGOW, $data);

        $value = MixBlupInstructionFileBase::MISSING_REPLACEMENT;

        if ($siga) {
            $odinBc = self::calcOdinBc($siga);

            if (self::$minOdinBC <= $odinBc && $odinBc <= self::$maxOdinBC) {
                $value = self::numberFormat($odinBc,self::ODIN_BC_DECIMALS);
            }
        }

        return DsvWriterUtil::pad(
            $value,
            self::ODIN_BC_COLUMN_WIDTH,
            true)
            ;
    }


    /**
     * @return string
     */
    private static function getFormattedOdinBcNullFiller()
    {
        return self::getFormattedStaticVarNullFiller(self::$odinBcFormattedNullFiller, self::ODIN_BC_COLUMN_WIDTH);
    }


    /**
     * @return string
     */
    private static function getFormattedSIgANullFiller()
    {
        return self::getFormattedStaticVarNullFiller(self::$sIgaFormattedNullFiller, self::NZ_S_IGA_COLUMN_WIDTH);
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
        return self::getFormattedStaticVarNullFiller(self::$nzIgaFormattedNullFiller, self::NZ_IGA_COLUMN_WIDTH);
    }


    /**
     * @param string $staticVar
     * @param string $padLength
     * @return string
     */
    private static function getFormattedStaticVarNullFiller($staticVar, $padLength)
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


    /**
     * @param array $data
     * @return string
     */
    private static function getFormattedFirstLitterAgeAndLastLitterOrdinal(array $data)
    {
        $ageValue = null;
        $gaveBirthAsOneYearOld = ArrayUtil::get(JsonInputConstant::GAVE_BIRTH_AS_ONE_YEAR_OLD, $data, null);
        if ($gaveBirthAsOneYearOld !== null) {
            $ageValue = $gaveBirthAsOneYearOld ? 1 : 2; // for all ages above 1 the value is 2
        }

        $litterOrdinal = ArrayUtil::get(JsonInputConstant::LITTER_ORDINAL, $data, null);

        $value = $ageValue && $litterOrdinal ? strval($ageValue).strval($litterOrdinal)
            : MixBlupInstructionFileBase::CLASS_MISSING_REPLACEMENT;

        return DsvWriterUtil::pad($value, 3, true);
    }


    /**
     * @param $sqlValues
     */
    public static function initializeOdinBcBoundaryValues($sqlValues)
    {
        $nd = self::generateOdinBcNormalDistribution($sqlValues);

        self::$maxOdinBC = $nd->getMean() + 4 * $nd->getStandardDeviation();
        self::$minOdinBC = $nd->getMean() - 4 * $nd->getStandardDeviation();
    }


    /**
     * @param $sqlValues
     * @return \AppBundle\Entity\NormalDistribution|null
     */
    private static function generateOdinBcNormalDistribution($sqlValues)
    {
        $sIgaValues = ArrayUtil::mapNestedValues('s_iga_glasgow', $sqlValues, true);
        $odinValues = array_map('AppBundle\Component\MixBlup\WormResistanceDataFile::calcOdinBc', $sIgaValues);
        return NormalDistributionService::getMeanAndStandardDeviation($odinValues);
    }


    /**
     * @param float $siga
     * @return float
     */
    private static function calcOdinBc($siga)
    {
        if ($siga === null) {
            return null;
        }

        $divisor = pow((floatval($siga)/100.0) + 1, 0.25);

        if (NumberUtil::isFloatZero($divisor)) {
            return null;
        }

        return -4.0 / $divisor;
    }
}