<?php


namespace AppBundle\Cache;


use AppBundle\Util\SqlUtil;
use Doctrine\DBAL\Connection;

/**
 * Class TailLengthCacher
 * @package AppBundle\Cache
 */
class TailLengthCacher
{
    /**
     * @param Connection $conn
     * @return int
     */
    public static function updateAll(Connection $conn) {
        return self::update($conn, null);
    }


    /**
     * @param Connection $conn
     * @param $animalIds
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function update(Connection $conn, $animalIds)
    {
        $updateCount = 0;

        $animalIdFilterString = "";
        $animalIdFilterString2 = "";
        if(is_array($animalIds)) {
            if(count($animalIds) == 0) {
                return $updateCount;
            }
            else {
                $animalIdFilterString = " AND (".SqlUtil::getFilterStringByIdsArray($animalIds,'tt.animal_id').")";
                $animalIdFilterString2 = " AND (".SqlUtil::getFilterStringByIdsArray($animalIds,'animal_id').")";
            }
        } elseif($animalIds != null) {
            return $updateCount;
        }

        $sqlBase = "FROM tail_length t
                  INNER JOIN measurement m ON t.id = m.id
                  INNER JOIN (
                               SELECT animal_id, MAX(log_date) as max_log_date
                               FROM tail_length tt
                                 INNER JOIN measurement mm ON tt.id = mm.id
                               --Remove is_revoked if column data is moved to is_active and variable is removed
                               WHERE mm.is_active ".$animalIdFilterString."
                               GROUP BY tt.animal_id
                   ) AS last ON last.animal_id = t.animal_id AND m.log_date = last.max_log_date";

        $sqlUpdateToNonBlank = "WITH rows AS (
                  UPDATE animal_cache SET
                        tail_length = v.tail_length,
                        log_date = NOW()
                  FROM (
                        SELECT t.animal_id, t.length
                        ".$sqlBase."
                        INNER JOIN animal_cache c ON c.animal_id = t.animal_id
                        INNER JOIN animal a ON t.animal_id = a.id
                        WHERE c.tail_length ISNULL OR c.tail_length <> t.length
                        -- AND a.location_id = 00000 < filter location_id here when necessary
                ) AS v(animal_id, tail_length) WHERE animal_cache.animal_id = v.animal_id
                RETURNING 1
                )
                SELECT COUNT(*) AS count FROM rows;";
        $updateCount = $conn->query($sqlUpdateToNonBlank)->fetch()['count'];

        $sqlMakeBlank = "WITH rows AS (
                  UPDATE animal_cache SET tail_length = NULL,
                    log_date = NOW()
                  WHERE animal_cache.tail_length NOTNULL
                  ".$animalIdFilterString2."
                  AND animal_cache.animal_id NOT IN (
                  SELECT t.animal_id
                    ".$sqlBase."
                  )
                  RETURNING 1
                )
                SELECT COUNT(*) AS count FROM rows;";
        $updateCount += $conn->query($sqlMakeBlank)->fetch()['count'];

        return $updateCount;
    }
}