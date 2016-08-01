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


        $lastIllnesses = Finder::findLatestActiveIllnessesOfLocation($location, $em);

        $scrapieStatus = null;
        $scrapieEndDate = null;

        $maediVisnaStatus = null;
        $maediVisnaEndDate = null;

        if(self::$locationHealth != null) {

            $lastScrapie = $lastIllnesses[Constant::SCRAPIE];
            if($lastScrapie != null) {
                $scrapieStatus = $lastScrapie->getStatus();
                $scrapieEndDate = $lastScrapie->getEndDate();
            }

            $lastMaediVisna = $lastIllnesses[Constant::MAEDI_VISNA];
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
    
}