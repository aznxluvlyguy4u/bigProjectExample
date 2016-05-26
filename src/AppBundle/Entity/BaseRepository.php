<?php

namespace AppBundle\Entity;

use AppBundle\Constant\Constant;
use AppBundle\Enumerator\RequestType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;

/**
 * Class BaseRepository
 * @package AppBundle\Entity
 */
class BaseRepository extends EntityRepository
{
    public function persist($entity)
    {
        $this->getEntityManager()->persist($entity);
        $this->update($entity);

        return $entity;
    }

    public function update($entity)
    {
        $this->getEntityManager()->flush($entity);

        return $entity;
    }

    protected function getRequests($requests, $state = null)
    {
        if($state == null) {
            return $requests;

        } else {
            $filteredRequests = new ArrayCollection();
            foreach($requests as $request) {
                if($request->getRequestState() == $state) {
                    $filteredRequests->add($request);
                }
            }
        }

        return $filteredRequests;
    }
    
    protected function getRequestByRequestId($requests, $requestId)
    {
        foreach($requests as $request) {
            $foundRequestId = $request->getRequestId();
            if($foundRequestId == $requestId) {
                return $request;
            }
        }

        return null;
    }

    protected function getResponseMessageFromRequestsByMessageNumber($requests, $messageNumber)
    {
        foreach($requests as $request) {
            foreach($request->getResponses() as $response) {
                if($response->getMessageNumber() == $messageNumber) {
                    return $response;
                }
            }
        }

        return null;
    }

    public function getLatestLogDate(Client $client, $entityType, $entityType2 = null)
    {
        $relationNumberKeeper = $client->getRelationNumberKeeper();

        //TODO Phase 2+ filter by UBN.
        if($entityType2 == null) {
            $sql = "SELECT MAX(log_date) FROM declare_base WHERE type = '" . $entityType."' AND relation_number_keeper ='" . $relationNumberKeeper . "'";

        } else {
            $sql = "SELECT MAX(log_date) FROM declare_base WHERE (type = '" . $entityType."' OR type = '" . $entityType2. "') AND relation_number_keeper = '"  . $relationNumberKeeper . "'";
        }

        $query = $this->getEntityManager()->getConnection()->prepare($sql);
        $query->execute();

        return new \DateTime($query->fetchColumn());
    }

    public function getLatestLogDatesForDashboardDeclarations(Client $client)
    {
        $repository = $this->getEntityManager()->getRepository(Constant::DECLARE_BASE_REPOSITORY);

        $latestArrivalLogdate = $repository->getLatestLogDate($client,RequestType::DECLARE_ARRIVAL_ENTITY, RequestType::DECLARE_IMPORT_ENTITY);
        $latestDepartLogdate = $repository->getLatestLogDate($client,RequestType::DECLARE_DEPART_ENTITY, RequestType::DECLARE_EXPORT_ENTITY);
        $latestLossLogdate = $repository->getLatestLogDate($client,RequestType::DECLARE_LOSS_ENTITY);
        $latestTagTransferLogdate = $repository->getLatestLogDate($client,RequestType::DECLARE_TAGS_TRANSFER_ENTITY);
        $latestBirthLogdate = $repository->getLatestLogDate($client,RequestType::DECLARE_BIRTH_ENTITY);

        $declarationLogDate = new ArrayCollection();
        $declarationLogDate->set(RequestType::DECLARE_ARRIVAL_ENTITY, $latestArrivalLogdate);
        $declarationLogDate->set(RequestType::DECLARE_DEPART_ENTITY, $latestDepartLogdate);
        $declarationLogDate->set(RequestType::DECLARE_LOSS_ENTITY, $latestLossLogdate);
        $declarationLogDate->set(RequestType::DECLARE_TAGS_TRANSFER_ENTITY, $latestTagTransferLogdate);
        $declarationLogDate->set(RequestType::DECLARE_BIRTH_ENTITY, $latestBirthLogdate);

        return $declarationLogDate;
    }
}
