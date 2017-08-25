<?php

namespace AppBundle\Service;


use AppBundle\Component\LocationHealthMessageBuilder;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationHealth;
use AppBundle\Entity\LocationHealthMessage;
use AppBundle\Entity\LocationHealthQueue;
use AppBundle\Entity\LocationHealthQueueRepository;
use AppBundle\Entity\LocationHealthRepository;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Util\Finder;
use AppBundle\Util\HealthChecker;
use AppBundle\Util\LocationHealthUpdater;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectManager;

class HealthUpdaterService
{
    /** @var ObjectManager */
    private $entityManager;

    /**
     * HealthUpdaterService constructor.
     * @param $entityManager
     */
    public function __construct(ObjectManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }


    /**
     * @param DeclareArrival|DeclareImport $declareInBase
     */
    public function updateLocationHealth($declareInBase)
    {
        $location = $declareInBase->getLocation();

        //update locationHealth chronologically
        $isDeclareInBase = true;
        $this->updateLocationHealthByArrivalOrImport($location, $declareInBase, $isDeclareInBase);

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
            $this->updateLocationHealthByArrivalOrImport($location, $declareIn, $isDeclareInBase);
        }
    }

    /**
     * @param Location $location
     * @param DeclareArrival|DeclareImport $declareIn
     * @param boolean $isDeclareBaseIn
     */
    private function updateLocationHealthByArrivalOrImport(Location $location, $declareIn, $isDeclareBaseIn)
    {
        $em = $this->entityManager;

        if($declareIn instanceof DeclareArrival) {
            LocationHealthUpdater::updateByGivenUbnOfOrigin($em, $location, $declareIn, $isDeclareBaseIn);

        } else if ($declareIn instanceof DeclareImport) {
            LocationHealthUpdater::updateWithoutOriginHealthData($em, $location, $declareIn, $isDeclareBaseIn);

        } else {
            //do nothing
        }
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