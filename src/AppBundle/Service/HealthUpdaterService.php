<?php

namespace AppBundle\Service;


use AppBundle\Constant\Constant;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationHealthMessage;
use AppBundle\Entity\MaediVisna;
use AppBundle\Entity\Scrapie;
use AppBundle\Util\Finder;
use AppBundle\Util\LocationHealthUpdater;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;

class HealthUpdaterService
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var LocationHealthUpdater $locationHealthUpdater */
    private $locationHealthUpdater;

    /**
     * HealthUpdaterService constructor.
     * @param EntityManagerInterface $entityManager
     * @param LocationHealthUpdater $locationHealthUpdater
     */
    public function __construct(EntityManagerInterface $entityManager, LocationHealthUpdater $locationHealthUpdater)
    {
        $this->entityManager = $entityManager;
        $this->locationHealthUpdater = $locationHealthUpdater;
    }


    /**
     * @param DeclareArrival|DeclareImport $declareInBase
     */
    public function updateLocationHealth($declareInBase)
    {
        $location = $declareInBase->getLocation();

        //update locationHealth chronologically
        $isDeclareInBase = true;
        $this->updateLocationHealthByArrivalOrImport($location, $declareInBase, $isDeclareInBase, true);

        /*
         * Warning!
         *
         * While recursivelyRecalculatingPreviousArrivalsAndImports
         * will more accurately represent the illness status based on animal residence history,
         * this will mess up the administrative illness status, and will create a lot of "duplicate" recalculated health history!
         *
         * So that code has been deleted. Don't add that stuff again.
         */
    }

    /**
     * @param Location $location
     * @param DeclareArrival|DeclareImport $declareIn
     * @param boolean $isDeclareBaseIn
     * @param boolean $createLocationHealthMessage
     */
    private function updateLocationHealthByArrivalOrImport(Location $location, $declareIn, $isDeclareBaseIn,
                                                           $createLocationHealthMessage)
    {
        if($declareIn instanceof DeclareArrival) {
            $this->locationHealthUpdater->updateByGivenUbnOfOrigin($location, $declareIn, $isDeclareBaseIn,
                $createLocationHealthMessage);

        } else if ($declareIn instanceof DeclareImport) {
            $this->locationHealthUpdater->updateWithoutOriginHealthData($location, $declareIn, $isDeclareBaseIn,
                $createLocationHealthMessage);

        }
        // else do nothing
    }

    /**
     * The issue of LocationHealthMessages without any persisted related illnesses occurs if the LocationHealthUpdate
     * is suddenly aborted halfway.
     *
     * @param Location $location
     */
    public function fixLocationHealthMessagesWithNullValues(Location $location)
    {
        $em = $this->entityManager;

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('location', $location))
            ->andWhere(Criteria::expr()->eq('maediVisna', null))
            ->orWhere(Criteria::expr()->eq('scrapie', null))
            ->orderBy(['arrivalDate' => Criteria::ASC]);

        $locationHealthMessagesWithNull = $em->getRepository(LocationHealthMessage::class)
            ->matching($criteria);


        if($locationHealthMessagesWithNull->count() > 0) {

            foreach ($locationHealthMessagesWithNull as $locationHealthMessage) {
                $messageObject = $locationHealthMessage->getRequest();
                if ($messageObject == null) {
                    $em->remove($locationHealthMessage);
                }
            }
            $em->flush();

            /** @var LocationHealthMessage $locationHealthMessage */
            foreach ($locationHealthMessagesWithNull as $locationHealthMessage) {
                $messageObject = $locationHealthMessage->getRequest();

                if($messageObject != null) {
                    if($messageObject->getLocation() == null) {
                        $messageObject->setLocation($location);
                    }
                }
            }

        }
    }


    /**
     * @param Location $location
     */
    public function fixIncongruentLocationHealthIllnessValues(Location $location)
    {
        //Get the latest values
        $latestActiveIllnessesDestination = Finder::findLatestActiveIllnessesOfLocation($location, $this->entityManager);
        /** @var MaediVisna $previousMaediVisnaDestination */
        $latestMaediVisna = $latestActiveIllnessesDestination->get(Constant::MAEDI_VISNA);
        /** @var Scrapie $previousScrapieDestination */
        $latestScrapie = $latestActiveIllnessesDestination->get(Constant::SCRAPIE);

        if ($location->getLocationHealth() === null) {
            LocationHealthUpdater::persistNewLocationHealthWithInitialValues($this->entityManager, $location, new \DateTime('today'));
        }

        $locationHealth = $location->getLocationHealth();

        $anyValueChanged = false;
        if ($locationHealth->getCurrentScrapieStatus() !== $latestScrapie->getStatus()) {
            $locationHealth->setCurrentScrapieStatus($latestScrapie->getStatus());
            $anyValueChanged = true;
        }

        if ($locationHealth->getCurrentMaediVisnaStatus() !== $latestMaediVisna->getStatus()) {
            $locationHealth->setCurrentMaediVisnaStatus($latestMaediVisna->getStatus());
            $anyValueChanged = true;
        }

        if ($locationHealth->getCurrentScrapieEndDate() !== $latestScrapie->getEndDate()) {
            $locationHealth->setCurrentScrapieEndDate($latestScrapie->getEndDate());
            $anyValueChanged = true;
        }

        if ($locationHealth->getCurrentMaediVisnaEndDate() !== $latestMaediVisna->getEndDate()) {
            $locationHealth->setCurrentMaediVisnaEndDate($latestMaediVisna->getEndDate());
            $anyValueChanged = true;
        }

        if ($anyValueChanged) {
            $this->entityManager->persist($locationHealth);
            $this->entityManager->flush();
        }
    }


}