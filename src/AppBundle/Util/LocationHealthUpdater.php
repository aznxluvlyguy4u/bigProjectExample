<?php

namespace AppBundle\Util;

use AppBundle\Component\LocationHealthMessageBuilder;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\HealthCheckTask;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationHealth;
use AppBundle\Entity\LocationHealthMessage;
use AppBundle\Entity\MaediVisna;
use AppBundle\Entity\RetrieveAnimals;
use AppBundle\Entity\Scrapie;
use AppBundle\Enumerator\MaediVisnaStatus;
use AppBundle\Enumerator\ScrapieGenotypeType;
use AppBundle\Enumerator\ScrapieStatus;
use AppBundle\Exception\InvalidSwitchCaseException;
use AppBundle\Service\EmailService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Monolog\Logger;

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
    /** @var Logger */
    private $logger;

    public function __construct(EntityManagerInterface $em, EmailService $emailService, Logger $logger)
    {
        $this->em = $em;
        $this->emailService = $emailService;
        $this->logger = $logger;
    }


    /**
     * @param Location $locationOfDestination
     * @param DeclareArrival $declareArrival
     * @param Animal $animal
     * @param boolean $isDeclareInBase used to only hide the obsolete illnesses once at the beginning of the HealthUpdaterService for loop
     * @param boolean $createLocationHealthMessage
     * @return DeclareArrival
     * @throws InvalidSwitchCaseException
     */
    public function updateByGivenUbnOfOrigin(Location $locationOfDestination,
                                             DeclareArrival $declareArrival,
                                             Animal $animal,
                                             $isDeclareInBase,
                                             $createLocationHealthMessage
    )
    {
        $ubnPreviousOwner = $declareArrival->getUbnPreviousOwner();
        $checkDate = $declareArrival->getArrivalDate();

        $locationOfOrigin = $this->em->getRepository(Constant::LOCATION_REPOSITORY)->findOneByActiveUbn($ubnPreviousOwner);
        return $this->updateByGivenLocationOfOrigin($declareArrival ,$locationOfDestination, $checkDate,
            $isDeclareInBase, $locationOfOrigin, $animal, $createLocationHealthMessage);
    }


    /**
     * @param Location $locationOfDestination
     * @param DeclareImport $declareImport
     * @param Animal $animal
     * @param boolean $isDeclareInBase used to only hide the obsolete illnesses once at the beginning of the HealthUpdaterService for loop
     * @param boolean $createLocationHealthMessage
     * @return DeclareImport
     * @throws InvalidSwitchCaseException
     */
    public function updateWithoutOriginHealthData(Location $locationOfDestination,
                                                  DeclareImport $declareImport,
                                                  Animal $animal,
                                                  $isDeclareInBase,
                                                  $createLocationHealthMessage
    )
    {
        $checkDate = $declareImport->getImportDate();
        return $this->updateByGivenLocationOfOrigin($declareImport, $locationOfDestination, $checkDate,
            $isDeclareInBase, null, $animal, $createLocationHealthMessage);
    }


    /**
     * @param HealthCheckTask $healthCheckTask
     * @throws InvalidSwitchCaseException
     */
    public function updateByHealthCheckTaskFromRvoSync(HealthCheckTask $healthCheckTask)
    {
        $destinationLocation = $healthCheckTask->getDestinationLocation();
        if (LocationHealthUpdater::checkHealthStatus($destinationLocation)) {
            $animal = $this->em->getRepository(Animal::class)->findByHealthCheckTaskFromSync($healthCheckTask);

            if (!$animal) {
                $this->logger->warning('ANIMAL NOT FOUND IN DATABASE FOR HEALTH CHECK TASK, '
                    . 'ULN : ' . $healthCheckTask->getUlnNumber() .', '
                    . 'Destination: ' . $healthCheckTask->getDestinationUbn()
                );
            }

            $this->updateByGivenLocationOfOrigin($healthCheckTask,
                $destinationLocation, $healthCheckTask->getSyncDate(),
                true,null, $animal,true);

        } else {
            $this->logger->warning('No health checks will be done for UBN: ' . $destinationLocation->getUbn());
            $this->logger->warning('Health check task will not be processed');
        }
    }


    /**
     * @param DeclareArrival|DeclareImport|HealthCheckTask $declareInOrHealthCheckTask
     * @param Location $locationOfDestination
     * @param \DateTime $checkDate
     * @param Location|null $locationOfOrigin
     * @param Animal|null $animal
     * @param bool $recheckLocationHealthNullCheck used to only hide the obsolete illnesses once at the beginning of the HealthUpdaterService for loop
     * @param bool $createLocationHealthMessage
     * @return  DeclareArrival|DeclareImport|HealthCheckTask
     * @throws InvalidSwitchCaseException
     */
    private function updateByGivenLocationOfOrigin($declareInOrHealthCheckTask,
                                                   Location $locationOfDestination,
                                                   \DateTime $checkDate,
                                                   bool $recheckLocationHealthNullCheck,
                                                   ?Location $locationOfOrigin,
                                                   ?Animal $animal,
                                                   bool $createLocationHealthMessage
    )
    {
        $checkDate = TimeUtil::getDayOfDateTime($checkDate);
        $includeMaediVisna = LocationHealthUpdater::checkMaediVisnaStatus($locationOfDestination);
        $includeScrapie = LocationHealthUpdater::checkScrapieStatus($locationOfDestination);

        $this->logger->debug(($includeMaediVisna ? '' : 'NOT ') . 'Health checking MAEDI VISNA');
        $this->logger->debug(($includeScrapie ? '' : 'NOT ') . 'Health checking SCRAPIE');

        //Initializing the locationHealth if necessary. This is a fail safe. All locations should be created with their own locationHealth.
        $locationOfDestination = $this->persistInitialLocationHealthIfNull($locationOfDestination, $checkDate,
            $includeMaediVisna, $includeScrapie);

        $locationHealthMessage = null;
        if ($createLocationHealthMessage) {
            //Persist a new LocationHealthMessage for declareIn, without the healthValues
            $locationHealthMessage = self::persistNewLocationHealthMessage($declareInOrHealthCheckTask);
        }

        if($recheckLocationHealthNullCheck) {
            //Redo the null check in case all illnesses are made hidden
            $locationOfDestination = $this->persistInitialLocationHealthIfNull($locationOfDestination, $checkDate,
                $includeMaediVisna, $includeScrapie);
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

        $isScrapieStatusDemotingAnimal = self::isScrapieStatusDemotingAnimal($animal);

        if ($locationOfOrigin == null || // an import or Location that is not in our NSFO database
            !$locationOfOrigin->getAnimalHealthSubscription() // location where health statuses are not updated anymore
        ) {

            if ($includeMaediVisna &&
                !$previousMaediVisnaDestination->isStatusBlank()){
                $latestMaediVisnaDestination = $this->persistNewDefaultMaediVisnaAndHideFollowingOnes($locationHealthDestination, $checkDate);
                $locationHealthMessage->setCheckForMaediVisna(true);
            } //else do nothing

            if ($includeScrapie &&
                $isScrapieStatusDemotingAnimal){
                $latestScrapieDestination = $this->persistNewDefaultScrapieAndHideFollowingOnes($locationHealthDestination, $checkDate);
                $locationHealthMessage->setCheckForScrapie(true);
            } //else do nothing

            $locationHealthOrigin = null;


        } else { //location of origin is known and in the NSFO database

            $locationHealthOrigin = $locationOfOrigin->getLocationHealth();

            if ($includeMaediVisna) {

                $maediVisnaOrigin = Finder::findLatestActiveMaediVisna($locationOfOrigin, $this->em);
                if($maediVisnaOrigin != null) {
                    $maediVisnaOriginIsHealthy = HealthChecker::verifyIsMaediVisnaStatusHealthy($maediVisnaOrigin->getStatus());
                } else {
                    $maediVisnaOriginIsHealthy = false;
                }

                if(!$maediVisnaOriginIsHealthy && !$previousMaediVisnaDestination->isStatusBlank()){
                    $locationHealthMessage->setCheckForMaediVisna(true);
                    $latestMaediVisnaDestination = $this->persistNewDefaultMaediVisnaAndHideFollowingOnes($locationHealthDestination, $checkDate);
                } //else do nothing
            }

            if ($includeScrapie) {
                $checkByAnimalData = true;

                if ($checkByAnimalData) {
                    $animalMutationSourceIsScrapieStatusDemoting = $isScrapieStatusDemotingAnimal;

                } else {
                    $scrapieOrigin = Finder::findLatestActiveScrapie($locationOfOrigin, $this->em);
                    if($scrapieOrigin != null) {
                        $scrapieOriginIsHealthy = HealthChecker::verifyIsScrapieStatusHealthy($scrapieOrigin->getStatus());
                    } else {
                        $scrapieOriginIsHealthy = false;
                    }

                    $animalMutationSourceIsScrapieStatusDemoting = !$scrapieOriginIsHealthy &&
                        !$previousScrapieDestination->isStatusBlank()
                    ;
                }

                if ($animalMutationSourceIsScrapieStatusDemoting) {
                    $locationHealthMessage->setCheckForScrapie(true);
                    $latestScrapieDestination = $this->persistNewDefaultScrapieAndHideFollowingOnes($locationHealthDestination, $checkDate);
                } //else do nothing
            }

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
            $this->finalizeLocationHealthMessage($locationHealthMessage,
                $locationHealthDestination, $locationHealthOrigin, $illnesses);
        }

        return $declareInOrHealthCheckTask;
    }


    /**
     * @param Animal|null $animal
     * @return bool
     */
    public static function isScrapieStatusDemotingAnimal(?Animal $animal): bool
    {
        return $animal == null || self::isScrapieStatusDemotingGenotype($animal->getScrapieGenotype());
    }

    /**
     * @param null|string $scrapieGenotype
     * @return bool
     */
    public static function isScrapieStatusDemotingGenotype(?string $scrapieGenotype): bool
    {
        return $scrapieGenotype !== ScrapieGenotypeType::ARR_ARR;
    }

    /**
     * @param DeclareArrival|DeclareImport|HealthCheckTask $messageObject
     * @return LocationHealthMessage
     * @throws InvalidSwitchCaseException
     */
    private function persistNewLocationHealthMessage($messageObject)
    {
        $locationHealthMessage = LocationHealthMessageBuilder::prepare($messageObject);

        //Set LocationHealthMessage relationships
        if ($messageObject instanceof HealthCheckTask) {
            $location = $messageObject->getDestinationLocation();
        } else {
            $location = $messageObject->getLocation();
            $messageObject->setHealthMessage($locationHealthMessage);
        }

        $location->addHealthMessage($locationHealthMessage);

        //Persist LocationHealthMessage
        $this->em->persist($location);
        $this->em->persist($locationHealthMessage);
        $this->em->flush();

        return $locationHealthMessage;
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
     * @param LocationHealthMessage $locationHealthMessage
     * @param LocationHealth $locationHealthDestination
     * @param LocationHealth $locationHealthOrigin
     * @param ArrayCollection $illnesses
     */
    private function finalizeLocationHealthMessage(LocationHealthMessage $locationHealthMessage,
                                                   $locationHealthDestination, $locationHealthOrigin, $illnesses)
    {
        $locationHealthMessage = LocationHealthMessageBuilder::finalize($locationHealthMessage,
            $illnesses, $locationHealthDestination, $locationHealthOrigin);

        //Persist LocationHealthMessage
        $this->em->persist($locationHealthMessage);
        $this->em->flush();

        if ($locationHealthMessage->getCheckForMaediVisna() || $locationHealthMessage->getCheckForScrapie()) {
            $this->logger->debug('Sending health check notification ...');
            $sentEmail = $this->emailService->sendPossibleSickAnimalArrivalNotificationEmail($locationHealthMessage);
            $this->logger->debug(($sentEmail ? 'Email sent!' : 'FAILED sending email'));
        } else {
            $this->logger->debug('No  health check notification email is needed. Email not sent.');
        }
    }

    /**
     * @param EntityManagerInterface $em
     * @param Location $location
     * @param \DateTime $checkDate
     * @param bool $createDefaultMaediVisna
     * @param bool $createDefaultScrapie
     * @return Location
     */
    public static function persistNewLocationHealthWithInitialValues(EntityManagerInterface $em, Location $location,
                                                                     $checkDate,
                                                                     bool $createDefaultMaediVisna = true,
                                                                     bool $createDefaultScrapie = true
    )
    {
        //Create a LocationHealth with a MaediVisna and Scrapie with all statuses set to Under Observation
        $locationHealth = new LocationHealth();
        if ($createDefaultMaediVisna) {
            $locationHealth->createDefaultMaediVisna($checkDate);
        }
        if ($createDefaultScrapie) {
            $locationHealth->createDefaultScrapie($checkDate);
        }

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
        if ($locationHealth->getAnimalHealthSubscription()) {
            $maediVisna = new MaediVisna(MaediVisnaStatus::UNDER_OBSERVATION);
        } else {
            $maediVisna = new MaediVisna(MaediVisnaStatus::BLANK);
        }

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
        if ($locationHealth->getAnimalHealthSubscription()) {
            $scrapie = new Scrapie(ScrapieStatus::UNDER_OBSERVATION);
        } else {
            $scrapie = new Scrapie(ScrapieStatus::BLANK);
        }

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
     * @param bool $includeMaediVisna
     * @param bool $includeScrapie
     * @return Location
     */
    private function persistInitialLocationHealthIfNull(Location $locationOfDestination, $checkDate,
                                                        bool $includeMaediVisna = true,
                                                        bool $includeScrapie = true
    )
    {
        if($locationOfDestination == null) {
            return null;
        }
        
        $locationHealthDestination = $locationOfDestination->getLocationHealth();

        if($locationHealthDestination == null) {
            self::persistNewLocationHealthWithInitialValues($this->em, $locationOfDestination, $checkDate,
                $includeMaediVisna, $includeScrapie);

        } else {

            if ($includeMaediVisna) {
                $maediVisnaDestination = Finder::findLatestActiveMaediVisna($locationOfDestination, $this->em);
                if ($maediVisnaDestination == null) {
                    $this->persistNewDefaultMaediVisnaAndHideFollowingOnes($locationHealthDestination, $checkDate);
                }
            }

            if ($includeScrapie) {
                $scrapieDestination = Finder::findLatestActiveScrapie($locationOfDestination, $this->em);
                if ($scrapieDestination == null) {
                    $this->persistNewDefaultScrapieAndHideFollowingOnes($locationHealthDestination, $checkDate);
                }
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


    /**
     * @param Location $location
     * @return bool
     */
    public static function checkHealthStatus(Location $location): bool
    {
        return self::checkMaediVisnaStatus($location)
            || self::checkScrapieStatus($location)
            ;
    }


    /**
     * @param Location $location
     * @return bool
     */
    public static function checkMaediVisnaStatus(Location $location): bool
    {
        return $location->getAnimalHealthSubscription();
    }


    /**
     * @param Location $location
     * @return bool
     */
    public static function checkScrapieStatus(Location $location): bool
    {
        return $location->getAnimalHealthSubscription() || $location->hasNonBlankScrapieStatus();
    }
}