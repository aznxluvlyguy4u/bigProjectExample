<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Query\Expr\Join;

/**
 * Class BreedIndexRepository
 * @package AppBundle\Entity
 */
class BreedIndexRepository extends BaseRepository {

    /**
     * @param \DateTime $generationDate
     * @param bool $isIncludingOnlyAliveAnimals
     * @param string $breedIndexClass
     * @return array
     */
    protected function getBreedIndexValues($generationDate, $isIncludingOnlyAliveAnimals, $breedIndexClass)
    {
        $qb = $this->getManager()->createQueryBuilder();

        $qbBase = $qb
            ->select('b')
            ->from ($breedIndexClass, 'b')
        ;

        if ($isIncludingOnlyAliveAnimals) {
            $qbBase
                ->innerJoin('b.animal', 'a', Join::WITH, $qb->expr()->eq('b.animal', 'a.id'))
                ->where(
                    $qb->expr()->eq('a.isAlive', 'true')
                );
        }

        $qbBase
            ->andWhere(
                $qb->expr()->eq('b.generationDate', "'".($generationDate->format('Y-m-d'))."'")
            );

        return $qb->getQuery()->getResult();
    }

}