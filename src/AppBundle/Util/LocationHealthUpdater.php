<?php

namespace AppBundle\Util;

use AppBundle\Component\LocationHealthMessageBuilder;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationHealth;
use AppBundle\Entity\LocationHealthMessage;
use AppBundle\Entity\MaediVisna;
use AppBundle\Entity\Scrapie;
use AppBundle\Enumerator\MaediVisnaStatus;
use AppBundle\Enumerator\ScrapieStatus;
use AppBundle\Constant\Constant;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
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
     * @param DeclareArrival $declareArrival
     * @param boolean $isDeclareInBase used to only hide the obsolete illnesses once at the beginning of the HealthService for loop
     * @return ArrayCollection
     */
    public static function updateByGivenUbnOfOrigin(ObjectManager $em, Location $locationOfDestination,
                                                    DeclareArrival $declareArrival, $isDeclareInBase)
    {
        $ubnPreviousOwner = $declareArrival->getUbnPreviousOwner();
        $checkDate = $declareArrival->getArrivalDate();

        $locationOfOrigin = $em->getRepository(Constant::LOCATION_REPOSITORY)->findByUbn($ubnPreviousOwner);
        return self::updateByGivenLocationOfOrigin($em, $declareArrival ,$locationOfDestination, $checkDate, $isDeclareInBase, $locationOfOrigin);
    }


    /**
     * @param ObjectManager $em
     * @param Location $locationOfDestination
     * @param DeclareImport $declareImport
     * @param boolean $isDeclareInBase used to only hide the obsolete illnesses once at the beginning of the HealthService for loop
     * @return ArrayCollection
     */
    public static function updateWithoutOriginHealthData(ObjectManager $em, Location $locationOfDestination, DeclareImport $declareImport, $isDeclareInBase)
    {
        $checkDate = $declareImport->getImportDate();
        return self::updateByGivenLocationOfOrigin($em, $declareImport, $locationOfDestination, $checkDate, $isDeclareInBase, null);
    }



    /**
     * @param ObjectManager $em
     * @param Location $locationOfDestination
     * @param DeclareArrival|DeclareImport $declareIn
     * @param \DateTime $checkDate
     * @param boolean $isDeclareInBase used to only hide the obsolete illnesses once at the beginning of the HealthService for loop
     * @param Location $locationOfOrigin
     * @return DeclareArrival|DeclareImport
     */
    private static function updateByGivenLocationOfOrigin(ObjectManager $em, $declareIn, Location $locationOfDestination,
                                                          \DateTime $checkDate, $isDeclareInBase, $locationOfOrigin = null)
    {
        //Initializing the locationHealth if necessary. This is a fail safe. All locations should be created with their own locationHealth.
        $locationOfDestination = self::persistInitialLocationHealthIfNull($em, $locationOfDestination, $checkDate);

        //Persist a new LocationHealthMessage for declareIn, without the healthValues
        self::persistNewLocationHealthMessage($em, $declareIn);

        //Hide/Deactivate all illness records after that one. Even for statuses that didn't change to simplify the logic.
        if($isDeclareInBase) {
            self::hideAllFollowingIllnesses($em, $locationOfDestination, $checkDate);
            //Redo the null check in case all illnesses are made hidden
            $locationOfDestination = self::persistInitialLocationHealthIfNull($em, $locationOfDestination, $checkDate);
        }

        //Get the latest values
        $latestActiveIllnessesDestination = Finder::findLatestActiveIllnessesOfLocation($locationOfDestination, $em); //returns null values if null
        $previousMaediVisnaDestination = $latestActiveIllnessesDestination->get(Constant::MAEDI_VISNA);
        $previousScrapieDestination = $latestActiveIllnessesDestination->get(Constant::SCRAPIE);

        $locationHealthDestination = $locationOfDestination->getLocationHealth(); //Null check already done in the first command of this function
        $previousMaediVisnaDestinationIsHealthy = HealthChecker::verifyIsMaediVisnaStatusHealthy($previousMaediVisnaDestination->getStatus());
        $previousScrapieDestinationIsHealthy = HealthChecker::verifyIsScrapieStatusHealthy($previousScrapieDestination->getStatus());

        //Initialize default values
        $latestMaediVisnaDestination = $previousMaediVisnaDestination;
        $latestScrapieDestination = $previousScrapieDestination;


        //Do the health check ...

        if($locationOfOrigin == null) { //an import or Location that is not in our NSFO database

            if( $previousMaediVisnaDestinationIsHealthy ){
                $latestMaediVisnaDestination = self::persistNewDefaultMaediVisna($em, $locationHealthDestination, $checkDate);
            } //else do nothing

            if( $previousScrapieDestinationIsHealthy ){
                $latestScrapieDestination = self::persistNewDefaultScrapie($em, $locationHealthDestination, $checkDate);
            } //else do nothing

            $locationHealthOrigin = null;


        } else { //location of origin is known and in the NSFO database

            $locationHealthOrigin = $locationOfOrigin->getLocationHealth();

            $maediVisnaOrigin = Finder::findLatestActiveMaediVisna($locationOfOrigin, $em);
            if($maediVisnaOrigin != null) {
                $maediVisnaOriginIsHealthy = HealthChecker::verifyIsMaediVisnaStatusHealthy($maediVisnaOrigin->getStatus());
            } else {
                $maediVisnaOriginIsHealthy = false;
            }

            $scrapieOrigin = Finder::findLatestActiveScrapie($locationOfOrigin, $em);
            if($scrapieOrigin != null) {
                $scrapieOriginIsHealthy = HealthChecker::verifyIsScrapieStatusHealthy($scrapieOrigin->getStatus());
            } else {
                $scrapieOriginIsHealthy = false;
            }


            if(!$maediVisnaOriginIsHealthy && $previousMaediVisnaDestinationIsHealthy){
                $latestMaediVisnaDestination = self::persistNewDefaultMaediVisna($em, $locationHealthDestination, $checkDate);
            } //else do nothing

            if(!$scrapieOriginIsHealthy && $previousScrapieDestinationIsHealthy) {
                $latestScrapieDestination = self::persistNewDefaultScrapie($em, $locationHealthDestination, $checkDate);
            } //else do nothing

            self::persistTheOverallLocationHealthStatus($em, $locationOfOrigin); //FIXME see function
        }

        self::persistTheOverallLocationHealthStatus($em, $locationOfDestination); //FIXME see function


        $illnesses = new ArrayCollection();
        $illnesses->set(Constant::MAEDI_VISNA, $latestMaediVisnaDestination);
        $illnesses->set(Constant::SCRAPIE, $latestScrapieDestination);

        /* The LocationHealthMessage contains the LocationHealth history
            and must be calculated AFTER the locationHealth has been updated.
        */
        self::finalizeLocationHealthMessage($em, $declareIn, $locationHealthDestination, $locationHealthOrigin, $illnesses);

        return $declareIn;
    }

    /**
     * @param ObjectManager $em
     * @param DeclareArrival|DeclareImport $messageObject
     */
    private static function persistNewLocationHealthMessage(ObjectManager $em, $messageObject)
    {
        $locationHealthMessage = LocationHealthMessageBuilder::prepare($messageObject);
        $location = $messageObject->getLocation();

        //Set LocationHealthMessage relationships
        $messageObject->setHealthMessage($locationHealthMessage);
        $location->addHealthMessage($locationHealthMessage);

        //Persist LocationHealthMessage
        $em->persist($location);
        $em->persist($locationHealthMessage);
        $em->flush();
    }

    private static function persistNewIllnessesOfLocationIfLatestAreNull(ObjectManager $em, Location $locationOfDestination, \DateTime $checkDate)
    {
        $latestActiveIllnessesDestination = Finder::findLatestActiveIllnessesOfLocation($locationOfDestination, $em);
        $previousMaediVisnaDestination = $latestActiveIllnessesDestination->get(Constant::MAEDI_VISNA);
        $previousScrapieDestination = $latestActiveIllnessesDestination->get(Constant::SCRAPIE);

        if($previousMaediVisnaDestination == null){
            $previousMaediVisnaDestination = self::persistNewDefaultMaediVisna($em, $locationOfDestination->getLocationHealth(), $checkDate);
            $latestActiveIllnessesDestination->set(Constant::MAEDI_VISNA, $previousMaediVisnaDestination);
        }
        if($previousScrapieDestination == null){
            $previousScrapieDestination = self::persistNewDefaultScrapie($em, $locationOfDestination->getLocationHealth(), $checkDate);
            $latestActiveIllnessesDestination->set(Constant::SCRAPIE, $previousScrapieDestination);
        }
        return $latestActiveIllnessesDestination;
    }

    /**
     * @param ObjectManager $em
     * @param DeclareArrival|DeclareImport $messageObject
     * @param LocationHealth $locationHealthDestination
     * @param LocationHealth $locationHealthOrigin
     * @param ArrayCollection $illnesses
     */
    private static function finalizeLocationHealthMessage(ObjectManager $em, $messageObject, $locationHealthDestination, $locationHealthOrigin, $illnesses)
    {
        $locationHealthMessage = LocationHealthMessageBuilder::finalize($em, $messageObject, $illnesses, $locationHealthDestination, $locationHealthOrigin);

        //Persist LocationHealthMessage
        $em->persist($locationHealthMessage);
        $em->flush();
    }

    /**
     * @param Location $locationOfDestination
     * @param \DateTime $checkDate
     * @return Location
     */
    private static function persistNewLocationHealthWithInitialValues(ObjectManager $em, Location $locationOfDestination, $checkDate)
    {
        //Create a LocationHealth with a MaediVisna and Scrapie with all statusses set to Under Observation
        $createWithDefaultUnderObservationIllnesses = true;
        $locationHealth = new LocationHealth($createWithDefaultUnderObservationIllnesses, $checkDate);
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
     * @param \DateTime $checkDate
     * @return LocationHealth
     */
    private static function persistNewDefaultMaediVisna(ObjectManager $em, LocationHealth $locationHealth, $checkDate)
    {
        $maediVisna = new MaediVisna(MaediVisnaStatus::UNDER_OBSERVATION);
        $maediVisna->setCheckDate($checkDate);
        $maediVisna->setLocationHealth($locationHealth);
        $locationHealth->addMaediVisna($maediVisna);
        $locationHealth->setCurrentMaediVisnaStatus($maediVisna->getStatus());

        $em->persist($maediVisna);
        $em->persist($locationHealth);
        $em->flush();

        return $maediVisna;
    }

    /**
     * @param ObjectManager $em
     * @param LocationHealth $locationHealth
     * @param \DateTime $checkDate
     * @return LocationHealth
     */
    private static function persistNewDefaultScrapie(ObjectManager $em, LocationHealth $locationHealth, $checkDate)
    {
        $scrapie = new Scrapie(ScrapieStatus::UNDER_OBSERVATION);
        $scrapie->setCheckDate($checkDate);
        $scrapie->setLocationHealth($locationHealth);
        $locationHealth->addScrapie($scrapie);
        $locationHealth->setCurrentScrapieStatus($scrapie->getStatus());

        $em->persist($scrapie);
        $em->persist($locationHealth);
        $em->flush();

        return $scrapie;
    }

    /**
     * Initialize LocationHealth entities and values of destination where necessary
     *
     * @param ObjectManager $em
     * @param Location $locationOfDestination
     * @param \DateTime $checkDate
     * @return Location
     */
    private static function persistInitialLocationHealthIfNull(ObjectManager $em, Location $locationOfDestination, $checkDate)
    {
        if($locationOfDestination == null) {
            return null;
        }
        
        $locationHealthDestination = $locationOfDestination->getLocationHealth();

        if($locationHealthDestination == null) {
            self::persistNewLocationHealthWithInitialValues($em, $locationOfDestination, $checkDate);

        } else {

            $maediVisnaDestination = Finder::findLatestActiveMaediVisna($locationOfDestination, $em);
            if ($maediVisnaDestination == null) {
                self::persistNewDefaultMaediVisna($em, $locationHealthDestination, $checkDate);
            }

            $scrapieDestination = Finder::findLatestActiveScrapie($locationOfDestination, $em);
            if ($scrapieDestination == null) {
                self::persistNewDefaultScrapie($em, $locationHealthDestination, $checkDate);
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

    /**
     * @param ObjectManager $em
     * @param Location $location
     * @param ArrayCollection $latestActiveIllnesses
     */
    private static function hideAllFollowingIllnesses(ObjectManager $em, Location $location, \DateTime $checkDate)
    {

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('locationHealth', $location->getLocationHealth()))
            ->andWhere(Criteria::expr()->gt('checkDate', $checkDate))
            ->orderBy(['checkDate' => Criteria::ASC]);

        $maediVisnas = $em->getRepository('AppBundle:MaediVisna')
            ->matching($criteria);

        foreach($maediVisnas as $maediVisna) {
            $maediVisna->setIsHidden(true);
            $em->persist($maediVisna);
        }
        $em->flush();

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('locationHealth', $location->getLocationHealth()))
            ->andWhere(Criteria::expr()->gt('checkDate', $checkDate))
            ->orderBy(['checkDate' => Criteria::ASC]);

        $scrapies = $em->getRepository('AppBundle:Scrapie')
            ->matching($criteria);

        foreach($scrapies as $scrapie) {
            $scrapie->setIsHidden(true);
            $em->persist($scrapie);
        }
        $em->flush();
    }
}