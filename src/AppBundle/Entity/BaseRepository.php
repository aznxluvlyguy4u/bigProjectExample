<?php

namespace AppBundle\Entity;

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
            $foundRequestId = $request->getRequestId($requestId);
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
}
