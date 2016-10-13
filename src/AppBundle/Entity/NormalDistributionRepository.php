<?php

namespace AppBundle\Entity;
use AppBundle\Util\MathUtil;

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
}