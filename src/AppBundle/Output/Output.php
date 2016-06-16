<?php

namespace AppBundle\Output;
use AppBundle\Component\Utils;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationHealth;

/**
 * Class Output
 * 
 * @package AppBundle\Output
 */
abstract class Output
{
    /**
     * @var string
     */
    static protected $ubn;

    /**
     * @var LocationHealth
     */
    static protected $locationHealth;

    /**
     * @var string
     */
    static protected $locationHealthStatus;

    /**
     * @var string
     */
    static protected $maediVisnaStatus;

    /**
     * @var string
     */
    static protected $scrapieStatus;

    /**
     * @var \DateTime
     */
    static protected $maediVisnaEndDate;

    /**
     * @var \DateTime
     */
    static protected $scrapieEndDate;

    /**
     * @var \DateTime
     */
    static protected $checkDate;

    /**
     * @param Location $location
     */
    protected static function setUbnAndLocationHealthValues(Location $location = null)
    {
        if($location != null) {
            self::$ubn = $location->getUbn();
            self::$locationHealth = Utils::returnLastLocationHealth($location->getHealths());

        } else {
            self::$ubn = null;
            self::$locationHealth = null;
        }

        if(self::$locationHealth != null) {
            self::$locationHealthStatus = self::$locationHealth->getLocationHealthStatus();
            self::$maediVisnaStatus = self::$locationHealth->getMaediVisnaStatus();
            self::$scrapieStatus = self::$locationHealth->getScrapieStatus();
            self::$maediVisnaEndDate = self::$locationHealth->getMaediVisnaEndDate();
            self::$scrapieEndDate = self::$locationHealth->getScrapieEndDate();
            self::$checkDate = self::$locationHealth->getCheckDate();
            
        //The default values    
        } else {
            self::$locationHealthStatus = "";
            self::$maediVisnaStatus = "";
            self::$scrapieStatus = "";
            self::$maediVisnaEndDate = "";
            self::$scrapieEndDate = "";
            self::$checkDate = "";
        }
    }
    
}