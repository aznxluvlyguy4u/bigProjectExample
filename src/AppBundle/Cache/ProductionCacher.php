<?php


namespace AppBundle\Cache;


use AppBundle\Enumerator\RequestStateType;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\DBAL\Connection;

class ProductionCacher
{
    /**
     * @param Connection $conn
     * @return int
     */
    public static function updateAllProductionValues(Connection $conn)
    {
        return self::updateProductionValues($conn, '');
    }


    /**
     * @param Connection $conn
     * @param array $animalIds
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function updateProductionValues(Connection $conn, $animalIds)
    {
        $updateCount = 0;

        $animalIdFilterString = "";
        if(is_array($animalIds)) {
            if(count($animalIds) == 0) {
                return $updateCount;
            }
            else {
                $animalIdFilterString = " AND (".SqlUtil::getFilterStringByIdsArray($animalIds,'a.id').") ";
            }
        } elseif($animalIds != null) {
            return $updateCount;
        }

        $sql = "WITH rows AS (
                  UPDATE animal_cache
                  SET
                    production_age             = v.production_age,
                    litter_count               = v.litter_count,
                    total_offspring_count      = v.total_born_count,
                    born_alive_offspring_count = v.born_alive_count,
                    gave_birth_as_one_year_old = v.gave_birth_as_one_year_old,
                    log_date = '".TimeUtil::getTimeStampNow()."'
                  FROM (
                         SELECT DISTINCT
                           (a.id)                                           AS animal_id,
                           MAX(c.id)                                        AS animal_cache_id,
                           EXTRACT(YEAR FROM AGE(MAX(l.litter_date), MAX(a.date_of_birth))) + --get years
                           ROUND(CAST(EXTRACT(MONTH FROM AGE(MAX(l.litter_date), MAX(a.date_of_birth))) AS DOUBLE PRECISION) /
                                 11) --add year if months >= 6
                                                                            AS age_in_nsfo_system,
                           COUNT(l.id)                                      AS litter_count,
                           SUM(l.born_alive_count) + SUM(l.stillborn_count) AS total_born_count,
                           SUM(l.born_alive_count)                          AS born_alive_count,
                           FALSE                                            AS has_one_year_mark,
                           --fathers never get a one-year-mark
                           (
                             MAX(c.production_age) <> EXTRACT(YEAR FROM AGE(MAX(l.litter_date), MAX(a.date_of_birth))) + --get years
                                                      ROUND(CAST(EXTRACT(MONTH FROM AGE(MAX(l.litter_date), MAX(a.date_of_birth))) AS
                                                                 DOUBLE PRECISION) / 11) --add year if months >= 6
                             OR MAX(c.litter_count) <> COUNT(l.id)
                             OR MAX(c.total_offspring_count) <> SUM(l.born_alive_count) + SUM(l.stillborn_count)
                             OR MAX(c.born_alive_offspring_count) <> SUM(l.born_alive_count)
                             OR BOOL_AND(c.gave_birth_as_one_year_old) <> FALSE
                             OR MAX(c.production_age) ISNULL OR MAX(c.litter_count) ISNULL OR MAX(c.total_offspring_count) ISNULL OR
                             MAX(c.born_alive_offspring_count) ISNULL
                           )                                                AS update_production
                
                         FROM animal a
                           INNER JOIN litter l ON a.id = l.animal_father_id
                           INNER JOIN animal_cache c ON c.animal_id = a.id
                         WHERE date_of_birth NOTNULL AND l.status <> '".RequestStateType::REVOKED."' ".$animalIdFilterString."
                         GROUP BY a.id
                
                         UNION
                
                         SELECT DISTINCT
                           (a.id)                                           AS animal_id,
                           MAX(c.id)                                        AS animal_cache_id,
                           EXTRACT(YEAR FROM AGE(MAX(l.litter_date), MAX(a.date_of_birth))) + --get years
                           ROUND(CAST(EXTRACT(MONTH FROM AGE(MAX(l.litter_date), MAX(a.date_of_birth))) AS DOUBLE PRECISION) /
                                 11) --add year if months >= 6
                                                                            AS age_in_nsfo_system,
                           COUNT(l.id)                                      AS litter_count,
                           SUM(l.born_alive_count) + SUM(l.stillborn_count) AS total_born_count,
                           SUM(l.born_alive_count)                          AS born_alive_count,
                
                           EXTRACT(YEAR FROM AGE(MIN(l.litter_date), MAX(a.date_of_birth))) * 12 + --get all as months
                           EXTRACT(MONTH FROM AGE(MIN(l.litter_date), MAX(a.date_of_birth))) <= 18 AND a.gender = 'FEMALE'
                                                                            AS has_one_year_mark,
                           (
                             MAX(c.production_age) <> EXTRACT(YEAR FROM AGE(MAX(l.litter_date), MAX(a.date_of_birth))) + --get years
                                                      ROUND(CAST(EXTRACT(MONTH FROM AGE(MAX(l.litter_date), MAX(a.date_of_birth))) AS
                                                                 DOUBLE PRECISION) / 11) --add year if months >= 6
                             OR MAX(c.litter_count) <> COUNT(l.id)
                             OR MAX(c.total_offspring_count) <> SUM(l.born_alive_count) + SUM(l.stillborn_count)
                             OR MAX(c.born_alive_offspring_count) <> SUM(l.born_alive_count)
                             OR BOOL_AND(c.gave_birth_as_one_year_old) <>
                                (EXTRACT(YEAR FROM AGE(MIN(l.litter_date), MAX(a.date_of_birth))) * 12 + --get all as months
                                 EXTRACT(MONTH FROM AGE(MIN(l.litter_date), MAX(a.date_of_birth))) <= 18 AND a.gender = 'FEMALE')
                             OR MAX(c.production_age) ISNULL OR MAX(c.litter_count) ISNULL OR MAX(c.total_offspring_count) ISNULL OR
                             MAX(c.born_alive_offspring_count) ISNULL
                           )                                                AS update_production
                
                         FROM animal a
                           INNER JOIN litter l ON a.id = l.animal_mother_id
                           INNER JOIN animal_cache c ON c.animal_id = a.id
                         WHERE date_of_birth NOTNULL AND l.status <> '".RequestStateType::REVOKED."' ".$animalIdFilterString."
                         GROUP BY a.id
                         UNION --Below when date of births are null
                
                         SELECT DISTINCT
                           (a.id)                                           AS animal_id,
                           MAX(c.id)                                        AS animal_cache_id,
                           0                                                AS age_in_nsfo_system,
                           COUNT(l.id)                                      AS litter_count,
                           SUM(l.born_alive_count) + SUM(l.stillborn_count) AS total_born_count,
                           SUM(l.born_alive_count)                          AS born_alive_count,
                           FALSE                                            AS has_one_year_mark,
                           --fathers never get a one-year-mark
                           (
                             MAX(c.production_age) <> 0
                             OR MAX(c.litter_count) <> COUNT(l.id)
                             OR MAX(c.total_offspring_count) <> SUM(l.born_alive_count) + SUM(l.stillborn_count)
                             OR MAX(c.born_alive_offspring_count) <> SUM(l.born_alive_count)
                             OR BOOL_AND(c.gave_birth_as_one_year_old) <> FALSE
                             OR MAX(c.production_age) ISNULL OR MAX(c.litter_count) ISNULL OR MAX(c.total_offspring_count) ISNULL OR
                             MAX(c.born_alive_offspring_count) ISNULL
                           )                                                AS update_production
                
                         FROM animal a
                           INNER JOIN litter l ON a.id = l.animal_father_id
                           INNER JOIN animal_cache c ON c.animal_id = a.id
                         WHERE date_of_birth ISNULL AND l.status <> '".RequestStateType::REVOKED."' ".$animalIdFilterString."
                         GROUP BY a.id
                
                         UNION
                
                         SELECT DISTINCT
                           (a.id)                                           AS animal_id,
                           MAX(c.id)                                        AS animal_cache_id,
                           0                                                AS age_in_nsfo_system,
                           COUNT(l.id)                                      AS litter_count,
                           SUM(l.born_alive_count) + SUM(l.stillborn_count) AS total_born_count,
                           SUM(l.born_alive_count)                          AS born_alive_count,
                           FALSE                                            AS has_one_year_mark,
                           --dateOfBirth missing, cannot calculate this value
                           (
                             MAX(c.production_age) <> 0
                             OR MAX(c.litter_count) <> COUNT(l.id)
                             OR MAX(c.total_offspring_count) <> SUM(l.born_alive_count) + SUM(l.stillborn_count)
                             OR MAX(c.born_alive_offspring_count) <> SUM(l.born_alive_count)
                             OR BOOL_AND(c.gave_birth_as_one_year_old) <> FALSE
                             OR MAX(c.production_age) ISNULL OR MAX(c.litter_count) ISNULL OR MAX(c.total_offspring_count) ISNULL OR
                             MAX(c.born_alive_offspring_count) ISNULL
                           )                                                AS update_production
                
                         FROM animal a
                           INNER JOIN litter l ON a.id = l.animal_mother_id
                           INNER JOIN animal_cache c ON c.animal_id = a.id
                         WHERE date_of_birth ISNULL AND l.status <> '".RequestStateType::REVOKED."' ".$animalIdFilterString."
                         GROUP BY a.id
                         
                         UNION

                         SELECT
                           a.id    AS animal_id,
                           g.animal_cache_id AS animal_cache_id,
                           NULL      AS age_in_nsfo_system,
                           NULL      AS litter_count,
                           NULL      AS total_born_count,
                           NULL      AS born_alive_count,
                           FALSE     AS has_one_year_mark,
                           TRUE      AS update_production
                         FROM animal a
                           INNER JOIN animal_cache c ON c.animal_id = a.id
                           INNER JOIN (
                                        SELECT DISTINCT
                                          (a.id)    AS animal_id,
                                          MAX(c.id) AS animal_cache_id,
                                          COUNT(animal_father_id) = 0
                                          AND (
                                            MAX(c.production_age) NOTNULL OR
                                            MAX(c.litter_count) NOTNULL OR
                                            MAX(c.total_offspring_count) NOTNULL OR
                                            MAX(c.born_alive_offspring_count) NOTNULL OR
                                            BOOL_AND(c.gave_birth_as_one_year_old) = TRUE
                                          ) AS update_production
                                        FROM animal a
                                          INNER JOIN animal_cache c ON c.animal_id = a.id
                                          LEFT JOIN
                                          ( SELECT * FROM litter WHERE status <> '".RequestStateType::REVOKED."'
                                          )l ON l.animal_father_id = a.id
                                        WHERE a.type = 'Ram' ".$animalIdFilterString."
                                        GROUP BY a.id
                                      )g ON g.animal_id = a.id
                         WHERE g.update_production = TRUE
                
                         UNION
                
                         SELECT
                           a.id    AS animal_id,
                           g.animal_cache_id AS animal_cache_id,
                           NULL      AS age_in_nsfo_system,
                           NULL      AS litter_count,
                           NULL      AS total_born_count,
                           NULL      AS born_alive_count,
                           FALSE     AS has_one_year_mark,
                           TRUE      AS update_production
                         FROM animal a
                           INNER JOIN animal_cache c ON c.animal_id = a.id
                           INNER JOIN (
                                        SELECT DISTINCT
                                          (a.id)    AS animal_id,
                                          MAX(c.id) AS animal_cache_id,
                                          COUNT(animal_mother_id) = 0
                                          AND (
                                            MAX(c.production_age) NOTNULL OR
                                            MAX(c.litter_count) NOTNULL OR
                                            MAX(c.total_offspring_count) NOTNULL OR
                                            MAX(c.born_alive_offspring_count) NOTNULL OR
                                            BOOL_AND(c.gave_birth_as_one_year_old) = TRUE
                                          ) AS update_production
                                        FROM animal a
                                          INNER JOIN animal_cache c ON c.animal_id = a.id
                                          LEFT JOIN
                                          ( SELECT * FROM litter WHERE status <> '".RequestStateType::REVOKED."'
                                          )l ON l.animal_mother_id = a.id
                                        WHERE a.type = 'Ewe' ".$animalIdFilterString."
                                        GROUP BY a.id
                                      )g ON g.animal_id = a.id
                         WHERE g.update_production = TRUE
                         
                       ) AS v(animal_id, animal_cache_id, production_age, litter_count, total_born_count, born_alive_count, gave_birth_as_one_year_old, update_production)
                  WHERE v.update_production = TRUE AND animal_cache.id = v.animal_cache_id
                  RETURNING 1
                )
                SELECT COUNT(*) AS count FROM rows";
        $updateCount = $conn->query($sql)->fetch()['count'];
        return $updateCount;
    }
}