<?php

namespace AppBundle\Output;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationHealth;
use AppBundle\Util\Finder;
use AppBundle\Util\NullChecker;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class Output
 * 
 * @package AppBundle\Output
 */
abstract class Output
{
    /** @var string */
    static protected $ubn;

    /** @var LocationHealth */
    static protected $locationHealth;

    /** @var string */
    static protected $locationHealthStatus;

    /** @var string */
    static protected $maediVisnaStatus;

    /** @var string */
    static protected $scrapieStatus;

    /** @var \DateTime */
    static protected $maediVisnaEndDate;

    /** @var \DateTime */
    static protected $scrapieEndDate;

    /** @var \DateTime */
    static protected $maediVisnaCheckDate;

    /** @var \DateTime */
    static protected $scrapieCheckDate;

    /** @var \DateTime */
    static protected $checkDate;

    /** @var string */
    static protected $maediVisnaReasonOfEdit;

    /** @var string */
    static protected $scrapieReasonOfEdit;

    /**
     * @param Location $location
     */
    protected static function setUbnAndLocationHealthValues(ObjectManager $em, Location $location = null)
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
                self::$scrapieReasonOfEdit = NullChecker::isNull($lastScrapie->getReasonOfEdit()) ? "": $lastScrapie->getReasonOfEdit();
            }

            $lastMaediVisna = Finder::findLatestActiveMaediVisna($location, $em);
            if($lastMaediVisna != null) {
                self::$maediVisnaStatus = $lastMaediVisna->getStatus();
                self::$maediVisnaCheckDate = $lastMaediVisna->getCheckDate();
                self::$maediVisnaEndDate = $lastMaediVisna->getEndDate();
                self::$maediVisnaReasonOfEdit = NullChecker::isNull($lastMaediVisna->getReasonOfEdit()) ? "": $lastMaediVisna->getReasonOfEdit();
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
            self::$scrapieReasonOfEdit = "";
            self::$maediVisnaReasonOfEdit = "";
        }
    }


    /**
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    public static function createStandardJsonErrorResponse($message = 'INVALID INPUT', $code = 428)
    {
        return self::createStandardJsonResponse($message, $code);
    }


    /**
     * @return JsonResponse
     */
    public static function createStandardJsonSuccessResponse()
    {
        return self::createStandardJsonResponse('OK', 200);
    }
    
    
    /**
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    public static function createStandardJsonResponse($message, $code)
    {
        $result = [
            Constant::MESSAGE_NAMESPACE => $message,
            Constant::CODE_NAMESPACE => $code
        ];

        return new JsonResponse($result, $code);
    }
}