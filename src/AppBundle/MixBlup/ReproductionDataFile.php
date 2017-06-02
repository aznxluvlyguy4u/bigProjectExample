<?php


namespace AppBundle\MixBlup;

use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\MaxLength;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Setting\MixBlupSetting;
use Doctrine\DBAL\Connection;

/**
 * Class ReproductionDataFile
 * @package AppBundle\MixBlup
 */
class ReproductionDataFile extends MixBlupDataFileBase implements MixBlupDataFileInterface
{

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

            $record =
                self::getFormattedUln($data).
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
                self::getFormattedPmsg($data). //TODO
                self::getFormattedPermMil($data). //TODO
                self::getFormattedNullableMotherId($data). //TODO
                self::getFormattedLitterGroup($data).
                self::getFormattedNLing($data).
                self::getFormattedStillbornCount($data). //TODO
                self::getFormattedEarlyFertility($data). //TODO
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
        // TODO: Implement getSqlQueryRelatedAnimals() method.
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
        $nullReplacement = MixBlupInstructionFileBase::MISSING_REPLACEMENT;
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
                  NULL as breed_code_mother,
                  NULL as pmsg,
                  $nullReplacement as perm_mil,
                  $nullReplacement as ".JsonInputConstant::MOTHER_ID.",
                  $nullReplacement as ".JsonInputConstant::LITTER_GROUP.",
                  $nullReplacement as ".JsonInputConstant::N_LING.",
                  $nullReplacement as stillborn_count,
                  c.gave_birth_as_one_year_old as gave_birth_as_one_year_old,
                  $nullReplacement as ".JsonInputConstant::BIRTH_WEIGHT.",
                  $nullReplacement as birth_progress,
                  $nullReplacement as gestation_period,
                  $nullReplacement as birth_interval,
                  mom.".JsonInputConstant::UBN_OF_BIRTH."
                FROM animal mom
                  INNER JOIN animal_cache c ON c.animal_id = mom.id
                  INNER JOIN litter l ON l.animal_mother_id = mom.id
                  INNER JOIN declare_nsfo_base b ON l.id = b.id
                WHERE
                  ".self::getSqlBaseFilter()."
                  AND mom.ubn_of_birth NOTNULL
                  AND c.gave_birth_as_one_year_old";
    }
    
    
    /**
     * @return string
     */
    private static function getSqlBirthProgressRecords()
    {
        $nullReplacement = MixBlupInstructionFileBase::MISSING_REPLACEMENT;
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
                  mom.breed_code as breed_code_mother,
                  NULL as pmsg,
                  $nullReplacement as perm_mil,
                  mom.id as ".JsonInputConstant::MOTHER_ID.",
                  CONCAT(mom.uln_country_code, mom.uln_number,'_', LPAD(CAST(l.litter_ordinal AS TEXT), 2, '0')) as ".JsonInputConstant::LITTER_GROUP.",
                  $nullReplacement as ".JsonInputConstant::N_LING.",
                  $nullReplacement as stillborn_count,
                  FALSE as gave_birth_as_one_year_old,
                  COALESCE(c.birth_weight, $nullReplacement) as ".JsonInputConstant::BIRTH_WEIGHT.",
                  COALESCE(birth_progress.mix_blup_score, $nullReplacement) as birth_progress,
                  COALESCE(l.gestation_period, $nullReplacement) as gestation_period,
                  COALESCE(l.birth_interval, $nullReplacement) as birth_interval,
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
                  --   AND mom.recombination NOTNULL AND mom.heterosis NOTNULL";
    }


    /**
     * @return string
     */
    private static function getSqlLitterSizeRecords()
    {
        $nullReplacement = MixBlupInstructionFileBase::MISSING_REPLACEMENT;
        return "SELECT
                  1 as record_type_ordination,
                  'litter_size' as record_type,
                  CONCAT(mom.uln_country_code, mom.uln_number) as ".JsonInputConstant::ULN.",
                  mom.id as ".JsonInputConstant::ANIMAL_ID.",
                  EXTRACT(YEAR FROM AGE(mom.date_of_birth)) as ".JsonInputConstant::AGE.",
                  $nullReplacement as ".JsonInputConstant::TYPE.",
                  CONCAT(DATE_PART('year', mom.date_of_birth),'_', mom.ubn_of_birth) as ".JsonInputConstant::YEAR_AND_UBN_OF_BIRTH.",
                  mom.".JsonInputConstant::BREED_CODE.",
                  COALESCE(mom.heterosis, $nullReplacement) as ".JsonInputConstant::HETEROSIS.",
                  COALESCE(mom.recombination, $nullReplacement) as ".JsonInputConstant::RECOMBINATION.",
                  COALESCE(l.heterosis, $nullReplacement) as ".JsonInputConstant::HETEROSIS_LAMB.",
                  COALESCE(l.recombination, $nullReplacement) as ".JsonInputConstant::RECOMBINATION_LAMB.",
                  NULL as breed_code_mother,
                  m.pmsg,
                  mom.id as perm_mil,
                  $nullReplacement as ".JsonInputConstant::MOTHER_ID.",
                  CONCAT(mom.uln_country_code, mom.uln_number,'_', LPAD(CAST(l.litter_ordinal AS TEXT), 2, '0')) as ".JsonInputConstant::LITTER_GROUP.",
                  born_alive_count + l.stillborn_count as ".JsonInputConstant::N_LING.",
                  stillborn_count,
                  FALSE as gave_birth_as_one_year_old,
                  $nullReplacement as ".JsonInputConstant::BIRTH_WEIGHT.",
                  $nullReplacement as birth_progress,
                  $nullReplacement as gestation_period,
                  $nullReplacement as birth_interval,
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
                  AND mom.ubn_of_birth NOTNULL";
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
                       request_state = '".RequestStateType::IMPORTED."' OR
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
        $breedCodeMother = $data['breed_code_mother'];

        //TODO GET TE PART FROM BREEDCODE
        
        return 99999999999999999999999;
    }


    /**
     * @param array $data
     * @return string
     */
    protected static function getFormattedPmsg($data)
    {
        return 99999999999999999999999;
    }


    /**
     * @param array $data
     * @return string
     */
    protected static function getFormattedPermMil($data)
    {
        return 99999999999999999999999;
    }


    /**
     * @param array $data
     * @return string
     */
    protected static function getFormattedNullableMotherId($data)
    {
        return 99999999999999999999999;
    }


    /**
     * @param array $data
     * @return string
     */
    protected static function getFormattedStillbornCount($data)
    {
        return 99999999999999999999999;
    }


    /**
     * @param array $data
     * @return string
     */
    protected static function getFormattedEarlyFertility($data)
    {
        return 99999999999999999999999;
    }


    /**
     * @param array $data
     * @return string
     */
    protected static function getFormattedBirthProgress($data)
    {
        return 99999999999999999999999;
    }


    /**
     * @param array $data
     * @return string
     */
    protected static function getFormattedGestationPeriod($data)
    {
        return 99999999999999999999999;
    }


    /**
     * @param array $data
     * @return string
     */
    protected static function getFormattedBirthInterval($data)
    {
        return 99999999999999999999999;
    }
}