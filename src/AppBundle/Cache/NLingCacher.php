<?php


namespace AppBundle\Cache;


use AppBundle\Util\SqlUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\DBAL\Connection;

class NLingCacher
{
    /**
     * @param Connection $conn
     * @return int
     */
    public static function updateAllNLingValues(Connection $conn)
    {
        return self::updateNLingValues($conn, '');
    }


    /**
     * @param Connection $conn
     * @param array $animalIds
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function updateNLingValues(Connection $conn, $animalIds)
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
                SET n_ling = v.new_n_ling,
                    log_date = '".TimeUtil::getTimeStampNow()."'
                FROM (
                       -- nLing, litter still linked and not revoked
                       SELECT c.id as cache_id, c.n_ling as current_n_ling, CONCAT(l.born_alive_count + l.stillborn_count,'-ling') as new_n_ling,
                         (c.n_ling <> CONCAT(l.born_alive_count + l.stillborn_count,'-ling') OR c.n_ling ISNULL ) as update_n_ling
                       FROM animal a
                         INNER JOIN litter l ON a.litter_id = l.id
                         INNER JOIN animal_cache c ON c.animal_id = a.id
                       WHERE (l.status <> 'REVOKED' AND l.animal_mother_id NOTNULL)
                             AND (c.n_ling <> CONCAT(l.born_alive_count + l.stillborn_count,'-ling') OR c.n_ling ISNULL )
                             ".$animalIdFilterString."
                       UNION
                       -- nLing, litter still linked but revoked or mother not set
                       SELECT c.id as cache_id, c.n_ling as current_n_ling, '0-ling' as new_n_ling,
                         (c.n_ling <> '0-ling' OR c.n_ling ISNULL ) as update_n_ling
                       FROM animal a
                         INNER JOIN litter l ON a.litter_id = l.id
                         INNER JOIN animal_cache c ON c.animal_id = a.id
                       WHERE (l.status = 'REVOKED' OR l.animal_mother_id ISNULL) --If mother ISNULL the offspringCounts <> nLing
                             AND (c.n_ling <> '0-ling'  OR c.n_ling ISNULL ) --the default value for unknown nLings should be '0-ling'
                             ".$animalIdFilterString."
                       UNION
                       -- nLing, litter not linked anymore
                       SELECT c.id as cache_id, c.n_ling as current_n_ling, '0-ling' as new_n_ling,
                         (c.n_ling <> '0-ling' OR c.n_ling ISNULL ) as update_n_ling
                       FROM animal a
                         LEFT JOIN litter l ON a.litter_id = l.id
                         INNER JOIN animal_cache c ON c.animal_id = a.id
                       WHERE l.id ISNULL
                             AND (c.n_ling <> '0-ling'  OR c.n_ling ISNULL ) --the default value for unknown nLings should be '0-ling'
                             ".$animalIdFilterString."
                     ) AS v(cache_id, current_n_ling, new_n_ling, update_n_ling) WHERE animal_cache.id = v.cache_id AND v.update_n_ling = TRUE
                  RETURNING 1
                )
                SELECT COUNT(*) AS count FROM rows";
        $updateCount = $conn->query($sql)->fetch()['count'];
        return $updateCount;
    }
}