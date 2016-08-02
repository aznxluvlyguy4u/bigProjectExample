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

        //Find the previous entities in the history
        //'previous' refers to the non-revoked LocationHealthMessage-DeclareArrival/DeclareImport and the related
        //illnesses right before the given one.

        if($declareIn instanceof DeclareArrival) {
            $criteria = Criteria::create()
                ->where(Criteria::expr()->lt('arrivalDate', $declareIn->getArrivalDate()))
                ->andWhere(Criteria::expr()->eq('location', $locationOfDestination))
                ->orderBy(['arrivalDate' => Criteria::DESC])
                ->setMaxResults(1);
        } else { //DeclareImport
            $criteria = Criteria::create()
                ->where(Criteria::expr()->lt('arrivalDate', $declareIn->getImportDate()))
                ->andWhere(Criteria::expr()->eq('location', $locationOfDestination))
                ->orderBy(['arrivalDate' => Criteria::DESC])
                ->setMaxResults(1);
        }

        $previousHealthMessageResults = $em->getRepository('AppBundle:LocationHealthMessage')
            ->matching($criteria);

        if($previousHealthMessageResults->count() > 0) {
            $previousHealthMessage = $previousHealthMessageResults->get(0);
        } else {
            $previousHealthMessage = null;
        }

        if($previousHealthMessage == null) {
            $latestActiveIllnessesDestination = self::persistNewIllnessesOfLocationIfLatestAreNull($em, $locationOfDestination, $checkDate); //includes a null check
            $previousMaediVisnaDestination = $latestActiveIllnessesDestination->get(Constant::MAEDI_VISNA);
            $previousScrapieDestination = $latestActiveIllnessesDestination->get(Constant::SCRAPIE);

        } else {
            $previousMaediVisnaDestination = $previousHealthMessage->getMaediVisna();
            $previousScrapieDestination = $previousHealthMessage->getScrapie();

            $latestActiveIllnessesDestination = Finder::findLatestActiveIllnessesOfLocation($locationOfDestination, $em); //returns null values if null
            if($previousMaediVisnaDestination == null) {
                $previousMaediVisnaDestination = $latestActiveIllnessesDestination->get(Constant::MAEDI_VISNA);
                if($previousMaediVisnaDestination == null) {
                    $previousMaediVisnaDestination = self::persistNewDefaultMaediVisna($em, $locationOfDestination->getLocationHealth(), $checkDate);
                }
            }

            if($previousScrapieDestination == null) {
                $previousScrapieDestination = $latestActiveIllnessesDestination->get(Constant::SCRAPIE);
                if($previousScrapieDestination == null) {
                    $previousScrapieDestination = self::persistNewDefaultScrapie($em, $locationOfDestination->getLocationHealth(), $checkDate);
                }
            }

            $latestActiveIllnessesDestination->set(Constant::MAEDI_VISNA, $previousMaediVisnaDestination);
            $latestActiveIllnessesDestination->set(Constant::SCRAPIE, $previousScrapieDestination);
        }

        $locationHealthDestination = $locationOfDestination->getLocationHealth();
        $previousMaediVisnaDestinationIsHealthy = HealthChecker::verifyIsMaediVisnaStatusHealthy($previousMaediVisnaDestination->getStatus());
        $previousScrapieDestinationIsHealthy = HealthChecker::verifyIsScrapieStatusHealthy($previousScrapieDestination->getStatus());

        //Default
        $latestMaediVisnaDestination = $previousMaediVisnaDestination;
        $latestScrapieDestination = $previousScrapieDestination;

        //Hide/Deactivate all illness records after that one. Even for statuses that didn't change to simplify the logic.
        if($isDeclareInBase) {
            self::hideAllFollowingIllnesses($em, $locationOfDestination, $checkDate);
        }

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

            $locationOfOrigin = self::persistInitialLocationHealthIfNull($em, $locationOfOrigin, $checkDate);

            $locationHealthOrigin = $locationOfOrigin->getLocationHealth();
            $maediVisnaOrigin = $locationHealthOrigin->getMaediVisnas()->last();
            $scrapieOrigin = $locationHealthOrigin->getScrapies()->last();
            $maediVisnaOriginIsHealthy = HealthChecker::verifyIsMaediVisnaStatusHealthy($maediVisnaOrigin->getStatus());
            $scrapieOriginIsHealthy = HealthChecker::verifyIsScrapieStatusHealthy($scrapieOrigin->getStatus());

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

            $maediVisnaDestination = $locationHealthDestination->getMaediVisnas()->last();
            if ($maediVisnaDestination == null) {
                self::persistNewDefaultMaediVisna($em, $locationHealthDestination, $checkDate);
            }

            $scrapieDestination = $locationHealthDestination->getScrapies()->last();
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