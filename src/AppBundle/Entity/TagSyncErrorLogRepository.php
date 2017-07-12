<?php

namespace AppBundle\Entity;

use AppBundle\Util\SqlUtil;

/**
 * Class TagSyncErrorLogRepository
 * @package AppBundle\Entity
 */
class TagSyncErrorLogRepository extends BaseRepository
{
    /**
     * @return array
     */
    public function listRetrieveAnimalIds()
    {
        $sql = "SELECT retrieve_tags_id, count(*) as count
                FROM tag_sync_error_log l
                  INNER JOIN animal a ON l.uln_number = a.uln_number AND l.uln_country_code = a.uln_country_code
                  WHERE l.is_fixed = FALSE
                GROUP BY retrieve_tags_id";
        $results = $this->getConnection()->query($sql)->fetchAll();
        return SqlUtil::groupSqlResultsOfKey1ByKey2('count', 'retrieve_tags_id', $results);
    }


    /**
     * @param $retrieveAnimalsId
     * @return string
     */
    public function getQueryFilterByRetrieveAnimalIds($retrieveAnimalsId)
    {
        $sql = "SELECT l.uln_country_code, l.uln_number
                FROM tag_sync_error_log l
                  INNER JOIN animal a ON l.uln_number = a.uln_number AND l.uln_country_code = a.uln_country_code
                WHERE l.is_fixed = FALSE AND retrieve_tags_id = ".$retrieveAnimalsId;
        $results = $this->getConnection()->query($sql)->fetchAll();
        return SqlUtil::getUlnQueryFilter($results);
    }
}
