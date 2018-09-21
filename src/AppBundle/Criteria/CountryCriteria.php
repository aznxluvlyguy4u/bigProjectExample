<?php


namespace AppBundle\Criteria;


use AppBundle\Constant\Constant;
use Doctrine\Common\Collections\Criteria;

class CountryCriteria
{
    /**
     * @param string $continent
     * @return Criteria
     */
    public static function byContinent($continent)
    {
        return Criteria::create()
            ->where(Criteria::expr()->eq(Constant::CONTINENT_NAMESPACE, $continent))
        ;
    }
}