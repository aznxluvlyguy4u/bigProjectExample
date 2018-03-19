<?php


namespace AppBundle\Criteria;


use Doctrine\Common\Collections\Criteria;

class PedigreeRegisterRegistrationCriteria
{
    /**
     * @param string $breederNumber
     * @return Criteria
     */
    public static function byBreederNumber($breederNumber)
    {
        return Criteria::create()
            ->where(Criteria::expr()->eq('breederNumber', $breederNumber))
            ;
    }
}