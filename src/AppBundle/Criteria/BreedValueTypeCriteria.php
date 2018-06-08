<?php


namespace AppBundle\Criteria;


use Doctrine\Common\Collections\Criteria;

class BreedValueTypeCriteria
{
    /**
     * @param string $resultTableValueVariable
     * @return Criteria
     */
    public static function byResultTableValueVariable($resultTableValueVariable)
    {
        return Criteria::create()
            ->where(Criteria::expr()->eq('resultTableValueVariable', $resultTableValueVariable))
            ;
    }
}