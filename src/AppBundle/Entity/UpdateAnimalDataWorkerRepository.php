<?php

namespace AppBundle\Entity;
use Doctrine\Common\Collections\Criteria;

/**
 * Class UpdateAnimalDataWorkerRepository
 * @package AppBundle\Entity
 */
class UpdateAnimalDataWorkerRepository extends BaseRepository {

    /**
     * @param int|null $limit
     * @return array|mixed
     */
    function getTasks(?int $limit = null)
    {
        if ($limit < 1) {
            return [];
        }

        $qb = $this->getManager()->createQueryBuilder();
        $qb->select('w')
            ->from(UpdateAnimalDataWorker::class, 'w')
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