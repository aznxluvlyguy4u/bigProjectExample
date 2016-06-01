<?php

namespace AppBundle\Util;

use AppBundle\Entity\Animal;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationHealth;
use AppBundle\Enumerator\MaediVisnaStatus;
use AppBundle\Enumerator\ScrapieStatus;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Constant\Constant;
use AppBundle\Enumerator\LocationHealthStatus;
use Doctrine\ORM\EntityManager;

//TODO NOTE! For phase one we assume a location only has one LocationHealth. Even though healths is an ArrayCollection.
//TODO Replace get(0) by getting the last one in the ArrayCollection ->last() has some issues. Test it first.

/**
 * Class LocationHealthUpdater
 * @package AppBundle\Util
 */
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
        //By default set the status to UNDER_OBSERVATION for animals with an unknown HealthStatus
        $maediVisnaStatus = MaediVisnaStatus::UNDER_OBSERVATION;
        $scrapieStatus = ScrapieStatus::UNDER_OBSERVATION;

        if($locationOfOrigin != null) {
            $healthsOrigin = $locationOfOrigin->getHealths();
            if(!$healthsOrigin->isEmpty()) {
                $healthOrigin = $healthsOrigin->get(0);

                //Get the health status
                $maediVisnaStatus = $healthOrigin->getMaediVisnaStatus();
                $scrapieStatus = $healthOrigin->getScrapieStatus();
            }
        }

        $healthsDestination = $locationOfDestination->getHealths();
        if($healthsDestination->isEmpty()) {
            $locationOfDestination->getHealths()->clear();
            $locationOfDestination->addHealth(new LocationHealth());
        }

        $locationOfDestination = self::updateByStatus($locationOfDestination, $maediVisnaStatus, $scrapieStatus);
        $locationOfDestination = self::updateOverallHealthStatus($locationOfDestination);

        return $locationOfDestination;
    }

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
     * @param Location $location
     * @param string $maediVisnaStatus
     * @param string $scrapieStatus
     * @return Location
     */
    private static function updateByStatus($location, $maediVisnaStatus, $scrapieStatus)
    {
        //Check for maediVisnaStatus and scrapieStatus separately
        if( $maediVisnaStatus == MaediVisnaStatus::UNDER_OBSERVATION ||
            $maediVisnaStatus == null ||
            $maediVisnaStatus == "") {

            $location->getHealths()->get(0)->setMaediVisnaStatus(MaediVisnaStatus::UNDER_OBSERVATION);
        }

        if( $scrapieStatus == ScrapieStatus::UNDER_OBSERVATION ||
            $scrapieStatus == null ||
            $scrapieStatus == "") {

            $location->getHealths()->get(0)->setScrapieStatus(ScrapieStatus::UNDER_OBSERVATION);
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
        if($location->getHealths()->get(0)->getMaediVisnaStatus() == MaediVisnaStatus::UNDER_OBSERVATION ||
            $location->getHealths()->get(0)->getScrapieStatus() == ScrapieStatus::UNDER_OBSERVATION) {
            $location->getHealths()->get(0)->setLocationHealthStatus(LocationHealthStatus::UNDER_OBSERVATION);
        }

        return $location;
    }

}