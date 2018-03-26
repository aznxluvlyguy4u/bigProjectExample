<?php


namespace AppBundle\Criteria;


use Doctrine\Common\Collections\Criteria;


class NormalDistributionCriteria
{
    /**
     * @param int $year
     * @param boolean $isIncludingOnlyAliveAnimals
     * @return Criteria
     * @throws \Exception
     */
    public static function byYearAndIncludeOnlyAliveAnimals($year, $isIncludingOnlyAliveAnimals)
    {
        return Criteria::create()
                    ->where(
                        Criteria::expr()->eq('year', $year)
                    )
                    ->andWhere(Criteria::expr()->eq('isIncludingOnlyAliveAnimals', $isIncludingOnlyAliveAnimals))
            ;
    }

}