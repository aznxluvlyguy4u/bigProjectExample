<?php

namespace AppBundle\Entity;

use AppBundle\Util\SqlUtil;

/**
 * Class ActionLogRepository
 * @package AppBundle\Entity
 */
class ActionLogRepository extends BaseRepository
{

    /**
     * @return array
     */
    public function getUserActionTypes()
    {
        $sql = "SELECT user_action_type FROM action_log GROUP BY user_action_type ORDER BY user_action_type";
        $results = $this->getConnection()->query($sql)->fetchAll();
        return array_keys(SqlUtil::getSingleValueGroupedSqlResults('user_action_type', $results));
    }

}