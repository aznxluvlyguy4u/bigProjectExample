<?php


namespace AppBundle\Component\MixBlup;

use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\MaxLength;
use AppBundle\Enumerator\BreedCodeType;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Setting\MixBlupSetting;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\BreedCodeUtil;
use AppBundle\Util\CsvWriterUtil;
use Doctrine\DBAL\Connection;

/**
 * Class ReproductionDataFile
 * @package AppBundle\MixBlup
 */
class ReproductionDataFile extends MixBlupDataFileBase implements MixBlupDataFileInterface
{
    const DEFAULT_PMSG_VALUE = false;

    /**
     * @inheritDoc
     */
    static function generateDataFile(Connection $conn)
    {
        $dynamicColumnWidths = self::dynamicColumnWidths($conn);
        
        $records = [];
        foreach (self::getDataBySql($conn) as $data)
        {
            $parsedBreedCode = self::parseBreedCode($data);

            //Allow invalid breedCodes, as blank breedCodes
            if(!$parsedBreedCode) {
                $parsedBreedCode = self::getBlankBreedCodes();
            }

            $formattedUln = MixBlupSetting::INCLUDE_ULNS ? self::getFormattedUln($data) : '';

            $record =
                $formattedUln.
                self::getFormattedAnimalId($data).
                self::getFormattedAge($data, JsonInputConstant::AGE).
                self::getFormattedGenderFromType($data).
                self::getFormattedYearAndUbnOfBirth($data, $dynamicColumnWidths[JsonInputConstant::YEAR_AND_UBN_OF_BIRTH]).
                $parsedBreedCode.
                self::getFormattedHeterosis($data).
                self::getFormattedRecombination($data).
                self::getFormattedHeterosisLamb($data).
                self::getFormattedRecombinationLamb($data).
                self::getTeBreedCodepartOfMother($data). //TODO
                self::getFormattedPmsg($data).
                self::getFormattedPermMil($data).
                self::getFormattedNullableMotherId($data).
                self::getFormattedLitterGroup($data).
                self::getFormattedNLing($data).
                self::getFormattedStillbornCount($data).
                self::getFormattedEarlyFertility($data).
                self::getFormattedWeight($data, JsonInputConstant::BIRTH_WEIGHT).
                self::getFormattedBirthProgress($data).
                self::getFormattedGestationPeriod($data).
                self::getFormattedBirthInterval($data).
                self::getUbnOfBirthAsLastColumnValue($data);

            $records[] = $record;
        }
        
        return $records;
    }


    /**
     * @inheritDoc
     */
    static function getSqlQueryRelatedAnimals()
    {
        return "SELECT animal_mother_id as ".JsonInputConstant::ANIMAL_ID.", mom.type as ".JsonInputConstant::TYPE."
                FROM litter l
                  INNER JOIN animal mom ON mom.id = l.animal_mother_id
                  INNER JOIN declare_nsfo_base b ON l.id = b.id
                WHERE ".self::getSqlBaseFilter()."
                UNION
                SELECT lamb.id as ".JsonInputConstant::ANIMAL_ID.", lamb.type as ".JsonInputConstant::TYPE."
                FROM litter l
                  INNER JOIN declare_nsfo_base b ON l.id = b.id
                  INNER JOIN animal lamb ON lamb.litter_id = l.id
                WHERE ".self::getSqlBaseFilter().self::getErrorLogAnimalPedigreeFilter('mom.id');
    }


    /**
     * @param Connection $conn
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private static function getDataBySql(Connection $conn)
    {
        $sql =
            self::getSqlLitterSizeRecords().'
            UNION
            '.self::getSqlBirthProgressRecords().'
            UNION
            '.self::getSqlEarlyFertilityRecords().'
            ORDER BY record_type_ordination';

        return $conn->query($sql)->fetchAll();
    }
    

    /**
     * @return string
     */
    private static function getSqlEarlyFertilityRecords()
    {
        $nullReplacement = "'".MixBlupInstructionFileBase::MISSING_REPLACEMENT."'";
        return "SELECT
                  3 as record_type_ordination,
                  'early_fertility' as record_type,
                  CONCAT(mom.uln_country_code, mom.uln_number) as ".JsonInputConstant::ULN.",
                  mom.id as ".JsonInputConstant::ANIMAL_ID.",
                  $nullReplacement as ".JsonInputConstant::AGE.",
                  $nullReplacement as ".JsonInputConstant::TYPE.",
                  CONCAT(DATE_PART('year', mom.date_of_birth),'_', mom.ubn_of_birth) as ".JsonInputConstant::YEAR_AND_UBN_OF_BIRTH.",
                  mom.".JsonInputConstant::BREED_CODE.",
                  COALESCE(mom.heterosis, $nullReplacement) as ".JsonInputConstant::HETEROSIS.",
                  COALESCE(mom.recombination, $nullReplacement) as ".JsonInputConstant::RECOMBINATION.",
                  $nullReplacement as ".JsonInputConstant::HETEROSIS_LAMB.",
                  $nullReplacement as ".JsonInputConstant::RECOMBINATION_LAMB.",
                  NULL as ".JsonInputConstant::BREED_CODE_MOTHER.",
                  NULL as ".JsonInputConstant::PMSG.",
                  $nullReplacement as ".JsonInputConstant::PERM_MIL.",
                  $nullReplacement as ".JsonInputConstant::MOTHER_ID.",
                  $nullReplacement as ".JsonInputConstant::LITTER_GROUP.",
                  $nullReplacement as ".JsonInputConstant::N_LING.",
                  $nullReplacement as ".JsonInputConstant::TOTAL_STILLBORN_COUNT.",
                  c.gave_birth_as_one_year_old as ".JsonInputConstant::GAVE_BIRTH_AS_ONE_YEAR_OLD.",
                  $nullReplacement as ".JsonInputConstant::BIRTH_WEIGHT.",
                  $nullReplacement as ".JsonInputConstant::BIRTH_PROGRESS.",
                  $nullReplacement as ".JsonInputConstant::GESTATION_PERIOD.",
                  $nullReplacement as ".JsonInputConstant::BIRTH_INTERVAL.",
                  mom.".JsonInputConstant::UBN_OF_BIRTH."
                FROM animal mom
                  INNER JOIN animal_cache c ON c.animal_id = mom.id
                  INNER JOIN litter l ON l.animal_mother_id = mom.id
                  INNER JOIN declare_nsfo_base b ON l.id = b.id
                WHERE
                  ".self::getSqlBaseFilter()."
                  AND mom.ubn_of_birth NOTNULL
                  ".self::getErrorLogAnimalPedigreeFilter('mom.id');
    }
    
    
    /**
     * @return string
     */
    private static function getSqlBirthProgressRecords()
    {
        $nullReplacement = "'".MixBlupInstructionFileBase::MISSING_REPLACEMENT."'";
        return "SELECT
                  2 as record_type_ordination,
                  'birth_progress' as record_type,
                  CONCAT(lamb.uln_country_code, lamb.uln_number) as ".JsonInputConstant::ULN.",
                  lamb.id as ".JsonInputConstant::ANIMAL_ID.",
                  $nullReplacement as ".JsonInputConstant::AGE.",
                  lamb.".JsonInputConstant::TYPE.",
                  CONCAT(DATE_PART('year', lamb.date_of_birth),'_', lamb.ubn_of_birth) as ".JsonInputConstant::YEAR_AND_UBN_OF_BIRTH.",
                  lamb.".JsonInputConstant::BREED_CODE.",
                  COALESCE(mom.heterosis, $nullReplacement) as ".JsonInputConstant::HETEROSIS.",
                  COALESCE(mom.recombination, $nullReplacement) as ".JsonInputConstant::RECOMBINATION.",
                  COALESCE(lamb.heterosis, $nullReplacement) as ".JsonInputConstant::HETEROSIS_LAMB.",
                  COALESCE(lamb.recombination, $nullReplacement) as ".JsonInputConstant::RECOMBINATION_LAMB.",
                  mom.breed_code as ".JsonInputConstant::BREED_CODE_MOTHER.",
                  NULL as ".JsonInputConstant::PMSG.",
                  $nullReplacement as ".JsonInputConstant::PERM_MIL.",
                  mom.id as ".JsonInputConstant::MOTHER_ID.",
                  CONCAT(mom.uln_country_code, mom.uln_number,'_', LPAD(CAST(l.litter_ordinal AS TEXT), 2, '0')) as ".JsonInputConstant::LITTER_GROUP.",
                  $nullReplacement as ".JsonInputConstant::N_LING.",
                  $nullReplacement as ".JsonInputConstant::TOTAL_STILLBORN_COUNT.",
                  FALSE as ".JsonInputConstant::GAVE_BIRTH_AS_ONE_YEAR_OLD.",
                  COALESCE(c.birth_weight, $nullReplacement) as ".JsonInputConstant::BIRTH_WEIGHT.",
                  COALESCE(birth_progress.mix_blup_score, $nullReplacement) as ".JsonInputConstant::BIRTH_PROGRESS.",
                  COALESCE(l.gestation_period, $nullReplacement) as ".JsonInputConstant::GESTATION_PERIOD.",
                  COALESCE(l.birth_interval, $nullReplacement) as ".JsonInputConstant::BIRTH_INTERVAL.",
                  lamb.".JsonInputConstant::UBN_OF_BIRTH."
                FROM animal lamb
                  LEFT JOIN animal_cache c ON c.animal_id = lamb.id
                  LEFT JOIN birth_progress ON birth_progress.description = lamb.birth_progress
                  INNER JOIN litter l ON l.id = lamb.litter_id
                  INNER JOIN declare_nsfo_base b ON l.id = b.id
                  LEFT JOIN animal mom ON mom.id = l.animal_mother_id
                  LEFT JOIN mate m ON m.id = l.mate_id --Check if this should be an INNER JOIN
                WHERE
                  ".self::getSqlBaseFilter()."
                  AND lamb.gender <> '".GenderType::NEUTER."'
                  AND lamb.date_of_birth NOTNULL AND lamb.ubn_of_birth NOTNULL
                  --   AND mom.recombination NOTNULL AND mom.heterosis NOTNULL".self::getErrorLogAnimalPedigreeFilter('lamb.id');
    }


    /**
     * @return string
     */
    private static function getSqlLitterSizeRecords()
    {
        $nullReplacement = "'".MixBlupInstructionFileBase::MISSING_REPLACEMENT."'";
        return "SELECT
                  1 as record_type_ordination,
                  'litter_size' as record_type,
                  CONCAT(mom.uln_country_code, mom.uln_number) as ".JsonInputConstant::ULN.",
                  mom.id as ".JsonInputConstant::ANIMAL_ID.",
                  date_part('year',age(l.litter_date, mom.date_of_birth)) as ".JsonInputConstant::AGE.",
                  $nullReplacement as ".JsonInputConstant::TYPE.",
                  CONCAT(DATE_PART('year', mom.date_of_birth),'_', mom.ubn_of_birth) as ".JsonInputConstant::YEAR_AND_UBN_OF_BIRTH.",
                  mom.".JsonInputConstant::BREED_CODE.",
                  COALESCE(mom.heterosis, $nullReplacement) as ".JsonInputConstant::HETEROSIS.",
                  COALESCE(mom.recombination, $nullReplacement) as ".JsonInputConstant::RECOMBINATION.",
                  COALESCE(l.heterosis, $nullReplacement) as ".JsonInputConstant::HETEROSIS_LAMB.",
                  COALESCE(l.recombination, $nullReplacement) as ".JsonInputConstant::RECOMBINATION_LAMB.",
                  NULL as ".JsonInputConstant::BREED_CODE_MOTHER.",
                  m.pmsg as ".JsonInputConstant::PMSG.",
                  mom.id as ".JsonInputConstant::PERM_MIL.",
                  $nullReplacement as ".JsonInputConstant::MOTHER_ID.",
                  CONCAT(mom.uln_country_code, mom.uln_number,'_', LPAD(CAST(l.litter_ordinal AS TEXT), 2, '0')) as ".JsonInputConstant::LITTER_GROUP.",
                  born_alive_count + l.stillborn_count as ".JsonInputConstant::N_LING.",
                  stillborn_count as ".JsonInputConstant::TOTAL_STILLBORN_COUNT.",
                  FALSE as ".JsonInputConstant::GAVE_BIRTH_AS_ONE_YEAR_OLD.",
                  $nullReplacement as ".JsonInputConstant::BIRTH_WEIGHT.",
                  $nullReplacement as ".JsonInputConstant::BIRTH_PROGRESS.",
                  $nullReplacement as ".JsonInputConstant::GESTATION_PERIOD.",
                  $nullReplacement as ".JsonInputConstant::BIRTH_INTERVAL.",
                  mom.".JsonInputConstant::UBN_OF_BIRTH."
                FROM litter l
                  INNER JOIN declare_nsfo_base b ON l.id = b.id
                  INNER JOIN animal mom ON l.animal_mother_id = mom.id
                  LEFT JOIN mate m ON m.id = l.mate_id --Check if this should be an INNER JOIN
                WHERE
                  ".self::getSqlBaseFilter()."
                  --AND mom.recombination NOTNULL AND mom.heterosis NOTNULL
                  --AND m.pmsg NOTNULL --NULLABLE?
                  --AND mom.breed_code NOTNULL --NULLABLE?
                  AND mom.ubn_of_birth NOTNULL".self::getErrorLogAnimalPedigreeFilter('mom.id');;
    }


    /**
     * @return string
     */
    private static function getSqlBaseFilter()
    {
        return "DATE_PART('year', NOW()) - DATE_PART('year', l.litter_date) <= ".MixBlupSetting::MEASUREMENTS_FROM_LAST_AMOUNT_OF_YEARS."
                  AND (request_state = '".RequestStateType::COMPLETED."' OR 
                       request_state = '".RequestStateType::FINISHED."' OR
                       request_state = '".RequestStateType::FINISHED_WITH_WARNING."' OR
                       request_state = '".RequestStateType::IMPORTED."'
                       )";
    }


    /**
     * @param array $data
     * @return string
     */
    protected static function getFormattedHeterosisLamb($data)
    {
        return self::getFormattedGeneVarianceFromData($data, MaxLength::HETEROSIS_AND_RECOMBINATION, JsonInputConstant::HETEROSIS_LAMB);
    }


    /**
     * @param array $data
     * @return string
     */
    protected static function getFormattedRecombinationLamb($data)
    {
        return self::getFormattedGeneVarianceFromData($data, MaxLength::HETEROSIS_AND_RECOMBINATION, JsonInputConstant::RECOMBINATION_LAMB);
    }


    /**
     * @param array $data
     * @return int
     */
    protected static function getTeBreedCodepartOfMother($data)
    {
        $breedCodeMother = $data[JsonInputConstant::BREED_CODE_MOTHER];

        $breedCodeParts = BreedCodeUtil::getBreedCodeAs8PartsFromBreedCodeString($breedCodeMother);
        $isValidBreedCode = BreedCodeUtil::verifySumOf8PartBreedCodeParts($breedCodeParts);

        if(!$isValidBreedCode) {
            CsvWriterUtil::pad(MixBlupInstructionFileBase::MISSING_REPLACEMENT, MaxLength::BREED_CODE_PART_BY_8_PARTS, true);;
        }

        return self::formatBreedCodePart(BreedCodeType::TE, $breedCodeParts);
    }


    /**
     * @param array $data
     * @return string
     */
    protected static function getFormattedPmsg($data)
    {
        $pmsg = ArrayUtil::get(JsonInputConstant::PMSG, $data);
        if(!is_bool($pmsg)) {
            $pmsg = self::DEFAULT_PMSG_VALUE;
        }
        return self::formatMixBlupBoolean($pmsg);
    }


    /**
     * @param array $data
     * @return string
     */
    protected static function getFormattedPermMil($data)
    {
        return self::getFormattedUln($data, JsonInputConstant::PERM_MIL);
    }


    /**
     * @param array $data
     * @return string
     */
    protected static function getFormattedNullableMotherId($data)
    {
        $motherId = ArrayUtil::get(JsonInputConstant::MOTHER_ID, $data, MixBlupInstructionFileBase::MISSING_REPLACEMENT);
        return CsvWriterUtil::pad($motherId, MaxLength::ANIMAL_ID, true);
    }


    /**
     * @param array $data
     * @return string
     */
    protected static function getFormattedStillbornCount($data)
    {
        $stillBornCount = ArrayUtil::get(JsonInputConstant::TOTAL_STILLBORN_COUNT, $data, MixBlupInstructionFileBase::MISSING_REPLACEMENT);
        return CsvWriterUtil::pad($stillBornCount, MaxLength::N_LING, true);
    }


    /**
     * @param array $data
     * @return string
     */
    protected static function getFormattedEarlyFertility($data)
    {
        $gaveBirthAsOneYearOld = ArrayUtil::get(JsonInputConstant::GAVE_BIRTH_AS_ONE_YEAR_OLD, $data, MixBlupInstructionFileBase::MISSING_REPLACEMENT);
        if(!is_bool($gaveBirthAsOneYearOld)) {
            return CsvWriterUtil::pad(MixBlupInstructionFileBase::MISSING_REPLACEMENT, MaxLength::BOOL_AS_INT, true);
        }
        return self::formatMixBlupBoolean($gaveBirthAsOneYearOld);
    }


    /**
     * @param array $data
     * @return string
     */
    protected static function getFormattedBirthProgress($data)
    {
        $birthProgressInt = ArrayUtil::get(JsonInputConstant::BIRTH_PROGRESS, $data, MixBlupInstructionFileBase::MISSING_REPLACEMENT);
        return CsvWriterUtil::pad($birthProgressInt, MaxLength::BIRTH_PROGRESS, true);
    }


    /**
     * @param array $data
     * @return string
     */
    protected static function getFormattedGestationPeriod($data)
    {
        $gestationPeriod = ArrayUtil::get(JsonInputConstant::GESTATION_PERIOD, $data, MixBlupInstructionFileBase::MISSING_REPLACEMENT);
        return CsvWriterUtil::pad($gestationPeriod, MaxLength::GESTATION_PERIOD, true);
    }


    /**
     * @param array $data
     * @return string
     */
    protected static function getFormattedBirthInterval($data)
    {
        $birthInterval = ArrayUtil::get(JsonInputConstant::BIRTH_INTERVAL, $data, MixBlupInstructionFileBase::MISSING_REPLACEMENT);
        return CsvWriterUtil::pad($birthInterval, MaxLength::BIRTH_INTERVAL, true);
    }
}