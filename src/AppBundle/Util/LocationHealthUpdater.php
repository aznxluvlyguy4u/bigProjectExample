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




        //If origin is not verified as healthy and the location health statuses are not identical, a new LocationHealth has to be added
        $newLocationHealth = new LocationHealth();
        //And we know the overall location is already not completely healthy at this point
        $newLocationHealth->setLocationHealthStatus(LocationHealthStatus::UNDER_OBSERVATION);


        //Now check each disease status separately ...
        
        //Scrapie
        if($healthOrigin->getScrapieStatus() != ScrapieStatus::RESISTANT 
            && $healthDestination->getScrapieStatus() != ScrapieStatus::RESISTANT) {
            $newLocationHealth->setScrapieStatus(ScrapieStatus::UNDER_OBSERVATION);
        }
        
        //MaediVisna
        $isOriginMaediVisnaFree = $healthOrigin->getMaediVisnaStatus() == MaediVisnaStatus::FREE_1_YEAR
            || $healthOrigin->getMaediVisnaStatus() == MaediVisnaStatus::FREE_2_YEAR;
        $isDestinationMaediVisnaFree = $healthDestination->getMaediVisnaStatus() == MaediVisnaStatus::FREE_1_YEAR
            || $healthDestination->getMaediVisnaStatus() == MaediVisnaStatus::FREE_2_YEAR;
        
        if(!($isOriginMaediVisnaFree && $isDestinationMaediVisnaFree)) {
            $newLocationHealth->setMaediVisnaStatus(ScrapieStatus::UNDER_OBSERVATION);
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
        if(    $locationHealth->getScrapieStatus() != ScrapieStatus::UNDER_OBSERVATION
            && $locationHealth->getScrapieStatus() != null
            && $locationHealth->getScrapieStatus() != ""
            && $locationHealth->getMaediVisnaStatus() != ScrapieStatus::UNDER_OBSERVATION
            && $locationHealth->getMaediVisnaStatus() != null
            && $locationHealth->getMaediVisnaStatus() != "") {
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
    
}