<?php

namespace AppBundle\Entity;
use AppBundle\Constant\BreedValueTypeConstant;
use Doctrine\ORM\Query\Expr\Join;

/**
 * Class BreedValueGeneticBaseRepository
 * @package AppBundle\Entity
 */
class BreedValueGeneticBaseRepository extends BaseRepository
{

    /**
     * @param int|string $year
     * @return array|BreedValueGeneticBase[]
     */
    public function getLambMeatIndexBasesByYear($year)
    {
        if (!ctype_digit($year) && !is_int($year)) {
            return [];
        }

        $qb = $this->getManager()->createQueryBuilder();

        $qb->select('b')
            ->from(BreedValueGeneticBase::class, 'b')
            ->innerJoin('b.breedValueType', 't', Join::WITH, $qb->expr()->eq('b.breedValueType', 't.id'))
            ->where($qb->expr()->eq('b.year', $year))
            ->andWhere($qb->expr()->orX(
                $qb->expr()->eq('t.nl', "'".BreedValueTypeConstant::GROWTH."'"),
                $qb->expr()->eq('t.nl', "'".BreedValueTypeConstant::MUSCLE_THICKNESS."'"),
                $qb->expr()->eq('t.nl', "'".BreedValueTypeConstant::FAT_THICKNESS_3."'")
            ))
        ;

        return $qb->getQuery()->getResult();
    }
}