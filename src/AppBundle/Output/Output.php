<?php

namespace AppBundle\Output;
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
            self::$locationHealth = $location->getLocationHealth();

        } else {
            self::$ubn = null;
            self::$locationHealth = null;
        }


        $scrapieStatus = null;
        $scrapieEndDate = null;

        $maediVisnaStatus = null;
        $maediVisnaEndDate = null;

        if(self::$locationHealth != null) {
            $lastScrapie = self::$locationHealth->getScrapies()->last();
            if($lastScrapie != null) {
                $scrapieStatus = $lastScrapie->getStatus();
                $scrapieEndDate = $lastScrapie->getEndDate();
            }

            $lastMaediVisna = self::$locationHealth->getMaediVisnas()->last();

            if($lastMaediVisna != null) {
                $maediVisnaStatus = $lastMaediVisna->getStatus();
                $maediVisnaEndDate = $lastMaediVisna->getEndDate();
            }
        }

        if(self::$locationHealth != null) {
            self::$locationHealthStatus = self::$locationHealth->getLocationHealthStatus();
            self::$maediVisnaStatus = $maediVisnaStatus;
            self::$scrapieStatus = $scrapieStatus;
            self::$maediVisnaEndDate = $maediVisnaEndDate;
            self::$scrapieEndDate = $scrapieEndDate;
            
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

    /**
     * Replace null values with empty strings for the frontend.
     * This only works with strings!
     *
     * @param string|null $value
     * @return string
     */
    public static function fillNull($value)
    {
        if($value == null) {
            return "";
        } else {
            return $value;
        }
    }
    
}