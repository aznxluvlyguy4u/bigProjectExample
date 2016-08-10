<?php

namespace AppBundle\Output;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationHealth;
use AppBundle\Util\Finder;
use Doctrine\ORM\EntityManager;

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
    static protected $maediVisnaCheckDate;

    /**
     * @var \DateTime
     */
    static protected $scrapieCheckDate;

    /**
     * @var \DateTime
     */
    static protected $checkDate;

    /**
     * @param Location $location
     */
    protected static function setUbnAndLocationHealthValues(EntityManager $em, Location $location = null)
    {
        if($location != null) {
            self::$ubn = $location->getUbn();
            self::$locationHealth = $location->getLocationHealth();

        } else {
            self::$ubn = null;
            self::$locationHealth = null;
        }
        

        if(self::$locationHealth != null) {
            self::$locationHealthStatus = self::$locationHealth->getLocationHealthStatus();

            $lastScrapie = Finder::findLatestActiveScrapie($location, $em);
            if($lastScrapie != null) {
                self::$scrapieStatus = $lastScrapie->getStatus();
                self::$scrapieCheckDate = $lastScrapie->getCheckDate();
                self::$scrapieEndDate = $lastScrapie->getEndDate();
            }

            $lastMaediVisna = Finder::findLatestActiveMaediVisna($location, $em);
            if($lastMaediVisna != null) {
                self::$maediVisnaStatus = $lastMaediVisna->getStatus();
                self::$maediVisnaCheckDate = $lastMaediVisna->getCheckDate();
                self::$maediVisnaEndDate = $lastMaediVisna->getEndDate();
            }
            
        //The default values    
        } else {
            self::$locationHealthStatus = "";
            self::$maediVisnaStatus = "";
            self::$scrapieStatus = "";
            self::$maediVisnaEndDate = "";
            self::$maediVisnaCheckDate = "";
            self::$scrapieEndDate = "";
            self::$scrapieCheckDate = "";
            self::$checkDate = "";
        }
    }
    
}