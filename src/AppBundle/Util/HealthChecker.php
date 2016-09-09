<?php

namespace AppBundle\Util;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationHealth;
use AppBundle\Enumerator\LocationHealthStatus;
use AppBundle\Enumerator\MaediVisnaStatus;
use AppBundle\Enumerator\RequestType;
use AppBundle\Enumerator\ScrapieStatus;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Types\DecimalType;

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
     * @param string $overallStatus
     * @return bool
     */
    public static function verifyIsOverallLocationStatusHealthy($overallStatus)
    {
        return $overallStatus == LocationHealthStatus::FREE;
    }

    /**
     * @param Location $location
     * @return bool
     */
    public static function verifyIsLocationCompletelyHealthy($location)
    {
        $locationHealth = $locationHealth = $location->getLocationHealth();
        $maediVisnaStatus = $locationHealth->getCurrentMaediVisnaStatus();
        $scrapieStatus = $locationHealth->getCurrentScrapieStatus();

        if(self::verifyIsMaediVisnaStatusHealthy($maediVisnaStatus)
        && self::verifyIsScrapieStatusHealthy($scrapieStatus)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param Location $location
     * @return bool
     */
    public static function verifyIsLocationCompletelyNotHealthy($location)
    {
        $locationHealth = $location->getLocationHealth();
        $maediVisnaStatus = $locationHealth->getCurrentMaediVisnaStatus();
        $scrapieStatus = $locationHealth->getCurrentScrapieStatus();

        if(!self::verifyIsMaediVisnaStatusHealthy($maediVisnaStatus)
            && !self::verifyIsScrapieStatusHealthy($scrapieStatus)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param LocationHealth $locationHealth
     * @return bool
     */
    public static function verifyIsLocationHealthy(LocationHealth $locationHealth)
    {
        $scrapieStatusHealthy = HealthChecker::verifyIsScrapieStatusHealthy($locationHealth->getCurrentScrapieStatus());
        $maediVisnaStatusHealthy = HealthChecker::verifyIsMaediVisnaStatusHealthy($locationHealth->getCurrentMaediVisnaStatus());

        if($scrapieStatusHealthy && $maediVisnaStatusHealthy) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param LocationHealth $destination
     * @param LocationHealth $origin
     * @return bool
     */
    public static function verifyHealthStatusesAreAtIdenticalLevel(LocationHealth $destination, LocationHealth $origin)
    {
        $destinationScrapieStatus = self::verifyIsScrapieStatusHealthy($destination->getCurrentScrapieStatus());
        $originScrapieStatus = self::verifyIsScrapieStatusHealthy($origin->getCurrentScrapieStatus());
        $destinationMaediVisnaStatus = self::verifyIsMaediVisnaStatusHealthy($destination->getCurrentMaediVisnaStatus());
        $originMaediVisnaStatus = self::verifyIsMaediVisnaStatusHealthy($origin->getCurrentMaediVisnaStatus());

        if($destinationScrapieStatus == $originScrapieStatus
            && $destinationMaediVisnaStatus == $originMaediVisnaStatus) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param DeclareArrival|DeclareImport $declareIn
     * @param ObjectManager $manager
     * @return bool
     */
    public static function verifyIsLocationOriginCompletelyHealthy($declareIn, $className, ObjectManager $manager){

        switch($className) {
            case RequestType::DECLARE_ARRIVAL_ENTITY:
                $locationOrigin = $manager->getRepository(Constant::LOCATION_REPOSITORY)->findOneByActiveUbn($declareIn->getUbnPreviousOwner());
                if($locationOrigin == null) {
                    return false;
                } else {
                    return self::verifyIsLocationCompletelyHealthy($locationOrigin);
                }

            case RequestType::DECLARE_IMPORT_ENTITY:
                return false;

            default:
                return null;
        }
    }

    /**
     * @param LocationHealth $previousLocationHealthDestination
     * @param LocationHealth $newLocationHealthDestination
     * @return bool
     */
    public static function verifyHasLocationHealthChanged($previousLocationHealthDestination, $newLocationHealthDestination){
        $previousScrapieStatus = $previousLocationHealthDestination->getCurrentScrapieStatus();
        $newScrapieStatus = $newLocationHealthDestination->getCurrentScrapieStatus();
        $previousMaediVisnaStatus = $previousLocationHealthDestination->getCurrentMaediVisnaStatus();
        $newMaediVisnaStatus = $newLocationHealthDestination->getCurrentMaediVisnaStatus();

        if($previousScrapieStatus == $newScrapieStatus
            && $previousMaediVisnaStatus == $newMaediVisnaStatus) {
            return false;
        } else {
            return true;
        }
    }
}