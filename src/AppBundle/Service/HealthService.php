<?php

namespace AppBundle\Service;


use AppBundle\Component\LocationHealthMessageBuilder;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationHealthQueue;
use AppBundle\Util\HealthChecker;
use AppBundle\Util\LocationHealthUpdater;
use Doctrine\ORM\EntityManager;

class HealthService
{
    /** @var EntityManager */
    private $entityManager;

    /**
     * HealthService constructor.
     * @param $entityManager
     */
    public function __construct($entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return LocationHealthQueue
     */
    public function getLocationHealthQueue()
    {
        $repository = $this->entityManager->getRepository(Constant::LOCATION_HEALTH_QUEUE_REPOSITORY);
        $queues = $repository->findAll();
        $count = sizeof($queues);

        if($count == 1) {
            return $queues[0];

        } else if($count == 0) {
            $repository->persist(new LocationHealthQueue());
            $this->entityManager->flush();
            return $repository->findAll()[0];

        } else { //count > 1
            $combinedLocationHealthQueue = Utils::combineLocationHealthQueues($queues);
            $repository->persist($combinedLocationHealthQueue);

            foreach($queues as $queue) {

                foreach($queue->getArrivals() as $arrival) {
                    $arrival->setLocationHealthQueue($combinedLocationHealthQueue);
                    $this->entityManager->persist($arrival);
                }
                foreach($queue->getImports() as $import) {
                    $import->setLocationHealthQueue($combinedLocationHealthQueue);
                    $this->entityManager->persist($import);
                }

                $repository->remove($queue);
            }

            $this->entityManager->flush();
            return $repository->findAll()[0];
        }
    }

    /**
     * @param DeclareArrival|DeclareImport $arrivalOrImport
     * @return LocationHealthQueue
     */
    public function updateLocationHealthQueue($arrivalOrImport)
    {
        $locationHealthQueue = $this->getLocationHealthQueue();
        $locationHealthQueue->addDeclaration($arrivalOrImport);
        $arrivalOrImport->setLocationHealthQueue($locationHealthQueue);
        $this->entityManager->persist($locationHealthQueue);
        $this->entityManager->persist($arrivalOrImport);
        $this->entityManager->flush();

        return $this->getLocationHealthQueue();
    }


    /**
     * @param DeclareArrival|DeclareImport $messageObject
     * @param Location $location
     * @param  Animal $animal
     * @return null|DeclareArrival|DeclareImport
     */
    public function checkAndPersistLocationHealthStatusAndCreateNewLocationHealthMessage($messageObject, $location, $animal)
    {
        $em = $this->entityManager;
        $previousLocationHealthId = Utils::returnLastLocationHealth($location->getHealths())->getId();

        if($messageObject instanceof DeclareImport) {
            $location = LocationHealthUpdater::updateWithoutOriginHealthData($em, $location);

        } else if($messageObject instanceof DeclareArrival) {
            $location = LocationHealthUpdater::updateByGivenUbnOfOrigin($em, $location, $messageObject->getUbnPreviousOwner());
        } else {
            return null; //Only Imports and Arrivals are allowed into the function
        }


        $previousLocationHealthDestination = $em->getRepository(Constant::LOCATION_HEALTH_REPOSITORY)->find($previousLocationHealthId);
        $newLocationHealthDestination = Utils::returnLastLocationHealth($location->getHealths());

        $isLocationCompletelyHealthy = HealthChecker::verifyIsLocationCompletelyHealthy($location);
        $isLocationOriginCompletelyHealthy = HealthChecker::verifyIsLocationOriginCompletelyHealthy($messageObject, Utils::getClassName($messageObject), $em);
        $hasLocationHealthChanged = HealthChecker::verifyHasLocationHealthChanged($previousLocationHealthDestination, $newLocationHealthDestination);

        /* LocationHealth Update */
        if(!$isLocationCompletelyHealthy && $hasLocationHealthChanged)
        {
            //Persist HealthStatus
            $messageObject->setLocation($location);
            $em->persist($newLocationHealthDestination);
            $em->persist($messageObject);
            $em->flush();

        } else {
            $previousLocationHealthId--;
        }


        /* LocationHealthMessage */
        if(!$isLocationOriginCompletelyHealthy) {
            $locationHealthMessage = LocationHealthMessageBuilder::build($em, $messageObject, $previousLocationHealthId, $animal);

            //Set LocationHealthMessage relationships
            $messageObject->setHealthMessage($locationHealthMessage);
            $location->addHealthMessage($locationHealthMessage);

            //Persist LocationHealthMessage
            $em->persist($locationHealthMessage);
            $em->flush();
        }

        return $messageObject;
    }
}