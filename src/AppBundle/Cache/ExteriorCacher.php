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
        return self::updateExteriors($conn, null);
    }


    /**
     * $animalIds == null: all exterior values in animalCache are updated
     * $animalIds count == 0; nothing is updated
     * $animalIds count > 0: only given animalIds are updated
     *
     * @param Connection $conn
     * @param array $animalIds
     * @return int
     */
    public static function updateExteriors(Connection $conn, $animalIds)
    {
        $updateCount = 0;

        $animalIdFilterString = "";
        if(is_array($animalIds)) {
            if(count($animalIds) == 0) {
                return $updateCount;
            }
            else {
                $animalIdFilterString = " AND (".SqlUtil::getFilterStringByIdsArray($animalIds,'xx.animal_id').")";
            }
        } elseif($animalIds != null) {
            return $updateCount;
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
                  log_date = '".TimeUtil::getTimeStampNow()."'
                FROM (
                  SELECT x.animal_id, x.skull, x.muscularity, x.proportion, x.exterior_type, x.leg_work, x.fur, x.general_appearance,
                    x.height, x.breast_depth, x.torso_length, x.markings, x.kind, x.progress, m.measurement_date
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
                    c.exterior_measurement_date ISNULL OR c.exterior_measurement_date <> m.measurement_date
                  )
                       -- AND a.location_id = 00000 < filter location_id here when necessary
                ) AS v(animal_id, skull, muscularity, proportion, exterior_type, leg_work, fur, general_appearance,
                height, breast_depth, torso_length, markings, kind, progress, measurement_date) WHERE animal_cache.animal_id = v.animal_id
                  RETURNING 1
                )
                SELECT COUNT(*) AS count FROM rows;";
        $updateCount = $conn->query($sql)->fetch()['count'];
        return $updateCount;
    }
}