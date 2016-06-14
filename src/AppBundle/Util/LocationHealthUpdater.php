<?php

namespace AppBundle\Util;

use AppBundle\Component\Utils;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationHealth;
use AppBundle\Enumerator\MaediVisnaStatus;
use AppBundle\Enumerator\ScrapieStatus;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Constant\Constant;
use AppBundle\Enumerator\LocationHealthStatus;
use Doctrine\ORM\EntityManager;

//TODO Nothing is done with the endDates yet.

/**
 * Class LocationHealthUpdater
 * @package AppBundle\Util
 */
class LocationHealthUpdater
{

    /**
     * @param EntityManager $em
     * @param Location $location
     * @param string $ubnPreviousOwner
     * @return Location
     */
    public static function updateByGivenUbnOfOrigin(EntityManager $em, Location $location, $ubnPreviousOwner)
    {
        $locationOfOrigin = $em->getRepository(Constant::LOCATION_REPOSITORY)->findByUbn($ubnPreviousOwner);
        return self::updateByGivenLocationOfOrigin($location, $locationOfOrigin);
    }

    /**
     * @param EntityManager $em
     * @param Location $location
     * @param Animal $animal
     * @return Location
     */
    public static function updateByGivenAnimal(EntityManager $em, Location $location, Animal $animal)
    {
        //Verify if Animal is in the NSFO database
        $retrievedAnimal = $em->getRepository(Constant::ANIMAL_REPOSITORY)->findByAnimal($animal);
        if($retrievedAnimal != null) {

            $locationOfOrigin = $retrievedAnimal->getLocation();
            return self::updateByGivenLocationOfOrigin($location, $locationOfOrigin);

        } else {

            return self::updateByGivenLocationOfOrigin($location);
        }
    }

    /**
     * @param Location $locationOfDestination
     * @return Location
     */
    public static function updateWithoutOriginHealthData(Location $locationOfDestination)
    {
        return self::updateByGivenLocationOfOrigin($locationOfDestination, null);
    }



    /**
     * @param Location $locationOfDestination
     * @param Location $locationOfOrigin
     * @return Location
     */
    public static function updateByGivenLocationOfOrigin(Location $locationOfDestination,
                                                         $locationOfOrigin = null)
    {
        //If either or both the Destination or Origin location have a LocationHealth of null
        //Then create a new LocationHealth and set all values to "under observation".
        if($locationOfOrigin == null) {
            $locationOfDestination->addHealth(self::createNewLocationHealthWithInitialValues());
            return $locationOfDestination;
        }

        $healthsDestination = $locationOfDestination->getHealths();
        $healthsOrigin = $locationOfOrigin->getHealths();

        if($healthsOrigin->isEmpty() || $healthsDestination->isEmpty()) {
            $locationOfDestination->addHealth(self::createNewLocationHealthWithInitialValues());
            return $locationOfDestination;
        }

        //If both do have a LocationHealth ...
        $healthOrigin = Utils::returnLastLocationHealth($healthsOrigin);
        $originLocationIsHealthy = self::verifyIsLocationHealthy($healthOrigin);

        if($originLocationIsHealthy) { //do nothing
            return $locationOfDestination;
        }

        //If origin is not verified as completely healthy ...

        $healthDestination = Utils::returnLastLocationHealth($locationOfDestination->getHealths());
        $locationHealthStatusesAreIdentical = self::verifyHealthStatusesAreIdentical($healthDestination, $healthOrigin);
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

    /**
     * @param LocationHealth $locationHealth
     * @return bool
     */
    public static function verifyIsLocationHealthy(LocationHealth $locationHealth)
    {
        $scrapieStatusHealthy = HealthChecker::verifyIsScrapieStatusHealthy($locationHealth->getScrapieStatus());
        $maediVisnaStatusHealthy = HealthChecker::verifyIsMaediVisnaStatusHealthy($locationHealth->getMaediVisnaStatus());

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
    public static function verifyHealthStatusesAreIdentical(LocationHealth $destination, LocationHealth $origin)
    {
        if($destination->getScrapieStatus() == $origin->getScrapieStatus()
        && $destination->getMaediVisnaStatus() == $origin->getMaediVisnaStatus()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Used for debugging in this class.
     * Because there will be many changes to the health logic in subsequent phases, it is highly likely
     * that this debugging function will come in handy.
     *
     * @param $i
     * @param $locationOfDestination
     * @param $locationOfOrigin
     */
    private static function dumpAndDie($i, $locationOfDestination, $locationOfOrigin)
    {
        $destinationHealth = Utils::returnLastLocationHealth($locationOfDestination->getHealths());
        $originHealth = Utils::returnLastLocationHealth($locationOfOrigin->getHealths());

        dump($i,
            array("destination maediVisna" => $destinationHealth->getMaediVisnaStatus(),
                "destination scrapie" => $destinationHealth->getScrapieStatus()),
            array("origin maediVisna" => $originHealth->getMaediVisnaStatus(),
                "origin scrapie" => $originHealth->getScrapieStatus())
        );

        die();
    }
}