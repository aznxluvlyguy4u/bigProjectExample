<?php

namespace AppBundle\Service;


use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationHealthMessage;
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

        if($declareInBase instanceof DeclareArrival) {
            $criteria = Criteria::create()
                ->where(Criteria::expr()->gt('arrivalDate', $declareInBase->getArrivalDate()))
                ->andWhere(Criteria::expr()->eq('location', $location))
                ->orderBy(['arrivalDate' => Criteria::ASC]);
        } else { //DeclareImport
            $criteria = Criteria::create()
                ->where(Criteria::expr()->gt('arrivalDate', $declareInBase->getImportDate()))
                ->andWhere(Criteria::expr()->eq('location', $location))
                ->orderBy(['arrivalDate' => Criteria::ASC]);
        }

        $locationHealthMessages = $this->entityManager->getRepository('AppBundle:LocationHealthMessage')
            ->matching($criteria);

        $isDeclareInBase = false;
        foreach($locationHealthMessages as $locationHealthMessage) {
            $declareIn = $locationHealthMessage->getRequest();
            $this->updateLocationHealthByArrivalOrImport($location, $declareIn, $isDeclareInBase, false);
        }
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

                    $this->updateLocationHealth($messageObject);
                }
            }

        }
    }

    /**
     * @param Location $location
     */
    public function fixArrivalsAndImportsWithoutLocationHealthMessage(Location $location)
    {
        $em = $this->entityManager;
        
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('location', $location))
            ->andWhere(Criteria::expr()->eq('healthMessage', null))
            ->orderBy(['arrivalDate' => Criteria::ASC]);

        $arrivalsWithoutLocationHealthMessage = $em->getRepository(DeclareArrival::class)
            ->matching($criteria);

        if($arrivalsWithoutLocationHealthMessage->count() > 0) {
            foreach ($arrivalsWithoutLocationHealthMessage as $arrival) {
                $this->updateLocationHealth($arrival);
            }
        }

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('location', $location))
            ->andWhere(Criteria::expr()->eq('healthMessage', null))
            ->orderBy(['importDate' => Criteria::ASC]);

        $importsWithoutLocationHealthMessage = $em->getRepository(DeclareImport::class)
            ->matching($criteria);

        if($importsWithoutLocationHealthMessage->count() > 0) {
            foreach ($importsWithoutLocationHealthMessage as $import) {
                $this->updateLocationHealth($import);
            }
        }
    }
}