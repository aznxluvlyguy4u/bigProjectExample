<?php

namespace AppBundle\Entity;
use AppBundle\Constant\BreedValueTypeConstant;
use AppBundle\Util\ArrayUtil;
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
     * @return ArrayCollection
     */
    public function getSiGAbyYears(array $years)
    {
        $results = new ArrayCollection();
        if (count($years) === 0) {
            return $results;
        }

        $qb = $this->getManager()->createQueryBuilder();

        $qb->select('n')
            ->from(NormalDistribution::class, 'n')
            ->where($qb->expr()->in('n.year', ':years'))
            ->andWhere($qb->expr()->eq('n.type', "'".BreedValueTypeConstant::IGA_SCOTLAND."'"))
            ->setParameter('years', $years)
        ;

        /** @var NormalDistribution $normalDistribution */
        foreach ($qb->getQuery()->getResult() as $normalDistribution) {
            $results->add($normalDistribution);
        }

        return $results;
    }

}