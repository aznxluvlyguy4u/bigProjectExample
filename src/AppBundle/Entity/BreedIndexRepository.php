<?php

namespace AppBundle\Entity;

use AppBundle\Util\SqlUtil;
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
    protected function getBreedIndexes($generationDate, $isIncludingOnlyAliveAnimals, $breedIndexClass)
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
                $qb->expr()->eq('b.generationDate', "'".($generationDate->format(SqlUtil::DATE_FORMAT))."'")
            );

        return $qb->getQuery()->getResult();
    }


    /**
     * @param string|\DateTime $generationDate
     * @param boolean $isIncludingOnlyAliveAnimals
     * @param string $breedIndexType
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getBreedIndexValues($generationDate, $isIncludingOnlyAliveAnimals, $breedIndexType)
    {
        $generationDateString = $generationDate instanceof \DateTime ? $generationDate->format(SqlUtil::DATE_FORMAT) : $generationDate;

        $animalJoin = $isIncludingOnlyAliveAnimals ? 'INNER JOIN animal a ON b.animal_id = a.id': '';
        $animalIsAliveFilter = $isIncludingOnlyAliveAnimals ? 'AND a.is_alive = TRUE' : '';

        $sql = "SELECT
                  b.index
                FROM breed_index b
                ".$animalJoin."
                WHERE b.generation_date = '".$generationDateString."' AND b.type = '".$breedIndexType."' ".$animalIsAliveFilter;
        return $this->getConnection()->query($sql)->fetchAll();
    }
}