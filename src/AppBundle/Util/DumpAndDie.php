<?php

namespace AppBundle\Util;


use AppBundle\Component\Utils;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationHealth;


class DumpAndDie
{

    /**
     * Used for debugging in this class.
     * Because there will be many changes to the health logic in subsequent phases, it is highly likely
     * that this debugging function will come in handy.
     *
     * @param integer $i
     * @param Location $locationOfDestination
     * @param Location $locationOfOrigin
     */
    public static function locationStatuses($i, $locationOfDestination, $locationOfOrigin)
    {
        $destinationHealth = Utils::returnLastLocationHealth($locationOfDestination->getHealths());
        $originHealth = Utils::returnLastLocationHealth($locationOfOrigin->getHealths());

        self::locationHealthStatuses($i, $destinationHealth, $originHealth);
    }

    /**
     * @param integer $i
     * @param LocationHealth $destinationHealth
     * @param LocationHealth $originHealth
     */
    public static function locationHealthStatuses($i, $destinationHealth, $originHealth)
    {
        dump($i,
            array("destination maediVisna" => $destinationHealth->getMaediVisnaStatus(),
                "destination scrapie" => $destinationHealth->getScrapieStatus()),
            array("origin maediVisna" => $originHealth->getMaediVisnaStatus(),
                "origin scrapie" => $originHealth->getScrapieStatus())
        );

        die();
    }
}