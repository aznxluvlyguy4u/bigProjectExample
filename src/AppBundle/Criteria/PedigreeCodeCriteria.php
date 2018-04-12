<?php


namespace AppBundle\Criteria;


use Doctrine\Common\Collections\Criteria;

class PedigreeCodeCriteria
{
    /**
     * @param $code
     * @return Criteria
     */
    public static function byCode($code)
    {
        return Criteria::create()
            ->where(Criteria::expr()->eq('code', $code))
            ;
    }
}