<?php

namespace AppBundle\Entity;

use AppBundle\Util\SqlUtil;
use AppBundle\Util\TimeUtil;

/**
 * Class ActionLogRepository
 * @package AppBundle\Entity
 */
class ActionLogRepository extends BaseRepository
{

    /**
     * @param int $userAccountId
     * @return array
     */
    public function getUserActionTypes($userAccountId)
    {
        $filter = is_int($userAccountId) || ctype_digit($userAccountId) ? 'WHERE user_account_id = '.$userAccountId : '';
        $sql = "SELECT user_action_type FROM action_log $filter GROUP BY user_action_type ORDER BY user_action_type";
        $results = $this->getConnection()->query($sql)->fetchAll();
        return array_keys(SqlUtil::getSingleValueGroupedSqlResults('user_action_type', $results));
    }


    /**
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param string $userActionType
     * @param int $userAccountId
     * @return array
     */
    public function findByDateTypeAndUserId($startDate, $endDate, $userActionType, $userAccountId)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $startDateQuery = $startDate !== null
            ? $qb->expr()->gte('action_log.logDate', "'".TimeUtil::getTimeStampForSql($startDate)."'") : null;
        $endDateQuery = $endDate !== null
            ? $qb->expr()->lte('action_log.logDate', "'".TimeUtil::getTimeStampForSql($endDate)."'") : null;
        $userActionTypeQuery = $userActionType !== null
            ? $qb->expr()->eq('action_log.userActionType', "'".$userActionType."'") : null;
        $userAccountId = $userAccountId !== null
            ? $qb->expr()->eq('action_log.userAccount', $userAccountId) : null;

        $qb
            ->select('action_log')
            ->from ('AppBundle:ActionLog', 'action_log')
            ->where($qb->expr()->andX(
                $startDateQuery,
                $endDateQuery,
                $userActionTypeQuery,
                $userAccountId
            ));

        $query = $qb->getQuery();
        return $query->getResult();
    }

}