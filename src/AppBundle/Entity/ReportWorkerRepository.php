<?php

namespace AppBundle\Entity;
use AppBundle\Util\DateUtil;
use Doctrine\Common\Collections\Criteria;

/**
 * Class ReportWorkerRepository
 * @package AppBundle\Entity
 */
class ReportWorkerRepository extends BaseRepository {

    /**
     * @param Person $user
     * @param Person|null $accountOwner
     * @return ReportWorker[]|array
     * @throws \Exception
     */
    function getReports(Person $user, ?Person $accountOwner)
    {
        if (!$user) {
            return [];
        }

        $date = new \DateTime();//now
        $interval = new \DateInterval('P1D');// P[eriod] 1 D[ay]
        $date->sub($interval);
        $isAdminEnvironment = $accountOwner == null;

        $qb = $this->getManager()->createQueryBuilder();
        $qb->select('w')
            ->from(ReportWorker::class, 'w')
            ->where(
                $qb->expr()->eq('w.actionBy', $user->getId())
            )
            ->andWhere($qb->expr()->gte('w.startedAt', DateUtil::getQueryBuilderFormat($date)))
            ->orderBy('w.startedAt', Criteria::DESC)
        ;

        if($isAdminEnvironment)
            $qb->andWhere($qb->expr()->isNull('w.owner'));
        else
            $qb->andWhere($qb->expr()->eq('w.owner', $accountOwner->getId()));

        return $qb->getQuery()->getResult();
    }
}