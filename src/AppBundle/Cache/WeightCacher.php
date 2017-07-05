<?php


namespace AppBundle\Cache;
use AppBundle\Constant\MeasurementConstant;
use AppBundle\Enumerator\WeightType;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\DBAL\Connection;

/**
 * Class AnimalWeightCacher
 * @package AppBundle\Cache
 */
class WeightCacher
{
    /**
     * @param Connection $conn
     * @return int
     */
    public static function updateAllWeights(Connection $conn){
        return self::updateWeights($conn, null);
    }


    /**
     * @param Connection $conn
     * @param array $animalIds
     * @return int
     */
    public static function updateWeights(Connection $conn, $animalIds){
        return
              self::updateLastWeights($conn, $animalIds)
            + self::updateBirthWeights($conn, $animalIds)
            + self::update8WeekWeights($conn, $animalIds)
            + self::update20WeekWeights($conn, $animalIds)
            ;
    }


    /**
     * @param Connection $conn
     * @param array $animalIds
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function updateLastWeights(Connection $conn, $animalIds)
    {
        $updateCount = 0;

        $animalIdFilterString = "";
        $animalIdFilterString2 = "";
        if(is_array($animalIds)) {
            if(count($animalIds) == 0) {
                return $updateCount;
            }
            else {
                $animalIdFilterString = " AND (".SqlUtil::getFilterStringByIdsArray($animalIds,'ww.animal_id').")";
                $animalIdFilterString2 = " AND (".SqlUtil::getFilterStringByIdsArray($animalIds,'animal_id').")";
            }
        } elseif($animalIds != null) {
            return $updateCount;
        }

        $sqlBase = "FROM weight w
                       INNER JOIN measurement m ON w.id = m.id
                       INNER JOIN (
                                    SELECT animal_id, MAX(measurement_date) as max_measurement_date,
                                      MAX(log_date) as max_log_date
                                    FROM weight ww
                                      INNER JOIN measurement mm ON ww.id = mm.id
                                      --Remove is_revoked if column data is moved to is_active and variable is removed
                                    WHERE ww.is_revoked = FALSE AND mm.is_active ".$animalIdFilterString."
                                    GROUP BY animal_id
                   ) AS last ON last.animal_id = w.animal_id AND m.measurement_date = last.max_measurement_date
                       AND m.log_date = last.max_log_date";

        $sqlUpdateToNonBlank = "WITH rows AS (
                  UPDATE animal_cache SET
                    last_weight = v.last_weight,
                    weight_measurement_date = v.weight_measurement_date,
                    log_date = '".TimeUtil::getTimeStampNow()."'
                  FROM (
                         SELECT w.animal_id, w.weight, m.measurement_date
                         ".$sqlBase." 
                  INNER JOIN animal_cache c ON c.animal_id = w.animal_id
                  INNER JOIN animal a ON w.animal_id = a.id
                  WHERE (
                  c.last_weight ISNULL OR c.last_weight <> w.weight OR
                  c.weight_measurement_date ISNULL OR c.weight_measurement_date <> m.measurement_date
                  )
                  -- AND a.location_id = 00000 < filter location_id here when necessary
                ) AS v(animal_id, last_weight, weight_measurement_date) WHERE animal_cache.animal_id = v.animal_id
                RETURNING 1
                )
                SELECT COUNT(*) AS count FROM rows;";
        $updateCount = $conn->query($sqlUpdateToNonBlank)->fetch()['count'];

        $sqlMakeBlank = "WITH rows AS (
                            UPDATE animal_cache SET last_weight = NULL,
                            weight_measurement_date = NULL,
                            log_date = '".TimeUtil::getTimeStampNow()."' 
                            WHERE (animal_cache.last_weight NOTNULL OR animal_cache.weight_measurement_date NOTNULL)
                            ".$animalIdFilterString2."
                            AND animal_cache.animal_id NOT IN (
                                SELECT w.animal_id 
                                ".$sqlBase."
                            )
                            RETURNING 1
                        )
                        SELECT COUNT(*) AS count FROM rows;";
        $updateCount += $conn->query($sqlMakeBlank)->fetch()['count'];

        return $updateCount;
    }


    /**
     * @param Connection $conn
     * @param array $animalIds
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function updateBirthWeights(Connection $conn, $animalIds)
    {
        $updateCount = 0;

        $animalIdFilterString = "";
        $animalIdFilterString2 = "";
        if(is_array($animalIds)) {
            if(count($animalIds) == 0) {
                return $updateCount;
            }
            else {
                $animalIdFilterString = " AND (".SqlUtil::getFilterStringByIdsArray($animalIds,'ww.animal_id').")";
                $animalIdFilterString2 = " AND (".SqlUtil::getFilterStringByIdsArray($animalIds,'animal_id').")";
            }
        } elseif($animalIds != null) {
            return $updateCount;
        }

        $sqlBase = " FROM animal a
                    INNER JOIN animal_cache c ON c.animal_id = a.id
                    INNER JOIN weight w ON w.animal_id = a.id
                    INNER JOIN measurement m ON w.id = m.id
                    INNER JOIN (
                    SELECT ww.animal_id,
                        MIN(ABS(DATE_PART('day', mm.measurement_date - aa.date_of_birth))) as min_days,
                        MAX(mm.log_date) as max_log_date
                    FROM animal aa
                    INNER JOIN weight ww ON aa.id = ww.animal_id
                    INNER JOIN measurement mm ON ww.id = mm.id
                    WHERE is_active AND ww.is_revoked = false AND ww.is_birth_weight
                    AND ".MeasurementConstant::BIRTH_WEIGHT_MIN_VALUE." <= ww.weight 
                    AND ww.weight <= ".MeasurementConstant::BIRTH_WEIGHT_MAX_VALUE." 
                    AND ".MeasurementConstant::BIRTH_WEIGHT_MIN_AGE." <= DATE_PART('day', mm.measurement_date - aa.date_of_birth)
                    AND DATE_PART('day', mm.measurement_date - aa.date_of_birth) <= ".MeasurementConstant::BIRTH_WEIGHT_MAX_AGE." 
                    ".$animalIdFilterString."
                    GROUP BY ww.animal_id
                    )g ON g.animal_id = a.id
                    WHERE ABS(DATE_PART('day', m.measurement_date - a.date_of_birth)) = g.min_days
                        AND m.log_date = g.max_log_date
                        AND m.is_active AND w.is_revoked = false AND w.is_birth_weight ";

        $sqlUpdateToNonBlank = "WITH rows AS (
                                UPDATE animal_cache SET birth_weight = v.birth_weight,
                                log_date = NOW() 
                                FROM (
                                    SELECT w.id, w.weight, w.animal_id 
                                    ".$sqlBase." 
                                          AND (c.birth_weight ISNULL OR
                                                c.birth_weight <> w.weight)
                                ) AS v(weight_id, birth_weight, animal_id)
                                WHERE animal_cache.animal_id = v.animal_id
                                RETURNING 1
                                )
                                SELECT COUNT(*) AS count FROM rows;";
        $updateCount = $conn->query($sqlUpdateToNonBlank)->fetch()['count'];

        $sqlMakeBlank = "WITH rows AS (
                            UPDATE animal_cache SET birth_weight = NULL,
                            log_date = NOW() 
                            WHERE animal_cache.birth_weight NOTNULL
                            ".$animalIdFilterString2."
                            AND animal_cache.animal_id NOT IN (
                                SELECT w.animal_id
                                ".$sqlBase."
                            )
                            RETURNING 1
                        )
                        SELECT COUNT(*) AS count FROM rows;";
        $updateCount += $conn->query($sqlMakeBlank)->fetch()['count'];

        return $updateCount;
    }


    /**
     * @param Connection $conn
     * @param array $animalIds
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function update8WeekWeights(Connection $conn, $animalIds)
    {
        return self::updateXWeekWeights($conn, $animalIds, WeightType::EIGHT_WEEKS);
    }


    /**
     * @param Connection $conn
     * @param array $animalIds
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function update20WeekWeights(Connection $conn, $animalIds)
    {
        return self::updateXWeekWeights($conn, $animalIds, WeightType::TWENTY_WEEKS);
    }


    /**
     * @param Connection $conn
     * @param array $animalIds
     * @param string $weightType
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    private static function updateXWeekWeights(Connection $conn, $animalIds, $weightType)
    {
        $updateCount = 0;

        $animalIdFilterString = "";
        $animalIdFilterString2 = "";
        if(is_array($animalIds)) {
            if(count($animalIds) == 0) {
                return $updateCount;
            }
            else {
                $animalIdFilterString = " AND (".SqlUtil::getFilterStringByIdsArray($animalIds,'ww.animal_id').")";
                $animalIdFilterString2 = " AND (".SqlUtil::getFilterStringByIdsArray($animalIds,'animal_id').")";
            }
        } elseif($animalIds != null) {
            return $updateCount;
        }


        switch ($weightType) {
            case WeightType::EIGHT_WEEKS:
                $weightColumnName = 'weight_at8weeks';
                $minWeightValue = MeasurementConstant::WEIGHT_AT_8_WEEKS_MIN_VALUE;
                $maxWeightValue = MeasurementConstant::WEIGHT_AT_8_WEEKS_MAX_VALUE;
                $minAgeInDays = MeasurementConstant::WEIGHT_AT_8_WEEKS_MIN_AGE;
                $maxAgeInDays = MeasurementConstant::WEIGHT_AT_8_WEEKS_MAX_AGE;
                $medianAgeInDays = MeasurementConstant::WEIGHT_AT_8_WEEKS_MEDIAN_AGE;
                break;

            case WeightType::TWENTY_WEEKS:
                $weightColumnName = 'weight_at20weeks';
                $minWeightValue = MeasurementConstant::WEIGHT_AT_20_WEEKS_MIN_VALUE;
                $maxWeightValue = MeasurementConstant::WEIGHT_AT_20_WEEKS_MAX_VALUE;
                $minAgeInDays = MeasurementConstant::WEIGHT_AT_20_WEEKS_MIN_AGE;
                $maxAgeInDays = MeasurementConstant::WEIGHT_AT_20_WEEKS_MAX_AGE;
                $medianAgeInDays = MeasurementConstant::WEIGHT_AT_20_WEEKS_MEDIAN_AGE;
                break;

            default:
                return $updateCount;
        }

        $ageColumnName = 'age_'.$weightColumnName;
        $dateColumnName = $weightColumnName.'_measurement_date';

        $sqlBase = "FROM weight w
             INNER JOIN animal_cache c ON c.animal_id = w.animal_id
             INNER JOIN measurement m ON m.id = w.id
             INNER JOIN animal a ON a.id = w.animal_id
             INNER JOIN (
                 -- A double GROUP BY + INNER JOIN is used here because there are cases where
                 -- two weights have equal ABS(measurementDate-targetDate) values.
                 -- One being ABS(-x) and the other ABS(x).
                 -- In that case pick the higher weight
                 SELECT MAX(w.weight) as max_weight, w.animal_id, g.min_days as days_deviation_from_target_days
                 FROM animal a
                   INNER JOIN animal_cache c ON c.animal_id = a.id
                   INNER JOIN weight w ON w.animal_id = a.id
                   INNER JOIN measurement m ON w.id = m.id
                   INNER JOIN (
                                SELECT ww.animal_id,
                                  MIN(ABS(DATE_PART('day', mm.measurement_date - aa.date_of_birth)-$medianAgeInDays)) as min_days
                                FROM animal aa
                                  INNER JOIN weight ww ON aa.id = ww.animal_id
                                  INNER JOIN measurement mm ON ww.id = mm.id
                                WHERE is_active AND ww.is_revoked = false AND ww.is_birth_weight = false
                                      AND ".$minWeightValue." <= ww.weight
                                      AND ww.weight <= ".$maxWeightValue."
                                      AND ".$minAgeInDays." <= DATE_PART('day', mm.measurement_date - aa.date_of_birth)
                                      AND DATE_PART('day', mm.measurement_date - aa.date_of_birth) <= ".$maxAgeInDays."
                                      ".$animalIdFilterString."
                                GROUP BY ww.animal_id
                              )g ON g.animal_id = a.id
                 WHERE ABS(DATE_PART('day', m.measurement_date - a.date_of_birth)-$medianAgeInDays) = g.min_days
                       AND m.is_active AND w.is_revoked = false AND w.is_birth_weight = false
                 GROUP BY w.animal_id, min_days
                 )gg ON max_weight = w.weight AND gg.animal_id = w.animal_id
                 AND ABS(DATE_PART('day', m.measurement_date - a.date_of_birth)-$medianAgeInDays) = days_deviation_from_target_days";

        $sqlUpdateToNonBlank = "WITH rows AS (
                                    UPDATE animal_cache SET $weightColumnName = v.$weightColumnName, $dateColumnName = v.measurement_date,
                                    log_date = NOW(), $ageColumnName = DATE_PART('day', v.measurement_date - v.date_of_birth)
                                    FROM (
                                            -- select the measurement with the lowest measurement_date if all else is equal
                                            SELECT
                                               w.weight AS $weightColumnName,
                                               w.animal_id,
                                               m.measurement_date,
                                               a.date_of_birth
                                             FROM weight w
                                               INNER JOIN animal_cache c ON c.animal_id = w.animal_id
                                               INNER JOIN measurement m ON m.id = w.id
                                               INNER JOIN animal a ON a.id = w.animal_id
                                               INNER JOIN (
                                                 SELECT
                                                   w.weight,
                                                   w.animal_id,
                                                   MIN(m.measurement_date) as min_measurement_date,
                                                   a.date_of_birth
                                                 ".$sqlBase."
                                                 GROUP BY w.animal_id, w.weight, a.date_of_birth, days_deviation_from_target_days
                                             )ggg ON w.weight = ggg.weight AND w.animal_id = ggg.animal_id AND m.measurement_date = ggg.min_measurement_date
                                         ) AS v($weightColumnName, animal_id, measurement_date, date_of_birth)
                                    WHERE animal_cache.animal_id = v.animal_id AND
                                        (
                                            animal_cache.$weightColumnName ISNULL OR
                                            animal_cache.$weightColumnName <> v.$weightColumnName OR
                                            animal_cache.$dateColumnName ISNULL OR
                                            animal_cache.$dateColumnName <> v.measurement_date OR
                                            animal_cache.$ageColumnName ISNULL OR
                                            animal_cache.$ageColumnName <> DATE_PART('day', v.measurement_date - v.date_of_birth)
                                        )
                                    RETURNING 1
                                )
                                SELECT COUNT(*) AS count FROM rows;";
        $updateCountNonBlank = $conn->query($sqlUpdateToNonBlank)->fetch()['count'];
        $updateCount += $updateCountNonBlank;

        $sqlMakeBlank = "WITH rows AS (
                            UPDATE animal_cache SET $weightColumnName = NULL, $dateColumnName = NULL, $ageColumnName = NULL,
                            log_date = NOW() 
                            WHERE (animal_cache.$weightColumnName NOTNULL OR animal_cache.$dateColumnName NOTNULL OR 
                                  animal_cache.$ageColumnName NOTNULL)
                                  ".$animalIdFilterString2."
                                  AND animal_cache.animal_id NOT IN (
                                  SELECT w.animal_id
                                  ".$sqlBase."
                            )
                            RETURNING 1
                        )
                        SELECT COUNT(*) AS count FROM rows;";
        $updateCountBlank = $conn->query($sqlMakeBlank)->fetch()['count'];
        $updateCount += $updateCountBlank;

        return $updateCount;
    }
}