<?php

namespace AppBundle\Util;


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


}