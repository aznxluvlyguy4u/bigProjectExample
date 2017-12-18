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
use AppBundle\Service\EmailService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;

//TODO Nothing is done with the endDates yet.

/**
 * Class LocationHealthUpdater
 * @package AppBundle\Util
 */
class LocationHealthUpdater
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var EmailService */
    private $emailService;

    public function __construct(EntityManagerInterface $em, EmailService $emailService)
    {
        $this->em = $em;
        $this->emailService = $emailService;
    }


    /**
     * @param Location $locationOfDestination
     * @param DeclareArrival $declareArrival
     * @param boolean $isDeclareInBase used to only hide the obsolete illnesses once at the beginning of the HealthUpdaterService for loop
     * @param boolean $createLocationHealthMessage
     * @return DeclareArrival
     */
    public function updateByGivenUbnOfOrigin(Location $locationOfDestination,
                                             DeclareArrival $declareArrival,
                                             $isDeclareInBase,
                                             $createLocationHealthMessage
    )
    {
        $ubnPreviousOwner = $declareArrival->getUbnPreviousOwner();
        $checkDate = $declareArrival->getArrivalDate();

        $locationOfOrigin = $this->em->getRepository(Constant::LOCATION_REPOSITORY)->findOneByActiveUbn($ubnPreviousOwner);
        return self::updateByGivenLocationOfOrigin($declareArrival ,$locationOfDestination, $checkDate,
            $isDeclareInBase, $locationOfOrigin, $createLocationHealthMessage);
    }


    /**
     * @param Location $locationOfDestination
     * @param DeclareImport $declareImport
     * @param boolean $isDeclareInBase used to only hide the obsolete illnesses once at the beginning of the HealthUpdaterService for loop
     * @param boolean $createLocationHealthMessage
     * @return DeclareImport
     */
    public function updateWithoutOriginHealthData(Location $locationOfDestination,
                                                  DeclareImport $declareImport,
                                                  $isDeclareInBase,
                                                  $createLocationHealthMessage
    )
    {
        $checkDate = $declareImport->getImportDate();
        return self::updateByGivenLocationOfOrigin($declareImport, $locationOfDestination, $checkDate,
            $isDeclareInBase, null, $createLocationHealthMessage);
    }



    /**
     * @param Location $locationOfDestination
     * @param DeclareArrival|DeclareImport $declareIn
     * @param \DateTime $checkDate
     * @param boolean $isDeclareInBase used to only hide the obsolete illnesses once at the beginning of the HealthUpdaterService for loop
     * @param Location $locationOfOrigin
     * @param boolean $createLocationHealthMessage
     * @return DeclareArrival|DeclareImport
     */
    private function updateByGivenLocationOfOrigin($declareIn,
                                                   Location $locationOfDestination,
                                                   \DateTime $checkDate,
                                                   $isDeclareInBase,
                                                   $locationOfOrigin,
                                                   $createLocationHealthMessage
    )
    {
        $checkDate = TimeUtil::getDayOfDateTime($checkDate);

        //Initializing the locationHealth if necessary. This is a fail safe. All locations should be created with their own locationHealth.
        $locationOfDestination = $this->persistInitialLocationHealthIfNull($locationOfDestination, $checkDate);

        if ($createLocationHealthMessage) {
            //Persist a new LocationHealthMessage for declareIn, without the healthValues
            self::persistNewLocationHealthMessage($declareIn);
        }

        if($isDeclareInBase) {
            //Redo the null check in case all illnesses are made hidden
            $locationOfDestination = $this->persistInitialLocationHealthIfNull($locationOfDestination, $checkDate);
        }

        //Get the latest values
        $latestActiveIllnessesDestination = Finder::findLatestActiveIllnessesOfLocation($locationOfDestination, $this->em); //returns null values if null
        /** @var MaediVisna $previousMaediVisnaDestination */
        $previousMaediVisnaDestination = $latestActiveIllnessesDestination->get(Constant::MAEDI_VISNA);
        /** @var Scrapie $previousScrapieDestination */
        $previousScrapieDestination = $latestActiveIllnessesDestination->get(Constant::SCRAPIE);

        $locationHealthDestination = $locationOfDestination->getLocationHealth(); //Null check already done in the first command of this function
        $previousMaediVisnaDestinationIsHealthy = HealthChecker::verifyIsMaediVisnaStatusHealthy($previousMaediVisnaDestination->getStatus());
        $previousScrapieDestinationIsHealthy = HealthChecker::verifyIsScrapieStatusHealthy($previousScrapieDestination->getStatus());

        //Initialize default values
        $latestMaediVisnaDestination = $previousMaediVisnaDestination;
        $latestScrapieDestination = $previousScrapieDestination;


        //Do the health check ...

        if($locationOfOrigin == null) { //an import or Location that is not in our NSFO database

            if($previousMaediVisnaDestinationIsHealthy){
                $latestMaediVisnaDestination = $this->persistNewDefaultMaediVisnaAndHideFollowingOnes($locationHealthDestination, $checkDate);
            } //else do nothing

            if($previousScrapieDestinationIsHealthy){
                $latestScrapieDestination = $this->persistNewDefaultScrapieAndHideFollowingOnes($locationHealthDestination, $checkDate);
            } //else do nothing

            $locationHealthOrigin = null;


        } else { //location of origin is known and in the NSFO database

            $locationHealthOrigin = $locationOfOrigin->getLocationHealth();

            $maediVisnaOrigin = Finder::findLatestActiveMaediVisna($locationOfOrigin, $this->em);
            if($maediVisnaOrigin != null) {
                $maediVisnaOriginIsHealthy = HealthChecker::verifyIsMaediVisnaStatusHealthy($maediVisnaOrigin->getStatus());
            } else {
                $maediVisnaOriginIsHealthy = false;
            }

            $scrapieOrigin = Finder::findLatestActiveScrapie($locationOfOrigin, $this->em);
            if($scrapieOrigin != null) {
                $scrapieOriginIsHealthy = HealthChecker::verifyIsScrapieStatusHealthy($scrapieOrigin->getStatus());
            } else {
                $scrapieOriginIsHealthy = false;
            }


            if(!$maediVisnaOriginIsHealthy && $previousMaediVisnaDestinationIsHealthy){
                $latestMaediVisnaDestination = $this->persistNewDefaultMaediVisnaAndHideFollowingOnes($locationHealthDestination, $checkDate);
            } //else do nothing

            if(!$scrapieOriginIsHealthy && $previousScrapieDestinationIsHealthy) {
                $latestScrapieDestination = $this->persistNewDefaultScrapieAndHideFollowingOnes($locationHealthDestination, $checkDate);
            } //else do nothing

            $this->persistTheOverallLocationHealthStatus($locationOfOrigin); //FIXME see function
        }

        $this->persistTheOverallLocationHealthStatus($locationOfDestination); //FIXME see function


        $illnesses = new ArrayCollection();
        $illnesses->set(Constant::MAEDI_VISNA, $latestMaediVisnaDestination);
        $illnesses->set(Constant::SCRAPIE, $latestScrapieDestination);

        if ($createLocationHealthMessage) {
            /* The LocationHealthMessage contains the LocationHealth history
                and must be calculated AFTER the locationHealth has been updated.
            */
            $this->finalizeLocationHealthMessage($declareIn, $locationHealthDestination, $locationHealthOrigin, $illnesses);
        }

        return $declareIn;
    }

    /**
     * @param DeclareArrival|DeclareImport $messageObject
     */
    private function persistNewLocationHealthMessage($messageObject)
    {
        $locationHealthMessage = LocationHealthMessageBuilder::prepare($messageObject);
        $location = $messageObject->getLocation();

        //Set LocationHealthMessage relationships
        $messageObject->setHealthMessage($locationHealthMessage);
        $location->addHealthMessage($locationHealthMessage);

        //Persist LocationHealthMessage
        $this->em->persist($location);
        $this->em->persist($locationHealthMessage);
        $this->em->flush();
    }

    private function persistNewIllnessesOfLocationIfLatestAreNull(Location $locationOfDestination, \DateTime $checkDate)
    {
        $latestActiveIllnessesDestination = Finder::findLatestActiveIllnessesOfLocation($locationOfDestination, $this->em);
        $previousMaediVisnaDestination = $latestActiveIllnessesDestination->get(Constant::MAEDI_VISNA);
        $previousScrapieDestination = $latestActiveIllnessesDestination->get(Constant::SCRAPIE);

        if($previousMaediVisnaDestination == null){
            $previousMaediVisnaDestination = $this->persistNewDefaultMaediVisnaAndHideFollowingOnes($locationOfDestination->getLocationHealth(), $checkDate);
            $latestActiveIllnessesDestination->set(Constant::MAEDI_VISNA, $previousMaediVisnaDestination);
        }
        if($previousScrapieDestination == null){
            $previousScrapieDestination = $this->persistNewDefaultScrapieAndHideFollowingOnes($locationOfDestination->getLocationHealth(), $checkDate);
            $latestActiveIllnessesDestination->set(Constant::SCRAPIE, $previousScrapieDestination);
        }
        return $latestActiveIllnessesDestination;
    }

    /**
     * @param DeclareArrival|DeclareImport $messageObject
     * @param LocationHealth $locationHealthDestination
     * @param LocationHealth $locationHealthOrigin
     * @param ArrayCollection $illnesses
     */
    private function finalizeLocationHealthMessage($messageObject, $locationHealthDestination, $locationHealthOrigin, $illnesses)
    {
        $locationHealthMessage = LocationHealthMessageBuilder::finalize($messageObject, $illnesses, $locationHealthDestination, $locationHealthOrigin);

        //Persist LocationHealthMessage
        $this->em->persist($locationHealthMessage);
        $this->em->flush();

        if ($locationHealthMessage->getCheckForMaediVisna() || $locationHealthMessage->getCheckForScrapie()) {
            $this->emailService->sendPossibleSickAnimalArrivalNotificationEmail($locationHealthMessage);
        }
    }

    /**
     * @param EntityManagerInterface $em
     * @param Location $location
     * @param \DateTime $checkDate
     * @return Location
     */
    public static function persistNewLocationHealthWithInitialValues(EntityManagerInterface $em, Location $location, $checkDate)
    {
        //Create a LocationHealth with a MaediVisna and Scrapie with all statusses set to Under Observation
        $createWithDefaultUnderObservationIllnesses = true;
        $locationHealth = new LocationHealth($createWithDefaultUnderObservationIllnesses, $checkDate);
        $location->setLocationHealth($locationHealth);
        $locationHealth->setLocation($location);

        $em->persist($locationHealth->getMaediVisnas()->get(0));
        $em->persist($locationHealth->getScrapies()->get(0));
        $em->persist($locationHealth);
        $em->persist($location);
        $em->flush();

        return $location;
    }

    /**
     * @param LocationHealth $locationHealth
     * @param \DateTime $checkDate
     * @return MaediVisna
     */
    private function persistNewDefaultMaediVisnaAndHideFollowingOnes(LocationHealth $locationHealth, $checkDate)
    {
        $maediVisna = new MaediVisna(MaediVisnaStatus::UNDER_OBSERVATION);
        $maediVisna->setCheckDate($checkDate);
        $maediVisna->setLocationHealth($locationHealth);
        $locationHealth->addMaediVisna($maediVisna);
        $locationHealth->setCurrentMaediVisnaStatus($maediVisna->getStatus());

        $this->em->persist($maediVisna);
        $this->em->persist($locationHealth);
        $this->em->flush();

        self::hideAllFollowingMaediVisnas($this->em, $locationHealth->getLocation(), $checkDate);

        return $maediVisna;
    }

    /**
     * @param LocationHealth $locationHealth
     * @param \DateTime $checkDate
     * @return Scrapie
     */
    private function persistNewDefaultScrapieAndHideFollowingOnes(LocationHealth $locationHealth, $checkDate)
    {
        $scrapie = new Scrapie(ScrapieStatus::UNDER_OBSERVATION);
        $scrapie->setCheckDate($checkDate);
        $scrapie->setLocationHealth($locationHealth);
        $locationHealth->addScrapie($scrapie);
        $locationHealth->setCurrentScrapieStatus($scrapie->getStatus());

        $this->em->persist($scrapie);
        $this->em->persist($locationHealth);
        $this->em->flush();

        self::hideAllFollowingScrapies($this->em, $locationHealth->getLocation(), $checkDate);

        return $scrapie;
    }

    /**
     * Initialize LocationHealth entities and values of destination where necessary
     *
     * @param Location $locationOfDestination
     * @param \DateTime $checkDate
     * @return Location
     */
    private function persistInitialLocationHealthIfNull(Location $locationOfDestination, $checkDate)
    {
        if($locationOfDestination == null) {
            return null;
        }
        
        $locationHealthDestination = $locationOfDestination->getLocationHealth();

        if($locationHealthDestination == null) {
            self::persistNewLocationHealthWithInitialValues($this->em, $locationOfDestination, $checkDate);

        } else {

            $maediVisnaDestination = Finder::findLatestActiveMaediVisna($locationOfDestination, $this->em);
            if ($maediVisnaDestination == null) {
                $this->persistNewDefaultMaediVisnaAndHideFollowingOnes($locationHealthDestination, $checkDate);
            }

            $scrapieDestination = Finder::findLatestActiveScrapie($locationOfDestination, $this->em);
            if ($scrapieDestination == null) {
                $this->persistNewDefaultScrapieAndHideFollowingOnes($locationHealthDestination, $checkDate);
            }
        }

        return $locationOfDestination;
    }


    /**
     * @param Location $location
     * @return Location
     */
    private function persistTheOverallLocationHealthStatus(Location $location)
    {
        //TODO remove the (overall) locationHealthStatus from LocationHealth in conjuction with the Java entities.
        //For now the value is just set to null.

        if($location->getLocationHealth() != null) {
            $location->getLocationHealth()->setLocationHealthStatus(null);
            $this->em->persist($location);
            $this->em->flush();
        }

        return $location;
    }


    /**
     * @param Location $location
     * @param \DateTime $checkDate
     * @return Criteria
     */
    private static function getHideCriteria(Location $location, \DateTime $checkDate)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('locationHealth', $location->getLocationHealth()))
            ->andWhere(DateCriteria::gt('checkDate', $checkDate))
            ->orderBy(['checkDate' => Criteria::ASC]);

        return $criteria;
    }


    /**
     * @param ObjectManager $em
     * @param Location $location
     * @param \DateTime $checkDate
     */
    public static function hideAllFollowingMaediVisnas(ObjectManager $em, Location $location, \DateTime $checkDate)
    {
        $maediVisnas = $em->getRepository(MaediVisna::class)
            ->matching(self::getHideCriteria($location, $checkDate));

        /** @var MaediVisna $maediVisna */
        foreach($maediVisnas as $maediVisna) {
            $maediVisna->setIsHidden(true);
            $em->persist($maediVisna);
        }
        $em->flush();
    }


    /**
     * @param ObjectManager $em
     * @param Location $location
     * @param \DateTime $checkDate
     */
    public static function hideAllFollowingScrapies(ObjectManager $em, Location $location, \DateTime $checkDate)
    {
        $scrapies = $em->getRepository(Scrapie::class)
            ->matching(self::getHideCriteria($location, $checkDate));

        /** @var Scrapie $scrapie */
        foreach($scrapies as $scrapie) {
            $scrapie->setIsHidden(true);
            $em->persist($scrapie);
        }
        $em->flush();
    }


}