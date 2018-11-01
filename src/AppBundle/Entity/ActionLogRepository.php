<?php

namespace AppBundle\Entity;

use AppBundle\Enumerator\JmsGroup;
use AppBundle\Service\BaseSerializer;
use AppBundle\Service\CacheService;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;

/**
 * Class ActionLogRepository
 * @package AppBundle\Entity
 */
class ActionLogRepository extends BaseRepository
{
    const ACTION_LOG_PERSONS_CACHE_ID = 'GET_ACTION_LOG_PERSONS';

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
     * @param BaseSerializer $serializer
     * @param CacheService $cacheService
     * @return array
     */
    public function getUserAccountPersonIds(BaseSerializer $serializer, CacheService $cacheService)
    {
        if ($cacheService->isHit(self::ACTION_LOG_PERSONS_CACHE_ID)) {
            return $cacheService->getItem(self::ACTION_LOG_PERSONS_CACHE_ID);
        }

        $persons = $this->getManager()->getRepository(Person::class)->findAll();
        $output = $serializer->getDecodedJson($persons, [JmsGroup::ADDRESS, JmsGroup::BASIC, JmsGroup::UBN]);
        $cacheService->set(self::ACTION_LOG_PERSONS_CACHE_ID, $output);

        return $output;
    }


    /**
     * @param array|int[] $primaryKeys
     * @param bool $setPrimaryKeysAsArrayKeys
     * @return ActionLog[]|array
     * @throws \Exception
     */
    public function findByIds(array $primaryKeys, $setPrimaryKeysAsArrayKeys = true): array
    {
        if (!$primaryKeys) {
            return [];
        }

        if (!ArrayUtil::containsOnlyDigits($primaryKeys)) {
            throw new \Exception('Array contains non integers: '.implode(',', $primaryKeys),
                Response::HTTP_PRECONDITION_FAILED);
        }

        $qb = $this->getManager()->createQueryBuilder();

        $qb->select('a','actionBy', 'userAccount')
            ->from(ActionLog::class, 'a')
            ->innerJoin('a.actionBy', 'actionBy', Join::WITH, $qb->expr()->eq('a.actionBy', 'actionBy.id'))
            ->innerJoin('a.userAccount', 'userAccount', Join::WITH, $qb->expr()->eq('a.userAccount', 'userAccount.id'))
        ;

        foreach ($primaryKeys as $primaryKey) {
            $qb->orWhere($qb->expr()->eq('a.id', $primaryKey));
        }

        $query = $qb->getQuery();

        $query->setFetchMode(Person::class, 'actionBy', ClassMetadata::FETCH_EAGER);
        $query->setFetchMode(Person::class, 'userAccount', ClassMetadata::FETCH_EAGER);

        $births = $query->getResult();

        return $setPrimaryKeysAsArrayKeys ? $this->setPrimaryKeysAsArrayKeys($births) : $births;
    }
}