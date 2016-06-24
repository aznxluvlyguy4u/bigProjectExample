<?php

namespace AppBundle\Util;

use AppBundle\Component\Utils;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationHealth;
use AppBundle\Entity\MaediVisna;
use AppBundle\Entity\Scrapie;
use AppBundle\Enumerator\MaediVisnaStatus;
use AppBundle\Enumerator\ScrapieStatus;
use AppBundle\Constant\Constant;
use Doctrine\Common\Collections\ArrayCollection;
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
     * @param Location $locationOfDestination
     * @param string $ubnPreviousOwner
     * @return ArrayCollection
     */
    public static function updateByGivenUbnOfOrigin(ObjectManager $em, Location $locationOfDestination, $ubnPreviousOwner)
    {
        $locationOfOrigin = $em->getRepository(Constant::LOCATION_REPOSITORY)->findByUbn($ubnPreviousOwner);
        return self::updateByGivenLocationOfOrigin($em, $locationOfDestination, $locationOfOrigin);
    }


    /**
     * @param ObjectManager $em
     * @param Location $locationOfDestination
     * @return ArrayCollection
     */
    public static function updateWithoutOriginHealthData(ObjectManager $em,Location $locationOfDestination)
    {
        return self::updateByGivenLocationOfOrigin($em,$locationOfDestination, null);
    }



    /**
     * @param ObjectManager $em
     * @param Location $locationOfDestination
     * @param Location $locationOfOrigin
     * @return ArrayCollection
     */
    private static function updateByGivenLocationOfOrigin(ObjectManager $em, Location $locationOfDestination,
                                                         $locationOfOrigin = null)
    {
        $locationOfDestination = self::persistInitialLocationHealthIfNull($em, $locationOfDestination);

        $locationHealthDestination = $locationOfDestination->getLocationHealth();
        $maediVisnaDestination = Utils::returnlastMaediVisna($locationHealthDestination->getMaediVisnas());
        $scrapieDestination = Utils::returnlastScrapie($locationHealthDestination->getScrapies());
        $maediVisnaDestinationIsHealthy = HealthChecker::verifyIsMaediVisnaStatusHealthy($maediVisnaDestination->getStatus());
        $scrapieDestinationIsHealthy = HealthChecker::verifyIsScrapieStatusHealthy($scrapieDestination->getStatus());
        
        if($locationOfOrigin == null) { //an import or Location that is not in our NSFO database

            if( $maediVisnaDestinationIsHealthy ){
                self::persistNewDefaultMaediVisna($em, $locationHealthDestination);
            } //else do nothing

            if( $scrapieDestinationIsHealthy ){
                self::persistNewDefaultScrapie($em, $locationHealthDestination);
            } //else do nothing

            $locationHealthOrigin = null;


        } else { //location of origin is known and in the NSFO database

            $locationOfOrigin = self::persistInitialLocationHealthIfNull($em, $locationOfOrigin);

            $locationHealthOrigin = $locationOfOrigin->getLocationHealth();
            $maediVisnaOrigin = Utils::returnlastMaediVisna($locationHealthOrigin->getMaediVisnas());
            $scrapieOrigin = Utils::returnlastScrapie($locationHealthOrigin->getScrapies());
            $maediVisnaOriginIsHealthy = HealthChecker::verifyIsMaediVisnaStatusHealthy($maediVisnaOrigin->getStatus());
            $scrapieOriginIsHealthy = HealthChecker::verifyIsScrapieStatusHealthy($scrapieOrigin->getStatus());

            if(!$maediVisnaOriginIsHealthy && $maediVisnaDestinationIsHealthy){
                self::persistNewDefaultMaediVisna($em, $locationHealthDestination);
            } //else do nothing

            if(!$scrapieOriginIsHealthy && $scrapieDestinationIsHealthy) {
                self::persistNewDefaultScrapie($em, $locationHealthDestination);
            } //else do nothing

            self::persistTheOverallLocationHealthStatus($em, $locationOfOrigin); //FIXME see function
        }

        self::persistTheOverallLocationHealthStatus($em, $locationOfDestination); //FIXME see function


        $results = new ArrayCollection();
        $results->set(Constant::LOCATION_HEALTH_DESTINATION, $locationHealthDestination);
        $results->set(Constant::LOCATION_HEALTH_ORIGIN, $locationHealthOrigin);

        return $results;
    }

    /**
     * @param Location $locationOfDestination
     * @return Location
     */
    private static function persistNewLocationHealthWithInitialValues(ObjectManager $em, Location $locationOfDestination)
    {
        //Create a LocationHealth with a MaediVisna and Scrapie with all statusses set to Under Observation
        $createWithDefaultUnderObservationIllnesses = true;
        $locationHealth = new LocationHealth($createWithDefaultUnderObservationIllnesses);
        $locationOfDestination->setLocationHealth($locationHealth);
        $locationHealth->setLocation($locationOfDestination);

        $em->persist($locationHealth->getMaediVisnas()->get(0));
        $em->persist($locationHealth->getScrapies()->get(0));
        $em->persist($locationHealth);
        $em->persist($locationOfDestination);
        $em->flush();

        return $locationOfDestination;
    }

    /**
     * @param ObjectManager $em
     * @param LocationHealth $locationHealth
     * @return LocationHealth
     */
    private static function persistNewDefaultMaediVisna(ObjectManager $em, LocationHealth $locationHealth)
    {
        $maediVisna = new MaediVisna(MaediVisnaStatus::UNDER_OBSERVATION);
        $maediVisna->setLocationHealth($locationHealth);
        $locationHealth->addMaediVisna($maediVisna);
        $locationHealth->setCurrentMaediVisnaStatus($maediVisna->getStatus());

        $em->persist($maediVisna);
        $em->persist($locationHealth);
        $em->flush();

        return $locationHealth;
    }

    /**
     * @param ObjectManager $em
     * @param LocationHealth $locationHealth
     * @return LocationHealth
     */
    private static function persistNewDefaultScrapie(ObjectManager $em, LocationHealth $locationHealth)
    {
        $scrapie = new Scrapie(ScrapieStatus::UNDER_OBSERVATION);
        $scrapie->setLocationHealth($locationHealth);
        $locationHealth->addScrapie($scrapie);
        $locationHealth->setCurrentScrapieStatus($scrapie->getStatus());

        $em->persist($scrapie);
        $em->persist($locationHealth);
        $em->flush();

        return $locationHealth;
    }

    /**
     * Initialize LocationHealth entities and values of destination where necessary
     *
     * @param ObjectManager $em
     * @param Location $locationOfDestination
     * @return Location
     */
    private static function persistInitialLocationHealthIfNull(ObjectManager $em, Location $locationOfDestination)
    {
        if($locationOfDestination == null) {
            return null;
        }
        
        $locationHealthDestination = $locationOfDestination->getLocationHealth();

        if($locationHealthDestination == null) {
            self::persistNewLocationHealthWithInitialValues($em, $locationOfDestination);

        } else {

            $maediVisnaDestination = Utils::returnlastMaediVisna($locationHealthDestination->getMaediVisnas());
            if ($maediVisnaDestination == null) {
                self::persistNewDefaultMaediVisna($em, $locationHealthDestination);
            }

            $scrapieDestination = Utils::returnlastScrapie($locationHealthDestination->getScrapies());
            if ($scrapieDestination == null) {
                self::persistNewDefaultScrapie($em, $locationHealthDestination);
            }
        }

        return $locationOfDestination;
    }


    /**
     * @param ObjectManager $em
     * @param Location $location
     * @return Location
     */
    private static function persistTheOverallLocationHealthStatus(ObjectManager $em, Location $location)
    {
        //TODO remove the (overall) locationHealthStatus from LocationHealth in conjuction with the Java entities.
        //For now the value is just set to null.

        $location->getLocationHealth()->setLocationHealthStatus(null);
        $em->persist($location);
        $em->flush();

        return $location;
    }

}