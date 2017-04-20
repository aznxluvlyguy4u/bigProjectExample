<?php


namespace AppBundle\Cache;
use AppBundle\Constant\MeasurementConstant;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\DBAL\Connection;

/**
 * Class AnimalWeightCacher
 *
 * @ORM\Entity(repositoryClass="AppBundle\Cache")
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
     * @param $animalIds
     * @return int
     */
    public static function updateWeights(Connection $conn, $animalIds){
        return
            self::updateLastWeights($conn, $animalIds)
            + self::updateBirthWeights($conn, $animalIds)
            ;
    }


    /**
     * @param Connection $conn
     * @param $animalIds
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function updateLastWeights(Connection $conn, $animalIds)
    {
        $updateCount = 0;

        $animalIdFilterString = "";
        if(is_array($animalIds)) {
            if(count($animalIds) == 0) {
                return $updateCount;
            }
            else {
                $animalIdFilterString = " AND (".SqlUtil::getFilterStringByIdsArray($animalIds,'ww.animal_id').")";
            }
        } elseif($animalIds != null) {
            return $updateCount;
        }

        $sql = "WITH rows AS (
                  UPDATE animal_cache SET
                    last_weight = v.last_weight,
                    weight_measurement_date = v.weight_measurement_date,
                    log_date = '".TimeUtil::getTimeStampNow()."'
                  FROM (
                         SELECT w.animal_id, w.weight, m.measurement_date
                         FROM weight w
                           INNER JOIN measurement m ON w.id = m.id
                           INNER JOIN (
                                        SELECT animal_id, MAX(measurement_date) as max_measurement_date,
                                          MAX(log_date) as max_log_date
                                        FROM weight ww
                                          INNER JOIN measurement mm ON ww.id = mm.id
                                          --Remove is_revoked if column data is moved to is_active and variable is removed
                                        WHERE ww.is_revoked = FALSE".$animalIdFilterString." --AND mm.is_active = TRUE
                                        GROUP BY animal_id
                       ) AS last ON last.animal_id = w.animal_id AND m.measurement_date = last.max_measurement_date
                           AND m.log_date = last.max_log_date
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
        $updateCount = $conn->query($sql)->fetch()['count'];

        /*
         *  Note in the rare case all weights are revoked, then this will not be updated in the animal_cache table.
         *  If this needs to be implemented, the sql queries for the other weights can be used as an example.
         */

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
        if(is_array($animalIds)) {
            if(count($animalIds) == 0) {
                return $updateCount;
            }
            else {
                $animalIdFilterString = " AND (".SqlUtil::getFilterStringByIdsArray($animalIds,'ww.animal_id').")";
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
                                UPDATE animal_cache SET birth_weight = v.birth_weight
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
                            UPDATE animal_cache SET birth_weight = NULL
                            WHERE animal_cache.birth_weight NOTNULL
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
}