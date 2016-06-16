<?php

namespace AppBundle\Util;

use AppBundle\Component\Utils;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationHealth;
use AppBundle\Enumerator\MaediVisnaStatus;
use AppBundle\Enumerator\ScrapieStatus;
use AppBundle\Constant\Constant;
use AppBundle\Enumerator\LocationHealthStatus;
use Doctrine\Common\Persistence\ObjectManager;

//TODO Nothing is done with the endDates yet.

/**
 * Class LocationHealthUpdater
 * @package AppBundle\Util
 */
class LocationHealthUpdater
{

    /**
     * @param ObjectManager $em
     * @param Location $location
     * @param string $ubnPreviousOwner
     * @return Location
     */
    public static function updateByGivenUbnOfOrigin(ObjectManager $em, Location $location, $ubnPreviousOwner)
    {
        $locationOfOrigin = $em->getRepository(Constant::LOCATION_REPOSITORY)->findByUbn($ubnPreviousOwner);
        return self::updateByGivenLocationOfOrigin($em, $location, $locationOfOrigin);
    }


    /**
     * @param ObjectManager $em
     * @param Location $locationOfDestination
     * @return Location
     */
    public static function updateWithoutOriginHealthData(ObjectManager $em,Location $locationOfDestination)
    {
        return self::updateByGivenLocationOfOrigin($em,$locationOfDestination, null);
    }



    /**
     * @param ObjectManager $em
     * @param Location $locationOfDestination
     * @param Location $locationOfOrigin
     * @return Location
     */
    public static function updateByGivenLocationOfOrigin(ObjectManager $em, Location $locationOfDestination,
                                                         $locationOfOrigin = null)
    {
        //If either or both the Destination or Origin location have a LocationHealth of null
        //Then create a new LocationHealth and set all values to "under observation".
        if($locationOfOrigin == null) {
            $newLocationHealth = self::createNewLocationHealthWithInitialValues();
            $em->persist($newLocationHealth); $em->flush();
            $locationOfDestination->addHealth($newLocationHealth);
            return $locationOfDestination;
        }

        $healthsDestination = $locationOfDestination->getHealths();
        $healthsOrigin = $locationOfOrigin->getHealths();

        if($healthsOrigin->isEmpty() || $healthsDestination->isEmpty()) {
            $newLocationHealth = self::createNewLocationHealthWithInitialValues();
            $em->persist($newLocationHealth); $em->flush();
            $locationOfDestination->addHealth($newLocationHealth);
            return $locationOfDestination;
        }

        //If both do have a LocationHealth ...
        $healthOrigin = Utils::returnLastLocationHealth($healthsOrigin);
        $originLocationIsHealthy = HealthChecker::verifyIsLocationHealthy($healthOrigin);

        if($originLocationIsHealthy) { //do nothing
            return $locationOfDestination;
        }

        //If origin is not verified as completely healthy ...

        $healthDestination = Utils::returnLastLocationHealth($locationOfDestination->getHealths());
        $locationHealthStatusesAreIdentical = HealthChecker::verifyHealthStatusesAreAtIdenticalLevel($healthDestination, $healthOrigin);
        if($locationHealthStatusesAreIdentical) { //do nothing
            return $locationOfDestination;
        }

        //If origin is not verified as completely healthy and the location health statuses are not identical, a new LocationHealth has to be added
        $newLocationHealth = new LocationHealth();
        //And we know the overall location is already not completely healthy at this point
        $newLocationHealth->setLocationHealthStatus(LocationHealthStatus::UNDER_OBSERVATION);


        //Now check each disease status separately ...
        
        //Scrapie
        $scrapieStatusOrigin = $healthOrigin->getScrapieStatus();
        $scrapieStatusDestination = $healthDestination->getScrapieStatus();

        $isOriginScrapieHealthy = HealthChecker::verifyIsScrapieStatusHealthy($scrapieStatusOrigin);
        $isDestinationScrapieHealthy = HealthChecker::verifyIsScrapieStatusHealthy($scrapieStatusDestination);

        if($isOriginScrapieHealthy && $isDestinationScrapieHealthy) {
         $newLocationHealth->setScrapieStatus($scrapieStatusDestination);

        } else if(!$isOriginScrapieHealthy && $isDestinationScrapieHealthy) {
            $newLocationHealth->setScrapieStatus(ScrapieStatus::UNDER_OBSERVATION);

        } else if($isOriginScrapieHealthy && !$isDestinationScrapieHealthy) {
            $newLocationHealth->setScrapieStatus($scrapieStatusDestination);

        } else { //(!$isOriginScrapieHealthy && !$isDestinationScrapieHealthy)
            $newLocationHealth->setScrapieStatus($scrapieStatusDestination);
        }
        
        //MaediVisna
        $maediVisnaStatusOrigin = $healthOrigin->getMaediVisnaStatus();
        $maediVisnaStatusDestination = $healthDestination->getMaediVisnaStatus();
        
        $isOriginMaediVisnaHealthy = HealthChecker::verifyIsMaediVisnaStatusHealthy($maediVisnaStatusOrigin);
        $isDestinationMaediVisnaHealthy = HealthChecker::verifyIsMaediVisnaStatusHealthy($maediVisnaStatusDestination);

        if($isOriginMaediVisnaHealthy && $isDestinationMaediVisnaHealthy) {
            $newLocationHealth->setMaediVisnaStatus($maediVisnaStatusDestination);

        } else if(!$isOriginMaediVisnaHealthy && $isDestinationMaediVisnaHealthy) {
            $newLocationHealth->setMaediVisnaStatus(maediVisnaStatus::UNDER_OBSERVATION);

        } else if($isOriginMaediVisnaHealthy && !$isDestinationMaediVisnaHealthy) {
            $newLocationHealth->setMaediVisnaStatus($maediVisnaStatusDestination);

        } else { //(!$isOriginMaediVisnaHealthy && !$isDestinationMaediVisnaHealthy)
            $newLocationHealth->setMaediVisnaStatus($maediVisnaStatusDestination);
        }

        $locationOfDestination->addHealth($newLocationHealth);

        $em->persist($newLocationHealth); $em->flush();
        
        return $locationOfDestination;
    }


    public static function createNewLocationHealthWithInitialValues()
    {
        $locationHealth = new LocationHealth();
        $locationHealth->setMaediVisnaStatus(MaediVisnaStatus::UNDER_OBSERVATION);
        $locationHealth->setScrapieStatus(ScrapieStatus::UNDER_OBSERVATION);
        $locationHealth->setLocationHealthStatus(LocationHealthStatus::UNDER_OBSERVATION);

        return $locationHealth;
    }

}