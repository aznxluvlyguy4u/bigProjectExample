<?php


namespace AppBundle\Criteria;


use AppBundle\Entity\Location;
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
}