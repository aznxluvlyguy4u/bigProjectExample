<?php

namespace AppBundle\Entity;

use AppBundle\Constant\Constant;
use AppBundle\Enumerator\RequestType;
use AppBundle\Enumerator\RequestTypeNonIR;
use AppBundle\Util\SqlUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;

/**
 * Class BaseRepository
 * @package AppBundle\Entity
 */
class BaseRepository extends EntityRepository
{
    /**
     * @return ObjectManager|EntityManagerInterface
     */
    protected function getManager()
    {
        return $this->_em;
    }


    /**
     * @return Connection
     */
    protected function getConnection()
    {
        return $this->getManager()->getConnection();
    }


    public function persist($entity)
    {
        $this->getManager()->persist($entity);
        $this->update($entity);

        return $entity;
    }

    public function update($entity)
    {
        $this->getManager()->flush($entity);

        return $entity;
    }

    public function remove($entity)
    {
        $this->getManager()->remove($entity);

        return $entity;
    }


    protected function clearTableBase(string $tableName)
    {
        // DO NOT USE 'TRUNCATE TABLE table_name' because it does not lock the table, and can cause race condition bugs
        $sql = 'DELETE FROM '.$tableName;
        $this->_em->getConnection()->query($sql)->execute();
        SqlUtil::bumpPrimaryKeySeq($this->getConnection(), $tableName);
    }


    public function flush()
    {
        $this->getManager()->flush();
    }

    /**
     * @param QueryBuilder $qb
     * @param bool $onlyReturnQuery
     * @return mixed
     */
    protected function returnQueryOrResult(QueryBuilder $qb, $onlyReturnQuery = false)
    {
        $query = $qb->getQuery();

        if ($onlyReturnQuery) {
            return $query;
        }

        return $query->getResult();
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

        $query = $this->getManager()->getConnection()->prepare($sql);
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
        $repository = $this->getManager()->getRepository(Constant::DECLARE_BASE_REPOSITORY);

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

        $query = $this->getManager()->getConnection()->prepare($sql);
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

        $query = $this->getManager()->getConnection()->prepare($sql);
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
        $repository = $this->getManager()->getRepository(Constant::DECLARE_BASE_REPOSITORY);
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

        $query = $this->getManager()->getConnection()->prepare($sql);
        $query->execute();

        $results = array();

        foreach($query->fetchAll() as $item) {
            $message = $this->getManager()->getRepository('AppBundle:'.$item['type'])->find($item['id']);

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
        $query = $this->getManager()->getConnection()->prepare($sqlQuery);
        $query->execute();
        $result = $query->fetchColumn();

        if(!$result) {
            return null;
        } else {
            return $result;
        }
    }


    /**
     * @param $tableName
     * @param $animalIds
     * @return int
     */
    protected function deleteTableRecordsByTableNameAndAnimalIdsAndSql($tableName, $animalIds)
    {
        $animalIdFilterString = SqlUtil::getFilterStringByIdsArray($animalIds, 'animal_id');

        if($animalIdFilterString != '' && $animalIdFilterString != null) {

            $sql = "DELETE FROM $tableName WHERE ".$animalIdFilterString;
            return SqlUtil::updateWithCount($this->getConnection(), $sql);
        }
        return 0;
    }


    /**
     * @param array $results
     * @return mixed|null
     */
    protected function returnFirstQueryResult($results)
    {
        return count($results) === 0 ? null : array_shift($results);
    }


    /**
     * @param array|DeclareBaseInterface[] $entities
     * @return array|DeclareBaseInterface[]
     */
    public function setPrimaryKeysAsArrayKeys(array $entities): array
    {
        $result = [];
        foreach ($entities as $entity) {
            $result[$entity->getId()] = $entity;
        }
        return $result;
    }


    protected function sqlDeleteById(string $tableName, int $taskId, string $idColumn = 'id')
    {
        $sql = "DELETE FROM $tableName WHERE $idColumn = $taskId";
        $this->getConnection()->executeQuery($sql);
    }
}
