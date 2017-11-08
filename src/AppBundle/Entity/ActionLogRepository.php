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
     * @param int $userAccountPersonId
     * @return array
     */
    public function getUserActionTypes($userAccountPersonId)
    {
        $filter = is_string($userAccountPersonId) || ctype_alnum($userAccountPersonId) ? "WHERE p.person_id = '".$userAccountPersonId."'" : "";
        $sql = "
            SELECT user_action_type
            FROM action_log
              LEFT JOIN person p ON p.id = action_log.user_account_id  
            $filter 
            GROUP BY user_action_type 
            ORDER BY user_action_type";
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

        $qb
            ->select('action_log')
            ->from ('AppBundle:ActionLog', 'action_log')
        ;

        $startDateQuery = $startDate !== null
            ? $qb->expr()->gte('action_log.logDate', "'".TimeUtil::getTimeStampForSql($startDate)."'") : null;
        $endDateQuery = $endDate !== null
            ? $qb->expr()->lte('action_log.logDate', "'".TimeUtil::getTimeStampForSql($endDate)."'") : null;
        $userActionTypeQuery = $userActionType !== null
            ? $qb->expr()->eq('action_log.userActionType', "'".$userActionType."'") : null;
        $userAccountIdQuery = $userAccountId !== null
            ? $qb->expr()->orX(
                $qb->expr()->eq('action_log.userAccount', $userAccountId),
                $qb->expr()->eq('action_log.actionBy', $userAccountId)
            ): null;

        if ($startDateQuery !== null || $endDateQuery !== null || $userActionTypeQuery !== null || $userAccountIdQuery !== null) {
            $qb->where($qb->expr()->andX(
                $startDateQuery,
                $endDateQuery,
                $userActionTypeQuery,
                $userAccountIdQuery
            ));
        }

        $query = $qb->getQuery();
        return $query->getResult();
    }


    /**
     * @return array
     */
    public function getUserAccountPersonIds()
    {
        $sql = "SELECT p.person_id, p.first_name, p.last_name, p.type, p.email_address, p.is_active
                FROM person p
                INNER JOIN (
                    SELECT user_account_id FROM action_log
                    WHERE user_account_id NOTNULL
                    GROUP BY user_account_id
                    )l ON p.id = l.user_account_id
                ORDER BY p.last_name";
        return $this->getConnection()->query($sql)->fetchAll();
    }
}