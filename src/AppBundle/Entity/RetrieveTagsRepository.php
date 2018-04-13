<?php

namespace AppBundle\Entity;
use AppBundle\Enumerator\RequestStateType;
use Doctrine\Common\Collections\Criteria;

/**
 * Class RetrieveTagsRepository
 * @package AppBundle\Entity
 */
class RetrieveTagsRepository extends BaseRepository {


    /**
     * @param Location $location
     * @return int|null
     */
    public function findLastManual(Location $location)
    {
        if (!$location || !$location->getId()) {
            return null;
        }

        $qb = $this->getManager()->createQueryBuilder();

        $qb
            ->select('r')
            ->from(RetrieveTags::class, 'r')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('r.location', $location->getId()),
                    $qb->expr()->gte('r.isManual', 'true')
                )
            )
            ->orderBy('r.id', Criteria::DESC)
            ->setMaxResults(1)
        ;

        $results = $qb->getQuery()->getResult();

        return count($results) === 0 ? null : array_shift($results);
    }

}