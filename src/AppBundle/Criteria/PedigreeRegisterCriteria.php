<?php


namespace AppBundle\Criteria;


use Doctrine\Common\Collections\Criteria;

class PedigreeRegisterCriteria
{
    /**
     * @param string $abbreviation
     * @return Criteria
     */
    public static function byAbbreviation($abbreviation)
    {
        return Criteria::create()
            ->where(Criteria::expr()->eq('abbreviation', $abbreviation))
            ;
    }
}