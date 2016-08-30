<?php

namespace AppBundle\Entity;

use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Enumerator\RequestType;
use AppBundle\Enumerator\RequestTypeNonIR;
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

    public function remove($entity)
    {
        $this->getEntityManager()->remove($entity);

        return $entity;
    }


    public function flush()
    {
        $this->getEntityManager()->flush();
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


    /**
     * @param Client $client
     * @param string $entityType
     * @param string|null $entityType2
     * @return \DateTime
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getLatestLogDate(Client $client, $entityType, $entityType2 = null)
    {
        $relationNumberKeeper = $client->getRelationNumberKeeper();

        if($entityType2 == null) {
            $sql = "SELECT MAX(log_date) FROM declare_base WHERE type = '" . $entityType."' AND relation_number_keeper ='" . $relationNumberKeeper . "'";

        } else {
            $sql = "SELECT MAX(log_date) FROM declare_base WHERE (type = '" . $entityType."' OR type = '" . $entityType2. "') AND relation_number_keeper = '"  . $relationNumberKeeper . "'";
        }

        $query = $this->getEntityManager()->getConnection()->prepare($sql);
        $query->execute();
        $result = $query->fetchColumn();

        if(!$result) {
            return null;
        } else {
            return new \DateTime($result);
        }
    }

    /**
     * @param Client $client
     * @return ArrayCollection
     */
    public function getLatestLogDatesForDashboardDeclarations(Client $client)
    {
        /** @var DeclareBaseRepository $repository */
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


    /**
     * @param string $ubn
     * @param string $entityType
     * @param string|null $entityType2
     * @return \DateTime
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getLatestLogDatePerUbn($ubn, $entityType, $entityType2 = null)
    {
        if($entityType2 == null) {
            $sql = "SELECT MAX(log_date) FROM declare_base WHERE type = '" . $entityType."' AND ubn ='" . $ubn . "'";

        } else {
            $sql = "SELECT MAX(log_date) FROM declare_base WHERE (type = '" . $entityType."' OR type = '" . $entityType2. "') AND ubn = '"  . $ubn . "'";
        }

        $query = $this->getEntityManager()->getConnection()->prepare($sql);
        $query->execute();
        $result = $query->fetchColumn();

        if(!$result) {
            return null;
        } else {
            return new \DateTime($result);
        }
    }

    /**
     * @param string $ubn
     * @param string $nonIrNsfoDeclarationType
     * @return \DateTime
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getLatestNsfoDeclarationLogDatePerUbn($ubn, $nonIrNsfoDeclarationType)
    {
        $sql = "SELECT MAX(log_date) FROM declare_nsfo_base WHERE type = '" . $nonIrNsfoDeclarationType."' AND ubn ='" . $ubn . "'";

        $query = $this->getEntityManager()->getConnection()->prepare($sql);
        $query->execute();
        $result = $query->fetchColumn();

        if(!$result) {
            return null;
        } else {
            return new \DateTime($result);
        }
    }

    /**
     * @param Location $location
     * @param string $errorMessageForDateIsNull
     * @return ArrayCollection
     */
    public function getLatestLogDatesForDashboardDeclarationsPerLocation(Location $location, $errorMessageForDateIsNull = null)
    {
        /** @var DeclareBaseRepository $repository */
        $repository = $this->getEntityManager()->getRepository(Constant::DECLARE_BASE_REPOSITORY);
        $ubn = $location->getUbn();

        $latestArrivalLogdate = $repository->getLatestLogDatePerUbn($ubn,RequestType::DECLARE_ARRIVAL_ENTITY, RequestType::DECLARE_IMPORT_ENTITY);
        if($latestArrivalLogdate == null) {
            $latestArrivalLogdate = $errorMessageForDateIsNull;
        }

        $latestDepartLogdate = $repository->getLatestLogDatePerUbn($ubn,RequestType::DECLARE_DEPART_ENTITY, RequestType::DECLARE_EXPORT_ENTITY);
        if($latestDepartLogdate == null) {
            $latestDepartLogdate = $errorMessageForDateIsNull;
        }

        $latestLossLogdate = $repository->getLatestLogDatePerUbn($ubn,RequestType::DECLARE_LOSS_ENTITY);
        if($latestLossLogdate == null) {
            $latestLossLogdate = $errorMessageForDateIsNull;
        }

        $latestTagTransferLogdate = $repository->getLatestLogDatePerUbn($ubn,RequestType::DECLARE_TAGS_TRANSFER_ENTITY);
        if($latestTagTransferLogdate == null) {
            $latestTagTransferLogdate = $errorMessageForDateIsNull;
        }

        $latestBirthLogdate = $repository->getLatestLogDatePerUbn($ubn,RequestType::DECLARE_BIRTH_ENTITY);
        if($latestBirthLogdate == null) {
            $latestBirthLogdate = $errorMessageForDateIsNull;
        }
        
        $latestMateLogDate = $repository->getLatestNsfoDeclarationLogDatePerUbn($ubn,RequestTypeNonIR::MATE);
        if($latestMateLogDate == null) {
            $latestMateLogDate = $errorMessageForDateIsNull;
        }
        
        $declarationLogDate = new ArrayCollection();
        $declarationLogDate->set(RequestType::DECLARE_ARRIVAL_ENTITY, $latestArrivalLogdate);
        $declarationLogDate->set(RequestType::DECLARE_DEPART_ENTITY, $latestDepartLogdate);
        $declarationLogDate->set(RequestType::DECLARE_LOSS_ENTITY, $latestLossLogdate);
        $declarationLogDate->set(RequestType::DECLARE_TAGS_TRANSFER_ENTITY, $latestTagTransferLogdate);
        $declarationLogDate->set(RequestType::DECLARE_BIRTH_ENTITY, $latestBirthLogdate);
        $declarationLogDate->set(RequestTypeNonIR::MATE, $latestMateLogDate);

        return $declarationLogDate;
    }
    
    
    public function getArrivalsAndImportsAfterLogDateInChronologicalOrder(Location $location, \DateTime $logDate)
    {
        //TODO A LOT MORE OPTIMIZATION IS NEEDED HERE
        
        $ubn = $location->getUbn();

        $arrivalType = RequestType::DECLARE_ARRIVAL_ENTITY;
        $importType = RequestType::DECLARE_IMPORT_ENTITY;

        $sql = "SELECT id, log_date, type FROM declare_base
                WHERE (type = '" . $arrivalType."' OR type = '" . $importType. "')
                AND ubn = '"  . $ubn . "'
                ORDER BY log_date ASC";

        $query = $this->getEntityManager()->getConnection()->prepare($sql);
        $query->execute();

        $results = array();

        foreach($query->fetchAll() as $item) {
            $message = $this->getEntityManager()->getRepository('AppBundle:'.$item['type'])->find($item['id']);

            if($message->getLogDate() > $logDate) {
                $results[] = $message;
            }
        }

        return $results;
    }


    /**
     * @param string $sqlQuery
     * @return bool|null|mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function executeSqlQuery($sqlQuery)
    {
        $query = $this->getEntityManager()->getConnection()->prepare($sqlQuery);
        $query->execute();
        $result = $query->fetchColumn();

        if(!$result) {
            return null;
        } else {
            return $result;
        }
    }
}
