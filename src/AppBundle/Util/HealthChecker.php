<?php

namespace AppBundle\Util;


use AppBundle\Component\Utils;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\MaediVisnaStatus;
use AppBundle\Enumerator\ScrapieStatus;

class HealthChecker
{
    /**
     * @param string $scrapieStatus
     * @return bool
     */
    public static function verifyIsScrapieStatusHealthy($scrapieStatus)
    {
        return $scrapieStatus == ScrapieStatus::RESISTANT || $scrapieStatus == ScrapieStatus::FREE;
    }

    /**
     * @param string $maediVisnaStatus
     * @return bool
     */
    public static function verifyIsMaediVisnaStatusHealthy($maediVisnaStatus)
    {
        return $maediVisnaStatus == MaediVisnaStatus::FREE_1_YEAR
            || $maediVisnaStatus == MaediVisnaStatus::FREE_2_YEAR
            || $maediVisnaStatus == MaediVisnaStatus::FREE
            || $maediVisnaStatus == MaediVisnaStatus::STATUS_KNOWN_BY_AHD;
    }

    /**
     * @param Location $location
     * @return bool
     */
    public static function verifyIsLocationCompletelyHealthy($location)
    {
        $locationHealth = Utils::returnLastLocationHealth($location->getHealths());
        $maediVisnaStatus = $locationHealth->getMaediVisnaStatus();
        $scrapieStatus = $locationHealth->getScrapieStatus();

        if(self::verifyIsMaediVisnaStatusHealthy($maediVisnaStatus)
        && self::verifyIsScrapieStatusHealthy($scrapieStatus)) {
            return true;
        } else {
            return false;
        }
    }
}