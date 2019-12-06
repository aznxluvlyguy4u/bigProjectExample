<?php

namespace AppBundle\Entity;
use AppBundle\Service\TaskService;
use AppBundle\Util\DateUtil;
use Doctrine\Common\Collections\Criteria;

/**
 * Class UpdateAnimalDataWorkerRepository
 * @package AppBundle\Entity
 */
class UpdateAnimalDataWorkerRepository extends BaseRepository {

    /**
     * @param Person $user
     * @param int|null $limit
     * @return array|mixed
     * @throws \Exception
     */
    function getTasks(Person $user, ?int $limit = null)
    {
        if (!$user) {
            return [];
        }


        if ($limit && $limit < 1) {
            return [];
        }

        $qb = $this->getManager()->createQueryBuilder();
        $qb->select('w')
            ->from(UpdateAnimalDataWorker::class, 'w')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->gte('w.startedAt', DateUtil::getQueryBuilderFormat(TaskService::getMaxNonExpiredDate())),
                    $qb->expr()->isNotNull('w.finishedAt')
                )
            )
            ->orWhere(
                $qb->expr()->isNull('w.finishedAt')
            )
            ->orderBy('w.startedAt', Criteria::DESC)
            ->setMaxResults($limit)
        ;

        return $qb->getQuery()->getResult();
    }


    /**
     * @param string $hash
     * @return bool
     * @throws \Exception
     */
    function isSimilarNonExpiredTaskAlreadyInProgress($hash)
    {
        $qb = $this->getManager()->createQueryBuilder();
        $qb->select('w')
            ->from(UpdateAnimalDataWorker::class, 'w')
            ->where($qb->expr()->eq('w.hash', "'".$hash."'"))
            ->andWhere($qb->expr()->isNull('w.finishedAt'))
        ;

        $workerInProgress = $qb->getQuery()->getResult();

        return !empty($workerInProgress);
    }
}
