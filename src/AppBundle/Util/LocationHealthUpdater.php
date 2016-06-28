<?php

namespace AppBundle\Util;

use AppBundle\Component\LocationHealthMessageBuilder;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationHealth;
use AppBundle\Entity\MaediVisna;
use AppBundle\Entity\Scrapie;
use AppBundle\Enumerator\MaediVisnaStatus;
use AppBundle\Enumerator\ScrapieStatus;
use AppBundle\Constant\Constant;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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


        //Find the previous entities in the history
        //'previous' refers to the non-revoked LocationHealthMessage-DeclareArrival/DeclareImport and the related illnesses right before the given one.

        $locationHealthMessages = $locationOfDestination->getHealthMessages();
        $messageCount = $locationHealthMessages->count();
        $keyCurrentLocationHealthMessage = Finder::findLocationHealthMessageArrayKey($declareIn);

        if($messageCount == 0 || $keyCurrentLocationHealthMessage == 0) {
            $latestActiveIllnessesDestination = Finder::findLatestActiveIllnessesOfLocation($locationOfDestination);
        } else {
            $keyPreviousLocationHealthMessage = Finder::findKeyPreviousNonRevokedLocationHealthMessage($locationHealthMessages, $keyCurrentLocationHealthMessage);

            if($keyPreviousLocationHealthMessage == null) {
                $latestActiveIllnessesDestination = Finder::findLatestActiveIllnessesOfLocation($locationOfDestination);
            } else {
                $latestActiveIllnessesDestination = Finder::findIllnessesByArrayKey($locationHealthMessages, $keyPreviousLocationHealthMessage);
            }
        }

        $previousMaediVisnaDestination = $latestActiveIllnessesDestination->get(Constant::MAEDI_VISNA);
        $previousScrapieDestination = $latestActiveIllnessesDestination->get(Constant::SCRAPIE);
        $locationHealthDestination = $locationOfDestination->getLocationHealth();
        $previousMaediVisnaDestinationIsHealthy = HealthChecker::verifyIsMaediVisnaStatusHealthy($previousMaediVisnaDestination->getStatus());
        $previousScrapieDestinationIsHealthy = HealthChecker::verifyIsScrapieStatusHealthy($previousScrapieDestination->getStatus());

        //Default
        $latestMaediVisnaDestination = $previousMaediVisnaDestination;
        $latestScrapieDestination = $previousScrapieDestination;

        //Hide/Deactivate all illness records after that one. Even for statuses that didn't change to simplify the logic.
        if($isDeclareInBase) {
            self::hideAllFollowingIllnesses($em, $locationOfDestination, $latestActiveIllnessesDestination);
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
        self::persistNewLocationHealthMessage($em, $declareIn, $locationHealthDestination, $locationHealthOrigin, $illnesses);

        return $declareIn;
    }

    /**
     * @param ObjectManager $em
     * @param DeclareArrival|DeclareImport $messageObject
     * @param LocationHealth $locationHealthDestination
     * @param LocationHealth $locationHealthOrigin
     * @param ArrayCollection $illnesses
     */
    private static function persistNewLocationHealthMessage(ObjectManager $em, $messageObject, $locationHealthDestination, $locationHealthOrigin, $illnesses)
    {
        $locationHealthMessage = LocationHealthMessageBuilder::build($em, $messageObject, $illnesses, $locationHealthDestination, $locationHealthOrigin);
        $location = $messageObject->getLocation();

        //Set LocationHealthMessage relationships
        $messageObject->setHealthMessage($locationHealthMessage);
        $location->addHealthMessage($locationHealthMessage);

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
    private static function hideAllFollowingIllnesses(ObjectManager $em, Location $location, ArrayCollection $latestActiveIllnesses)
    {
        $maediVisnas = $location->getLocationHealth()->getMaediVisnas(); //ordered by checkDate
        $scrapies = $location->getLocationHealth()->getScrapies(); //ordered by checkDate

        $maediVisna = $latestActiveIllnesses->get(Constant::MAEDI_VISNA);
        $scrapie = $latestActiveIllnesses->get(Constant::SCRAPIE);

        self::hideFollowingMaediVisnas($em, $maediVisnas, $maediVisna);
        self::hideFollowingScrapies($em, $scrapies, $scrapie);
    }

    /**
     * @param ObjectManager $em
     * @param Collection $maediVisnas ordered in ascending order by checkDate
     * @param MaediVisna $baseMaediVisna
     * @return int
     */
    private static function hideFollowingMaediVisnas(ObjectManager $em, $maediVisnas, $baseMaediVisna)
    {
        $idBase = $baseMaediVisna->getId();
        $maediVisnaCount = $maediVisnas->count();

        for($i = $maediVisnaCount-1; $i >=0; $i--) {
            $maediVisna = $maediVisnas->get($i);
            if($idBase == $maediVisna->getId()) {
                return $maediVisnaCount-$i+1; //number of MaediVisnas made hidden
            } else {
                $maediVisna->setIsHidden(true);
                $em->persist($maediVisna);
                $em->flush();
            }
        }
    }

    /**
     * @param ObjectManager $em
     * @param Collection $scrapies ordered in ascending order by checkDate
     * @param Scrapie $baseScrapie
     * @return int
     */
    private static function hideFollowingScrapies(ObjectManager $em, $scrapies, $baseScrapie)
    {
        $idBase = $baseScrapie->getId();
        $scrapieCount = $scrapies->count();

        for($i = $scrapieCount-1; $i >=0; $i--) {
            $scrapie = $scrapies->get($i);
            if($idBase == $scrapie->getId()) {
                return $scrapieCount-$i+1;//number of Scrapies made hidden
            } else {
                $scrapie->setIsHidden(true);
                $em->persist($scrapie);
                $em->flush();
            }
        }
    }
}