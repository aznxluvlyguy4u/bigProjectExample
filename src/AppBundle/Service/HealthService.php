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
use AppBundle\Entity\LocationHealthQueue;
use AppBundle\Entity\LocationHealthQueueRepository;
use AppBundle\Entity\LocationHealthRepository;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Util\HealthChecker;
use AppBundle\Util\LocationHealthUpdater;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

class HealthService
{
    /** @var EntityManager */
    private $entityManager;

    /**
     * HealthService constructor.
     * @param $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }


    /**
     * @param DeclareArrival|DeclareImport $declareIn
     * @return null|ArrayCollection
     */
    public function updateLocationHealth($declareIn)
    {
        $location = $declareIn->getLocation();
        $em = $this->entityManager;

        if($declareIn instanceof DeclareArrival) {
            $ubnPreviousOwner = $declareIn->getUbnPreviousOwner();
            $checkDate = $declareIn->getArrivalDate();
            $result = LocationHealthUpdater::updateByGivenUbnOfOrigin($em, $location, $ubnPreviousOwner, $checkDate);

        } else if ($declareIn instanceof DeclareImport) {
            $checkDate = $declareIn->getImportDate();
            $result = LocationHealthUpdater::updateWithoutOriginHealthData($em, $location, $checkDate);

        } else {
            return null;
        }

        /* The LocationHealthMessage contains the LocationHealth history
          and must be calculated AFTER the locationHealth has been updated.
        */
        $locationHealthDestination = $result->get(Constant::LOCATION_HEALTH_DESTINATION);
        $locationHealthOrigin = $result->get(Constant::LOCATION_HEALTH_ORIGIN);
        $this->persistNewLocationHealthMessage($declareIn, $locationHealthDestination, $locationHealthOrigin);

        return $result;
    }


    /**
     * @param DeclareArrival|DeclareImport $messageObject
     * @param LocationHealth $locationHealthDestination
     * @param LocationHealth $locationHealthOrigin
     */
    private function persistNewLocationHealthMessage($messageObject, $locationHealthDestination, $locationHealthOrigin)
    {
        $locationHealthMessage = LocationHealthMessageBuilder::build($this->entityManager, $messageObject, $locationHealthDestination, $locationHealthOrigin);
        $location = $messageObject->getLocation();

        //Set LocationHealthMessage relationships
        $messageObject->setHealthMessage($locationHealthMessage);
        $location->addHealthMessage($locationHealthMessage);

        //Persist LocationHealthMessage
        $this->entityManager->persist($locationHealthMessage);
        $this->entityManager->flush();
    }
}