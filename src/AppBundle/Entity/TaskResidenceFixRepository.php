<?php

namespace AppBundle\Entity;

use AppBundle\Util\SqlUtil;
use Doctrine\Common\Collections\Criteria;
use DateTime;

/**
 * Class TaskResidenceFixRepository
 * @package AppBundle\Entity
 */
class TaskResidenceFixRepository extends BaseRepository {

    private function tableName(): string
    {
        return TaskResidenceFix::getTableName();
    }


    function exists(int $locationId): int
    {
        return $this->getConnection()->query(
            "SELECT * FROM ".$this->tableName()." WHERE location_id = $locationId"
            )->rowCount() > 0;
    }


    /**
     * @param  array|int[]  $locationIds
     * @param  DateTime $startedAt
     * return int tasks added count
     */
    function add(array $locationIds, DateTime $startedAt): int
    {
        $addedCount = 0;
        foreach ($locationIds as $locationId) {
            if (!$this->exists($locationId)) {
                $task = new TaskResidenceFix(
                    $locationId,
                    $startedAt
                );
                $this->getManager()->persist($task);
                $addedCount++;
            }
        }
        return $addedCount;
    }


    function next(): ?TaskResidenceFix
    {
        return $this->findOneBy([],['id' => Criteria::ASC]);
    }


    function purgeQueue()
    {
        parent::clearTableBase($this->tableName());
    }


    function bumpSequence()
    {
        SqlUtil::bumpPrimaryKeySeq($this->getConnection(), tableName());
    }


    function deleteTask(int $taskId)
    {
        $this->sqlDeleteById($this->tableName(), $taskId);
    }
}
