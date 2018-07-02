<?php

namespace AppBundle\Entity;
use AppBundle\Service\ReportService;
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

        $isAdminEnvironment = $accountOwner == null;

        $qb = $this->getManager()->createQueryBuilder();
        $qb->select('w')
            ->from(ReportWorker::class, 'w')
            ->where(
                $qb->expr()->eq('w.actionBy', $user->getId())
            )
            ->andWhere($qb->expr()->gte('w.startedAt', DateUtil::getQueryBuilderFormat(ReportService::getMaxNonExpiredDate())))
            ->orderBy('w.startedAt', Criteria::DESC)
        ;

        if($isAdminEnvironment)
            $qb->andWhere($qb->expr()->isNull('w.owner'));
        else
            $qb->andWhere($qb->expr()->eq('w.owner', $accountOwner->getId()));

        return $qb->getQuery()->getResult();
    }


    /**
     * @param string $hash
     * @return bool
     * @throws \Exception
     */
    function isSimilarNonExpiredReportAlreadyInProgress($hash)
    {
        $qb = $this->getManager()->createQueryBuilder();
        $workerInProgress = $qb->select('w')
            ->from(ReportWorker::class, 'w')
            ->where($qb->expr()->eq('w.hash', $hash))
            ->andWhere($qb->expr()->isNull('w.finishedAt'))
            ->andWhere($qb->expr()->gte('w.startedAt', DateUtil::getQueryBuilderFormat(ReportService::getMaxNonExpiredDate())))
        ;

        return !empty($workerInProgress);
    }
}