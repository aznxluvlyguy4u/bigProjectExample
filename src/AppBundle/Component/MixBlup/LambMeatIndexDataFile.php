<?php


namespace AppBundle\Component\MixBlup;

use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\MeasurementConstant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\MeasurementType;
use AppBundle\Setting\MixBlupSetting;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\SqlUtil;
use Doctrine\DBAL\Connection;

/**
 * Class LambMeatIndexDataFile
 * @package AppBundle\MixBlup
 */
class LambMeatIndexDataFile extends MixBlupDataFileBase implements MixBlupDataFileInterface
{
    const INCLUDE_TIMED_WEIGHTS_WITH_THE_SCAN_DATA = false;

    /**
     * @inheritDoc
     */
    static function generateDataFile(Connection $conn)
    {
        $baseValuesSearchArray = self::createBaseValuesSearchArray($conn);
        $timedWeightDataByAnimalId = self::getTimedWeightDataByAnimalId($conn);
        $getBirthDataByAnimalId = self::getBirthDataByAnimalId($conn);

        $records = [];


        //1. Add weight at 8 weeks and 20 weeks data
        foreach ($timedWeightDataByAnimalId as $animalId => $timedWeightData)
        {
            $baseRecordValues = ArrayUtil::get($animalId, $baseValuesSearchArray);
            if($baseRecordValues == null) { continue; }

            $records[] =
                $baseRecordValues[ReportLabel::START]
                .self::getFormattedTimedWeightDataRecord($timedWeightData)
                .$baseRecordValues[ReportLabel::END]
            ;
        }


        //2. Add birth data
        foreach ($getBirthDataByAnimalId as $animalId => $birthData)
        {
            $baseRecordValues = ArrayUtil::get($animalId, $baseValuesSearchArray);
            if($baseRecordValues == null) { continue; }

            $records[] =
                $baseRecordValues[ReportLabel::START]
                .self::getFormattedBirthDataRecord($birthData)
                .$baseRecordValues[ReportLabel::END]
            ;
        }


        //3. Add scan data
        foreach (self::getScanData($conn) as $scanData)
        {
            $animalId = $scanData[JsonInputConstant::ANIMAL_ID];
            $baseRecordValues = ArrayUtil::get($animalId, $baseValuesSearchArray);
            if($baseRecordValues == null) { continue; }

            $timedWeightData = ArrayUtil::get($animalId, $timedWeightDataByAnimalId);

            $scanRecordPart = self::getFormattedScanDataRecord($scanData, $timedWeightData);
            if($scanRecordPart) {
                $records[] =
                    $baseRecordValues[ReportLabel::START]
                    .$scanRecordPart
                    .$baseRecordValues[ReportLabel::END]
                ;
            }
        }

        return $records;
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
     * @param Connection $conn
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private static function createBaseValuesSearchArray(Connection $conn)
    {
        $dynamicColumnWidths = self::dynamicColumnWidths($conn);

        $results = [];
        foreach ($conn->query(self::getSqlQueryForBaseValues())->fetchAll() as $data) {
            $parsedBreedCode = self::parseBreedCode($data);
            $formattedMotherId = self::getFormattedMotherId($data);
            $formattedNling = self::getFormattedNLing($data);
            $formattedSuckleCount = self::getFormattedSuckleCount($data);

            if($parsedBreedCode != null
            && $formattedMotherId != MixBlupInstructionFileBase::CONSTANT_MISSING_PARENT_REPLACEMENT
            && $formattedNling != MixBlupInstructionFileBase::MISSING_REPLACEMENT
            && $formattedSuckleCount != MixBlupInstructionFileBase::MISSING_REPLACEMENT) {

                $formattedUln = MixBlupSetting::INCLUDE_ULNS ? self::getFormattedUln($data) : '';

                $recordBase =
                    $formattedUln.
                    self::getFormattedAnimalId($data).
                    $formattedMotherId.
                    self::getFormattedYearAndUbnOfBirth($data, $dynamicColumnWidths[JsonInputConstant::YEAR_AND_UBN_OF_BIRTH]).
                    self::getFormattedGenderFromType($data).
                    self::getFormattedLitterGroup($data).
                    $parsedBreedCode.
                    self::getFormattedHeterosis($data).
                    self::getFormattedRecombination($data).
                    $formattedNling.
                    $formattedSuckleCount;

                $recordEnding =
                    self::getFormattedUbnOfBirthWithoutPadding($data);

                $results[$data[JsonInputConstant::ANIMAL_ID]] = [
                    ReportLabel::START => $recordBase,
                    ReportLabel::END => $recordEnding,
                ];
            }
        }
        return $results;
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
                 mom.id as ".JsonInputConstant::MOTHER_ID.",
                 CONCAT(mom.uln_country_code, mom.uln_number) as ".JsonInputConstant::ULN_MOTHER.",
                 CONCAT(DATE_PART('year', a.date_of_birth),'_', a.ubn_of_birth) as ".JsonInputConstant::YEAR_AND_UBN_OF_BIRTH.",
                 CONCAT(mom.uln_country_code, mom.uln_number,'_', LPAD(CAST(l.litter_ordinal AS TEXT), 2, '0')) as ".JsonInputConstant::LITTER_GROUP.",
                 a.".JsonInputConstant::BREED_CODE.",
                 l.born_alive_count + l.stillborn_count as ".JsonInputConstant::N_LING.",
                 l.".JsonInputConstant::SUCKLE_COUNT.",
                 a.".JsonInputConstant::UBN_OF_BIRTH.",
                 a.".JsonInputConstant::HETEROSIS.",
                 a.".JsonInputConstant::RECOMBINATION.",
                 c.birth_weight, c.tail_length,
                 c.weight_at8weeks, c.age_weight_at8weeks,
                 c.weight_at20weeks, c.age_weight_at20weeks";
        }

        return "SELECT
                  ".$returnValuesString."
                FROM measurement m
                  INNER JOIN animal a ON a.id = CAST(substring(m.animal_id_and_date FROM '([0-9]+)(_)') AS INTEGER)
                  INNER JOIN animal mom ON mom.id = a.parent_mother_id
                  LEFT JOIN animal dad ON dad.id = a.parent_father_id
                  INNER JOIN litter l ON l.id = a.litter_id
                  LEFT JOIN animal_cache c ON c.animal_id = a.id
                WHERE 
                  ".self::getSqlBaseFilter()."
                  AND (
                        m.type = '".MeasurementType::BODY_FAT."' OR
                        m.type = '".MeasurementType::MUSCLE_THICKNESS."' OR
                        m.type = '".MeasurementType::TAIL_LENGTH."' OR
                        m.type = '".MeasurementType::WEIGHT."'
                      )
                  AND ".MeasurementConstant::N_LING_MIN." <= (l.born_alive_count + l.stillborn_count)
                  AND (l.born_alive_count + l.stillborn_count) <= ".MeasurementConstant::N_LING_MAX."
                  ".self::getErrorLogAnimalPedigreeFilter('a.id');
    }


    /**
     * @param string $date
     * @param bool $includeIsActiveMeasurement
     * @return string
     */
    private static function getSqlBaseFilter($date = 'm.measurement_date', $includeIsActiveMeasurement = true)
    {
        $filterString = '';
        if($includeIsActiveMeasurement) {
            $filterString = $filterString.'m.is_active AND ';
        }

        return $filterString."DATE_PART('year', NOW()) - DATE_PART('year', $date) <= ".MixBlupSetting::MEASUREMENTS_FROM_LAST_AMOUNT_OF_YEARS."
                  AND a.gender <> '".GenderType::NEUTER."'
                  AND a.date_of_birth NOTNULL AND a.ubn_of_birth NOTNULL
                  AND a.breed_code NOTNULL
                  AND $date <= NOW()
                  AND a.litter_id NOTNULL";
    }


    /**
     * @param Connection $conn
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private static function getBirthDataByAnimalId(Connection $conn)
    {
        $animalId = JsonInputConstant::ANIMAL_ID;
        $birthWeight = JsonInputConstant::BIRTH_WEIGHT;
        $sql = "SELECT a.id as $animalId,
                  c.tail_length, c.birth_weight as $birthWeight
                FROM animal_cache c
                INNER JOIN animal a ON a.id = c.animal_id
                WHERE
                  ".self::getSqlBaseFilter('date_of_birth', false)."
                  AND (
                          (c.tail_length NOTNULL 
                            AND c.tail_length <= " . MeasurementConstant::TAIL_LENGTH_MIN . " AND c.tail_length <= " . MeasurementConstant::TAIL_LENGTH_MAX . "
                          ) OR c.birth_weight NOTNULL
                      )
                  ".self::getErrorLogAnimalPedigreeFilter('a.id');;
        $results = $conn->query($sql)->fetchAll();
        return SqlUtil::createSearchArrayByKey(JsonInputConstant::ANIMAL_ID, $results);
    }


    /**
     * @param array $birthData
     * @return string
     */
    private static function getFormattedBirthDataRecord(array $birthData)
    {
        return
            self::getFormattedBlankAge(). //Scan age
            self::getFormattedBlankWeight(). //Scan weight
            self::getFormattedWeight($birthData, JsonInputConstant::BIRTH_WEIGHT). //Birth weight
            self::getFormattedTailLength($birthData). //TailLength
            self::getFormattedBlankWeight(). //weight_at8weeks
            self::getFormattedBlankAge(). //age_weight_at8weeks
            self::getFormattedBlankWeight(). //weight_at20weeks
            self::getFormattedBlankAge(). //age_weight_at20weeks
            self::getFormattedBlankFat(). //fat1
            self::getFormattedBlankFat(). //fat2
            self::getFormattedBlankFat(). //fat3
            self::getFormattedBlankMuscleThickness();
    }


    /**
     * @param Connection $conn
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private static function getScanData(Connection $conn)
    {
        $animalId = JsonInputConstant::ANIMAL_ID;
        $measurementAgeFromAnimalIdAndDate = "DATE_PART('day', DATE(substring(animal_id_and_date FROM '([0-9]{4}[-][0-9]{2}[-][0-9]{2})')) - a.date_of_birth)";
        $nullReplacement = MixBlupInstructionFileBase::MISSING_REPLACEMENT;
        
        $sql = "
        SELECT
          a.id as $animalId,
          animal_id_and_date,
          substring(animal_id_and_date FROM '([0-9]{4}[-][0-9]{2}[-][0-9]{2})') as measurement_date,
          $measurementAgeFromAnimalIdAndDate as measurement_age,
          COALESCE(NULLIF(g.weight,0), $nullReplacement) as weight,
          COALESCE(NULLIF(g.muscle_thickness,0), $nullReplacement) as muscle_thickness,
          COALESCE(NULLIF(g.fat1,0), $nullReplacement) as fat1,
          COALESCE(NULLIF(g.fat2,0), $nullReplacement) as fat2,
          COALESCE(NULLIF(g.fat3,0), $nullReplacement) as fat3,
          COALESCE(
              $measurementAgeFromAnimalIdAndDate = c.age_weight_at8weeks
              AND g.weight = c.weight_at8weeks, FALSE) as is_8weeks_weight,
          COALESCE(
              $measurementAgeFromAnimalIdAndDate = c.age_weight_at20weeks
              AND g.weight = c.weight_at20weeks, FALSE) as is_20weeks_weight
        FROM animal a
          INNER JOIN animal_cache c ON c.animal_id = a.id
          INNER JOIN (
                SELECT animal_id_and_date, animal_id,
                  SUM(weight) as weight,
                  SUM(muscle_thickness) as muscle_thickness,
                  SUM(fat1) as fat1,
                  SUM(fat2) as fat2,
                  SUM(fat3) as fat3
                FROM (
                          SELECT
                            wx.animal_id_and_date, animal_id, weight, muscle_thickness, fat1, fat2, fat3
                          FROM(
                          --The nested DISTINCT is necessary to only select the largest weight
                          --if there is a duplicate weight set by animal_id_and_date
                            SELECT
                              DISTINCT ON (m.animal_id_and_date) animal_id_and_date,
                              a.id AS animal_id,
                              w.weight,
                              0 as muscle_thickness,
                              0 as fat1,
                              0 as fat2,
                              0 as fat3
                            FROM measurement m
                              INNER JOIN weight w ON m.id = w.id
                              INNER JOIN animal a ON a.id = w.animal_id
                            WHERE ".self::getSqlBaseFilter()."
                                  AND w.is_revoked = FALSE
                                  AND w.is_birth_weight = FALSE
                            ORDER BY m.animal_id_and_date DESC, weight DESC
                          ) wx
                
                          UNION
                
                          SELECT
                            mx.animal_id_and_date, animal_id, weight, muscle_thickness, fat1, fat2, fat3
                          FROM (
                          --The nested DISTINCT is necessary to only select the largest muscle_thickness
                          --if there is a duplicate muscle_thickness set by animal_id_and_date
                            SELECT
                              DISTINCT ON (m.animal_id_and_date) animal_id_and_date,
                              a.id AS animal_id,
                              0 as weight,
                              t.muscle_thickness as muscle_thickness,
                              0 as fat1,
                              0 as fat2,
                              0 as fat3
                            FROM measurement m
                              INNER JOIN muscle_thickness t ON m.id = t.id
                              INNER JOIN animal a ON a.id = t.animal_id
                            WHERE ".self::getSqlBaseFilter()."
                            ORDER BY m.animal_id_and_date DESC, muscle_thickness DESC
                          ) mx
          
                          UNION
                
                          SELECT
                            bx.animal_id_and_date, animal_id, weight, muscle_thickness, fat1, fat2, fat3
                          FROM (
                          --The nested DISTINCT is necessary to only select the body_fat set with the largest fat3
                          --if there is a duplicate body_fat set by animal_id_and_date
                            SELECT
                              DISTINCT ON (m.animal_id_and_date) animal_id_and_date,
                              a.id AS animal_id,
                              0 as weight,
                              0 as muscle_thickness,
                              fat1.fat as fat1,
                              fat2.fat as fat2,
                              fat3.fat as fat3
                            FROM measurement m
                              INNER JOIN body_fat b ON m.id = b.id
                              INNER JOIN animal a ON a.id = b.animal_id
                              INNER JOIN fat1 ON b.fat1_id = fat1.id
                              INNER JOIN fat2 ON b.fat2_id = fat2.id
                              INNER JOIN fat3 ON b.fat3_id = fat3.id
                            WHERE ".self::getSqlBaseFilter()."
                            ORDER BY m.animal_id_and_date DESC, fat3 DESC
                          ) bx
                          
                ) x GROUP BY animal_id_and_date, animal_id
                ) g ON g.animal_id = a.id";
        
        $scanResults = $conn->query($sql)->fetchAll();
        return $scanResults;
    }


    /**
     * @param Connection $conn
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private static function getTimedWeightDataByAnimalId(Connection $conn)
    {
        $sql = "SELECT animal_id, weight_at8weeks, age_weight_at8weeks, weight_at20weeks, age_weight_at20weeks
                FROM animal_cache c
                INNER JOIN animal a ON a.id = c.animal_id
                WHERE
                  ".self::getSqlBaseFilter('date_of_birth', false)."
                  AND (
                    (weight_at8weeks NOTNULL AND age_weight_at8weeks NOTNULL) OR
                    (weight_at20weeks NOTNULL AND age_weight_at20weeks NOTNULL)
                  )".self::getErrorLogAnimalPedigreeFilter('a.id');
        $results = $conn->query($sql)->fetchAll();
        return SqlUtil::createSearchArrayByKey('animal_id', $results);
    }


    /**
     * @param array $timedWeightData
     * @return string
     */
    private static function getFormattedTimedWeightDataRecord(array $timedWeightData)
    {     
        return
            self::getFormattedBlankAge(). //Scan age
            self::getFormattedBlankWeight(). //Scan weight
            self::getFormattedBlankWeight(). //Birth weight
            self::getFormattedBlankTailLength(). //TailLength
            self::getFormattedWeight($timedWeightData, 'weight_at8weeks').
            self::getFormattedAge($timedWeightData, 'age_weight_at8weeks').
            self::getFormattedWeight($timedWeightData, 'weight_at20weeks').
            self::getFormattedAge($timedWeightData, 'age_weight_at20weeks').
            self::getFormattedBlankFat(). //fat1
            self::getFormattedBlankFat(). //fat2
            self::getFormattedBlankFat(). //fat3
            self::getFormattedBlankMuscleThickness();
    }


    /**
     * @param array $scanData
     * @param array $timedWeightData
     * @return string
     */
    private static function getFormattedScanDataRecord(array $scanData, $timedWeightData)
    {
        $nullReplacement = MixBlupInstructionFileBase::MISSING_REPLACEMENT;

        //$animalIdAndDate = $scanData['animal_id_and_date'];
        //$measurementDateString = $scanData['measurement_date'];

        $formattedAge = self::getFormattedAge($scanData, 'measurement_age');
        $formattedWeight = self::getFormattedWeight($scanData, JsonInputConstant::WEIGHT);

        $muscleThickness = $scanData[JsonInputConstant::MUSCLE_THICKNESS];
        $fat1 = $scanData[JsonInputConstant::FAT1];
        $fat2 = $scanData[JsonInputConstant::FAT2];
        $fat3 = $scanData[JsonInputConstant::FAT3];
        $is8WeeksWeight = $scanData['is_8weeks_weight'];
        $is20WeeksWeight = $scanData['is_20weeks_weight'];

        $areNonWeightValuesBlank =
            $fat1 == $nullReplacement &&
            $fat2 == $nullReplacement &&
            $fat3 == $nullReplacement &&
            $muscleThickness == $nullReplacement;

        if( $is8WeeksWeight && $areNonWeightValuesBlank ||
            $is20WeeksWeight && $areNonWeightValuesBlank)
        {
            //This weight value is already when including the timedWeightData
            return null;
        }

        //Set timedWeightRecord blank by default
        $timedWeightRecord =
            self::getFormattedBlankWeight(). //weight_at8weeks
            self::getFormattedBlankAge(). //age_weight_at8weeks
            self::getFormattedBlankWeight(). //weight_at20weeks
            self::getFormattedBlankAge(); //age_weight_at20weeks

        if(self::INCLUDE_TIMED_WEIGHTS_WITH_THE_SCAN_DATA)
        {
            if($is8WeeksWeight) {
                $timedWeightRecord =
                    $formattedWeight. //weight_at8weeks
                    $formattedAge. //age_weight_at8weeks
                    self::getFormattedBlankWeight(). //weight_at20weeks
                    self::getFormattedBlankAge(); //age_weight_at20weeks

            } elseif($is20WeeksWeight) {
                $timedWeightRecord =
                    self::getFormattedBlankWeight(). //weight_at8weeks
                    self::getFormattedBlankAge(). //age_weight_at8weeks
                    $formattedWeight. //weight_at20weeks
                    $formattedAge; //age_weight_at20weeks
            }
        }

        return
            $formattedAge. //Scan age
            $formattedWeight. //Scan weight
            self::getFormattedBlankWeight(). //Birth weight
            self::getFormattedBlankTailLength(). //TailLength
            $timedWeightRecord.
            self::getFormattedFat($scanData, JsonInputConstant::FAT1). //fat1
            self::getFormattedFat($scanData, JsonInputConstant::FAT2). //fat2
            self::getFormattedFat($scanData, JsonInputConstant::FAT3). //fat3
            self::getFormattedMuscleThickness($scanData, JsonInputConstant::MUSCLE_THICKNESS);
    }

    

}