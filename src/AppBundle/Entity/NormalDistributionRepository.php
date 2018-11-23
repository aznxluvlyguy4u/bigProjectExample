<?php

namespace AppBundle\Entity;
use AppBundle\Util\MathUtil;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class NormalDistributionRepository
 * @package AppBundle\Entity
 */
class NormalDistributionRepository extends BaseRepository {


    /**
     * NormalDistribution constructor.
     * @param string $type
     * @param int $year
     * @param array $values
     * @param boolean $isIncludingOnlyAliveAnimals
     */
    public function persistFromArray($type, $year, $values, $isIncludingOnlyAliveAnimals)
    {
        $valuesCount = count($values);

        if($valuesCount > 0) {
            $mean = array_sum($values) / count($values);
            $standardDeviation = MathUtil::standardDeviation($values, $mean);
            $this->persistFromValues($type, $year, $mean, $standardDeviation, $isIncludingOnlyAliveAnimals);
        }
    }


    /**
     * @param string $type
     * @param int $year
     * @param float $mean
     * @param float $standardDeviation
     * @param boolean $isIncludingOnlyAliveAnimals
     */
    public function persistFromValues($type, $year, $mean, $standardDeviation, $isIncludingOnlyAliveAnimals)
    {
        $normalDistribution = new NormalDistribution($type, $year, $mean, $standardDeviation, $isIncludingOnlyAliveAnimals);

        $this->getManager()->persist($normalDistribution);
        $this->getManager()->flush();
    }


    /**
     * @param array $years
     * @param string $breedValueType from BreedValueTypeConstant
     * @return ArrayCollection
     */
    public function getByBreedValueTypeAndYears($breedValueType, array $years)
    {
        $results = new ArrayCollection();
        if (count($years) === 0) {
            return $results;
        }

        $qb = $this->getManager()->createQueryBuilder();

        $qb->select('n')
            ->from(NormalDistribution::class, 'n')
            ->where($qb->expr()->in('n.year', ':years'))
            ->andWhere($qb->expr()->eq('n.type', "'".$breedValueType."'"))
            ->setParameter('years', $years)
        ;

        /** @var NormalDistribution $normalDistribution */
        foreach ($qb->getQuery()->getResult() as $normalDistribution) {
            $results->add($normalDistribution);
        }

        return $results;
    }


    /**
     * @param int $year
     * @param string $breedValueType from BreedValueTypeConstant
     * @return NormalDistribution|null
     */
    public function getByBreedValueTypeAndYear($breedValueType, $year)
    {
        $qb = $this->getManager()->createQueryBuilder();

        $qb->select('n')
            ->from(NormalDistribution::class, 'n')
            ->where($qb->expr()->eq('n.year', ':year'))
            ->andWhere($qb->expr()->eq('n.type', "'".$breedValueType."'"))
            ->setParameter('year', $year)
        ;

        $results = $qb->getQuery()->getResult();

        if (count($results) === 0) {
            return null;
        }

        return array_shift($results);
    }
}