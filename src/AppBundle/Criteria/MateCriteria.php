<?php


namespace AppBundle\Criteria;


use AppBundle\Entity\Location;
use AppBundle\Enumerator\RequestStateType;
use Doctrine\Common\Collections\Criteria;


class MateCriteria
{
    /**
     * @param Location $location
     * @return Criteria
     * @throws \Exception
     */
    public static function byLocation(Location $location)
    {
        if ($location === null) {
            throw new \Exception('Location cannot be null');
        }

        return Criteria::create()
                    ->where(
                        Criteria::expr()->eq('location', $location->getId())
                    );
    }


    /**
     * @return Criteria
     */
    public static function orderByEndDateDesc()
    {
        return Criteria::create()
                    ->orderBy(['endDate' => Criteria::DESC])
            ;
    }


    /**
     * @return Criteria
     */
    public static function requestStateIsFinished()
    {
        return Criteria::create()
                    ->where(
                        Criteria::expr()->eq('requestState', RequestStateType::FINISHED)
                    );
    }


    /**
     * @param bool $isOverwritten
     * @return Criteria
     */
    public static function isOverwrittenVersion(bool $isOverwritten = true)
    {
        return Criteria::create()
            ->where(
                Criteria::expr()->eq('isOverwrittenVersion', $isOverwritten)
            );
    }


    /**
     * @return Criteria
     */
    public static function hasNoLitter()
    {
        return Criteria::create()
            ->where(
                Criteria::expr()->eq('litter', null)
            );
    }

}