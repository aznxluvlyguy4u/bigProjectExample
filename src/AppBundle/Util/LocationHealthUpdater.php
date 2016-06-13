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

//TODO NOTE! For phase one we assume a location only has one LocationHealth. Even though healths is an ArrayCollection.

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
        $maediVisnaStatusOrigin = MaediVisnaStatus::UNDER_OBSERVATION;
        $scrapieStatusOrigin = ScrapieStatus::UNDER_OBSERVATION;

        if($locationOfOrigin != null) {
            $healthsOrigin = $locationOfOrigin->getHealths();
            if(!$healthsOrigin->isEmpty()) {
                $healthOrigin = Utils::returnLastItemFromCollectionByLogDate($healthsOrigin);

                //Get the health status
                $maediVisnaStatusOrigin = $healthOrigin->getMaediVisnaStatus();
                $scrapieStatusOrigin = $healthOrigin->getScrapieStatus();
            }
        }

        $healthsDestination = $locationOfDestination->getHealths();
        if($healthsDestination->isEmpty()) {
            $locationOfDestination->getHealths()->clear();
            $locationOfDestination->addHealth(new LocationHealth());
        }

        $locationOfDestination = self::updateByStatus($locationOfDestination, $maediVisnaStatusOrigin, $scrapieStatusOrigin);
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
     * @param string $maediVisnaStatusOrigin
     * @param string $scrapieStatusOrigin
     * @return Location
     */
    private static function updateByStatus($location, $maediVisnaStatusOrigin, $scrapieStatusOrigin)
    {
        //Check for maediVisnaStatus and scrapieStatus separately
        if( $maediVisnaStatusOrigin == MaediVisnaStatus::UNDER_OBSERVATION ||
            $maediVisnaStatusOrigin == null ||
            $maediVisnaStatusOrigin == "") {

            $lastHealth = Utils::returnLastItemFromCollectionByLogDate($location->getHealths());
            $lastHealth->setMaediVisnaStatus(MaediVisnaStatus::UNDER_OBSERVATION);
        }

        if( $scrapieStatusOrigin == ScrapieStatus::UNDER_OBSERVATION ||
            $scrapieStatusOrigin == null ||
            $scrapieStatusOrigin == "") {

            $lastHealth = Utils::returnLastItemFromCollectionByLogDate($location->getHealths());
            $lastHealth->setScrapieStatus(ScrapieStatus::UNDER_OBSERVATION);
        }

        return $location;
    }

    /**
     *
     * @param Location $location
     * @return Location
     */
    private static function updateOverallHealthStatus(Location $location)
    {
        $lastHealth = Utils::returnLastItemFromCollectionByLogDate($location->getHealths());

        if($lastHealth->getMaediVisnaStatus() == MaediVisnaStatus::UNDER_OBSERVATION ||
            $lastHealth->getScrapieStatus() == ScrapieStatus::UNDER_OBSERVATION) {
            $lastHealth->setLocationHealthStatus(LocationHealthStatus::UNDER_OBSERVATION);
        }

        return $location;
    }

}