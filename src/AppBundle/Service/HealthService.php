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

    /** @var LocationHealthRepository */
    private $locationHealthRepository;

    /** @var LocationHealthQueueRepository */
    private $locationHealthQueueRepository;

    /** @var DeclareArrival */
    private $arrivalRepository;

    /** @var DeclareImport */
    private $importRepository;

    /**
     * HealthService constructor.
     * @param $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->locationHealthQueueRepository = $entityManager->getRepository(Constant::LOCATION_HEALTH_QUEUE_REPOSITORY);
        $this->locationHealthRepository = $entityManager->getRepository(Constant::LOCATION_HEALTH_REPOSITORY);
        $this->arrivalRepository = $entityManager->getRepository(Constant::DECLARE_ARRIVAL_REPOSITORY);
        $this->importRepository = $entityManager->getRepository(Constant::DECLARE_IMPORT_REPOSITORY);
    }

    /**
     * @return LocationHealthQueue
     */
    public function getLocationHealthQueue()
    {
        $repository = $this->locationHealthQueueRepository;
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


    public function processLocationHealthQueue()
    {
        $queue = $this->getLocationHealthQueue();

        /* TODO sort messages in RequestState types to enable much quicker batch processing
          For example:
           - For REVOKED: PER LOCATION only process the Revoked message with the oldest logDate/Arrival date
           - For FINISHED: PER LOCATION group them by locationOrigin and only process one of them.
           Don't forget to delete all FINISHED & REVOKED messages even if they are not use to calculate LocationHealth.
        */
        foreach($queue->getArrivals() as $arrival) {
            $this->processDeclaration($arrival, $queue);
        }

        foreach ($queue->getImports() as $import) {
            $this->processDeclaration($import, $queue);
        }
    }


    /**
     * @param DeclareArrival|DeclareImport $declareIn
     * @param LocationHealthQueue $queue
     */
    private function processDeclaration($declareIn, $queue)
    {
        switch($declareIn->getRequestState()){
            case RequestStateType::CANCELLED:
                $this->removeMessageFromLocationHealthQueue($declareIn, $queue);
                break;

            case RequestStateType::FAILED:
                $this->removeMessageFromLocationHealthQueue($declareIn, $queue);
                break;

            case RequestStateType::FINISHED:
                $this->processFinishedMessageInLocationHealthQueue($declareIn);
                $this->removeMessageFromLocationHealthQueue($declareIn, $queue);
                break;

            case RequestStateType::REVOKED:
                $this->processRevokedMessageInLocationHealthQueue($declareIn);
                $this->removeMessageFromLocationHealthQueue($declareIn, $queue);
                break;

            case RequestStateType::OPEN:
                //do nothing. Leave the message in the queue
                break;

            case RequestStateType::REVOKING:
                //do nothing. Leave the message in the queue
                break;

            default:
                //do nothing. Leave the message in the queue
                break;
        }
    }

    /**
     * @param DeclareArrival|DeclareImport $declareIn
     * @param LocationHealthQueue $queue
     */
    public function removeMessageFromLocationHealthQueue($declareIn, $queue)
    {
        $declareIn->setLocationHealthQueue(null);
        $queue->removeDeclaration($declareIn);

        $this->entityManager->persist($declareIn);
        $this->entityManager->persist($queue);
        $this->entityManager->flush();
    }


    /**
     * Check and persist LocationHealthStatus
     * and create a new LocationHealthMessage.
     *
     * @param DeclareArrival|DeclareImport $messageObject
     * @param boolean $doCreateLocationHealthMessage
     * @return null|DeclareArrival|DeclareImport
     */
    public function processFinishedMessageInLocationHealthQueue($messageObject, $doCreateLocationHealthMessage = true)
    {
        $location = $messageObject->getLocation();
        $animal = $messageObject->getAnimal();

        $em = $this->entityManager;
        $previousLocationHealthId = Utils::returnLastLocationHealth($location->getHealths())->getId();

        if($messageObject instanceof DeclareImport) {
            $location = LocationHealthUpdater::updateWithoutOriginHealthData($em, $location);

        } else if($messageObject instanceof DeclareArrival) {
            $location = LocationHealthUpdater::updateByGivenUbnOfOrigin($em, $location, $messageObject->getUbnPreviousOwner());
        } else {
            return null; //Only Imports and Arrivals are allowed into the function
        }


        $previousLocationHealthDestination = $this->locationHealthRepository->find($previousLocationHealthId);
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
        if(!$isLocationOriginCompletelyHealthy && $doCreateLocationHealthMessage) { //
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



    /**
     * @param DeclareArrival|DeclareImport $messageObject
     * @return DeclareArrival|DeclareImport
     */
    public function processRevokedMessageInLocationHealthQueue($messageObject)
    {
        $location = $messageObject->getLocation();

        $locationHealthRepository = $this->entityManager->getRepository(Constant::LOCATION_HEALTH_REPOSITORY);

        //Deactivate LocationHealths after the LocationHealth right before the revoke
        $idLocationHealthBeforeMessage = $messageObject->getHealthMessage()->getPreviousLocationHealthId();
        $locationHealthsToRevoke = $this->locationHealthRepository->getAllAfterId($idLocationHealthBeforeMessage, $location);

        foreach($locationHealthsToRevoke as $locationHealthToRevoke) {
            $locationHealthToRevoke->setIsRevoked(true);
            $this->locationHealthRepository->persist($locationHealthToRevoke);
        }
        $this->entityManager->flush();

        //Find starting point for recalculating LocationHealth
        $logDateBeforeRevoke = $messageObject->getLogDate(); //NOTE! LogDate of LocationHealth is NOT the same as for the message

        //Find all message after that starting point for recalculating LocationHealth in chronological order
        $arrivalsAndImports = $this->arrivalRepository->getArrivalsAndImportsAfterLogDateInChronologicalOrder($location, $logDateBeforeRevoke);

        //To get a correct new history, the LocationHealths have to be calculated one by one in chronological order.
        foreach($arrivalsAndImports as $arrivalsAndImport) {
            /* LocationHealthMessage is not dependent on LocationHealth status, but only on location of origin.
               so a different LocationHealth history due to a revoke will not change it. */
            $doCreateLocationHealthMessage = false;
            $this->processFinishedMessageInLocationHealthQueue($arrivalsAndImport, $doCreateLocationHealthMessage);
        }

        return $messageObject;
    }
}