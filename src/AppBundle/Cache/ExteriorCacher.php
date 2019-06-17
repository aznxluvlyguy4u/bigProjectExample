<?php


namespace AppBundle\Cache;


use AppBundle\Util\SqlUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\DBAL\Connection;

class ExteriorCacher
{
    /**
     * @param Connection $conn
     * @return int
     */
    public static function updateAllExteriors(Connection $conn){
        return self::updateExteriors($conn, [], true);
    }


    /**
     * $processAllRecords == true  && $animalIds == []      : all exterior values in animalCache are updated
     * $processAllRecords == false && $animalIds count == 0 : nothing is updated
     * $processAllRecords == false && $animalIds count > 0  : only given animalIds are updated
     *
     * @param Connection $conn
     * @param array $animalIds
     * @param boolean $processAllRecords
     * @return int
     */
    public static function updateExteriors(Connection $conn, $animalIds, $processAllRecords = false)
    {
        if (!self::isInputValid($animalIds, $processAllRecords)) {
            return 0;
        }

        return
            self::updateForAnimalsWithoutActiveExteriors($conn, $animalIds, $processAllRecords) +
            self::updateForAnimalsWithActiveExteriors($conn, $animalIds, $processAllRecords)
            ;
    }


    /**
     * @param $animalIds
     * @param $processAllRecords
     * @return bool
     */
    private static function isInputValid($animalIds, $processAllRecords): bool {
        if (is_bool($processAllRecords) && $processAllRecords) {
            $isValid = true;
        } elseif(is_array($animalIds) && !empty($animalIds)) {
            $isValid = true;
        } else {
            $isValid = false;
        }
        return $isValid;
    }


    /**
     * @param Connection $conn
     * @param $animalIds
     * @param $processAllRecords
     * @return mixed
     */
    private static function updateForAnimalsWithActiveExteriors(Connection $conn, $animalIds, $processAllRecords)
    {
        if ($processAllRecords) {
            $animalIdFilterString = "";
        } else {
            $animalIdFilterString = " AND (".SqlUtil::getFilterStringByIdsArray($animalIds,'xx.animal_id').")";
        }

        $sql = "WITH rows AS (
                UPDATE animal_cache SET
                  skull = v.skull,
                  muscularity = v.muscularity,
                  proportion = v.proportion,
                  exterior_type = v.exterior_type,
                  leg_work = v.leg_work,
                  fur = v.fur,
                  general_appearance = v.general_appearance,
                  height = v.height,
                  breast_depth = v.breast_depth,
                  torso_length = v.torso_length,
                  markings = v.markings,
                  kind = v.kind,
                  progress = v.progress,
                  exterior_measurement_date = v.measurement_date,
                  exterior_inspector_id = v.inspector_id,
                  log_date = '".TimeUtil::getTimeStampNow()."'
                FROM (
                  SELECT x.animal_id, x.skull, x.muscularity, x.proportion, x.exterior_type, x.leg_work, x.fur, x.general_appearance,
                    x.height, x.breast_depth, x.torso_length, x.markings, x.kind, x.progress, m.measurement_date, m.inspector_id
                  FROM exterior x
                  INNER JOIN measurement m ON x.id = m.id
                  INNER JOIN (
                    SELECT animal_id, MAX(measurement_date) as max_measurement_date
                    FROM exterior xx
                      INNER JOIN measurement mm ON xx.id = mm.id
                      WHERE mm.is_active = TRUE".$animalIdFilterString."
                    GROUP BY animal_id
                  ) AS last ON last.animal_id = x.animal_id AND m.measurement_date = last.max_measurement_date
                  INNER JOIN animal_cache c ON c.animal_id = x.animal_id
                  INNER JOIN animal a ON x.animal_id = a.id
                  WHERE (
                    c.skull ISNULL OR c.skull <> x.skull OR
                    c.muscularity ISNULL OR c.muscularity <> x.muscularity OR
                    c.proportion ISNULL OR c.proportion <> x.proportion OR
                    c.exterior_type ISNULL OR c.exterior_type <> x.exterior_type OR
                    c.leg_work ISNULL OR c.leg_work <> x.leg_work OR
                    c.fur ISNULL OR c.fur <> x.fur OR
                    c.general_appearance ISNULL OR c.general_appearance <> x.general_appearance OR
                    c.height ISNULL OR c.height <> x.height OR
                    c.breast_depth ISNULL OR c.breast_depth <> x.breast_depth OR
                    c.torso_length ISNULL OR c.torso_length <> x.torso_length OR
                    c.markings ISNULL OR c.markings <> x.markings OR
                    c.kind <> x.kind OR (c.kind ISNULL AND x.kind NOTNULL) OR
                    c.progress ISNULL OR c.progress <> x.progress OR
                    c.exterior_measurement_date ISNULL OR c.exterior_measurement_date <> m.measurement_date OR
                    (c.exterior_inspector_id ISNULL AND m.inspector_id NOTNULL) OR
                    (c.exterior_inspector_id NOTNULL AND m.inspector_id ISNULL) OR
                    c.exterior_inspector_id <> m.inspector_id
                  )
                ) AS v(animal_id, skull, muscularity, proportion, exterior_type, leg_work, fur, general_appearance,
                height, breast_depth, torso_length, markings, kind, progress, measurement_date, inspector_id) WHERE animal_cache.animal_id = v.animal_id
                  RETURNING 1
                )
                SELECT COUNT(*) AS count FROM rows;";
        return $conn->query($sql)->fetch()['count'];
    }


    /**
     * @param Connection $conn
     * @param $animalIds
     * @param $processAllRecords
     * @return mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    private static function updateForAnimalsWithoutActiveExteriors(Connection $conn, $animalIds, $processAllRecords)
    {
        if ($processAllRecords) {
            $animalIdFilterString1 = "";
            $animalIdFilterString2 = "";
        } else {
            $animalIdsString = " (".SqlUtil::getFilterStringByIdsArray($animalIds,'a.id').")";
            $animalIdFilterString1 = " AND ".$animalIdsString;
            $animalIdFilterString2 = " WHERE ".$animalIdsString;
        }

        $sql = "WITH rows AS (
                UPDATE animal_cache SET
                    skull = null,
                    muscularity = null,
                    proportion = null,
                    exterior_type = null,
                    leg_work = null,
                    fur = null,
                    general_appearance = null,
                    height = null,
                    breast_depth = null,
                    torso_length = null,
                    markings = null,
                    kind = null,
                    progress = null,
                    exterior_measurement_date = null,
                    exterior_inspector_id = null,
                    log_date = '".TimeUtil::getTimeStampNow()."'
                    FROM (
                        SELECT
                            a.id,
                            COALESCE(xcount.value,0) as active_exterior_count,
                            c.skull NOTNULL OR
                            c.muscularity NOTNULL OR
                            c.proportion NOTNULL OR
                            c.exterior_type NOTNULL OR
                            c.leg_work NOTNULL OR
                            c.fur NOTNULL OR
                            c.general_appearance NOTNULL OR
                            c.height NOTNULL OR
                            c.breast_depth NOTNULL OR
                            c.torso_length NOTNULL OR
                            c.markings NOTNULL OR
                            c.kind NOTNULL OR
                            c.progress NOTNULL OR
                            c.exterior_measurement_date NOTNULL OR
                            c.exterior_inspector_id NOTNULL as has_exterior_cache_values
                        FROM animal a
                                 INNER JOIN animal_cache c ON c.animal_id = a.id
                                 LEFT JOIN (
                            SELECT
                                COUNT(a.id) as value,
                                a.id as animal_id
                            FROM exterior x
                                     INNER JOIN measurement m ON x.id = m.id
                                     INNER JOIN animal a on x.animal_id = a.id
                            WHERE m.is_active = TRUE".$animalIdFilterString1."
                            GROUP BY a.id
                        )xcount ON xcount.animal_id = a.id
                        ".$animalIdFilterString2."
                    ) AS v(animal_id) WHERE
                        animal_cache.animal_id = v.animal_id AND
                        active_exterior_count = 0 AND has_exterior_cache_values
                    RETURNING 1
            )
            SELECT COUNT(*) AS count FROM rows";
        return $conn->query($sql)->fetch()['count'];
    }
}