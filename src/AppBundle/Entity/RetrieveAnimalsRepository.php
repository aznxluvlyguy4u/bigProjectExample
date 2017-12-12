<?php

namespace AppBundle\Entity;

use AppBundle\Util\ArrayUtil;

/**
 * Class RetrieveAnimalsRepository
 * @package AppBundle\Entity
 */
class RetrieveAnimalsRepository extends BaseRepository
{
    /**
     * @param Location $location
     * @return RetrieveAnimals|null
     */
    public function getLatestRvoLeadingRetrieveAnimals(Location $location)
    {
        if ($location === null || !is_int($location->getId())) {
            return null;
        }

        $em = $this->getEntityManager();
        $queryBuilder = $em->createQueryBuilder();

        $queryBuilder
            ->select('r')
            ->from (RetrieveAnimals::class, 'r')
            ->where($queryBuilder->expr()->andX(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('r.location', $location->getId()),
                    $queryBuilder->expr()->eq('r.isRvoLeading', 'true')
                )
            ))
            ->orderBy('r.logDate', 'DESC')
            ->getFirstResult()
        ;

        $query = $queryBuilder->getQuery();
        $result = $query->getResult();

        if ($result) {
            return ArrayUtil::firstValue($result);
        }

        return null;
    }
}