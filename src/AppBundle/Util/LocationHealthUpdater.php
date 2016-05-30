<?php

namespace AppBundle\Util;

use AppBundle\Entity\Animal;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationHealth;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Constant\Constant;
use AppBundle\Enumerator\HealthStatus;
use Doctrine\ORM\EntityManager;

class LocationHealthUpdater
{
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
        //By default set the status to IN_OBSERVATION
        $maediVisnaStatus = HealthStatus::IN_OBSERVATION;
        $scrapieStatus = HealthStatus::IN_OBSERVATION;

        if($locationOfOrigin != null) {
            $healthOrigin = $locationOfOrigin->getHealth();

            if($healthOrigin != null) {
                //Get the health status
                $maediVisnaStatus = $healthOrigin->getMaediVisnaStatus();
                $scrapieStatus = $healthOrigin->getScrapieStatus();
            }
        }

        //Create a new LocationHealth entity if the destination Location does not have one yet
        if($locationOfDestination->getHealth() == null) {
            $locationOfDestination->setHealth(new LocationHealth());
        }

        $locationOfDestination = self::updateByStatus($locationOfDestination, $maediVisnaStatus, $scrapieStatus);
        $locationOfDestination = self::updateOverallHealthStatus($locationOfDestination);

        return $locationOfDestination;
    }

    /**
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
     * @param Location $location
     * @param string $maediVisnaStatus
     * @param string $scrapieStatus
     * @return Location
     */
    private static function updateByStatus($location, $maediVisnaStatus, $scrapieStatus)
    {
        //Check for maediVisnaStatus and scrapieStatus separately
        if($maediVisnaStatus == HealthStatus::UNKNOWN ||
            $maediVisnaStatus == HealthStatus::IN_OBSERVATION ||
            $maediVisnaStatus == null ||
            $maediVisnaStatus == "") {

            $location->getHealth()->setMaediVisnaStatus(HealthStatus::IN_OBSERVATION);
        }

        if($scrapieStatus == HealthStatus::UNKNOWN ||
            $scrapieStatus == HealthStatus::IN_OBSERVATION ||
            $scrapieStatus == null ||
            $scrapieStatus == "") {

            $location->getHealth()->setScrapieStatus(HealthStatus::IN_OBSERVATION);
        }

        if($maediVisnaStatus == HealthStatus::INFECTED) {
            $location->getHealth()->setMaediVisnaStatus(HealthStatus::INFECTED);
        }

        if($scrapieStatus == HealthStatus::INFECTED) {
            $location->getHealth()->setScrapieStatus(HealthStatus::INFECTED);
        }

        return $location;
    }

    /**
     *
     * TODO Verify if this function is correct
     *
     * @param Location $location
     * @return Location
     */
    private static function updateOverallHealthStatus(Location $location)
    {
        //Update the overall LocationHealthStatus in this exact order
        if($location->getHealth()->getMaediVisnaStatus() == HealthStatus::IN_OBSERVATION ||
            $location->getHealth()->getScrapieStatus() == HealthStatus::IN_OBSERVATION) {
            $location->getHealth()->setLocationHealthStatus(HealthStatus::IN_OBSERVATION);
        }

        if($location->getHealth()->getMaediVisnaStatus() == HealthStatus::INFECTED ||
            $location->getHealth()->getScrapieStatus() == HealthStatus::INFECTED) {
            $location->getHealth()->setLocationHealthStatus(HealthStatus::INFECTED);
        }

        return $location;
    }

}