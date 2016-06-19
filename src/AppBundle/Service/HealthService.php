<?php

namespace AppBundle\Service;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\LocationHealthQueue;
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
}