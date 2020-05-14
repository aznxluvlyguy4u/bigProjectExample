<?php

namespace AppBundle\Entity;

use AppBundle\Util\SqlUtil;
use Doctrine\Common\Collections\Criteria;

/**
 * Class InbreedingCoefficientTaskSmallRepository
 * @package AppBundle\Entity
 */
class InbreedingCoefficientTaskSmallRepository extends BaseRepository {

    private function tableName(): string
    {
        return InbreedingCoefficientTaskSmall::getTableName();
    }

    function exists(int $ramId, int $eweId, bool $checkRecalculateIsTrue = false): bool
    {
        $recalculateFilter = $checkRecalculateIsTrue ? 'AND recalculate' : '';
        $sql = "SELECT recalculate FROM inbreeding_coefficient_task_small t 
WHERE ram_id = $ramId AND ewe_id = $eweId ".$recalculateFilter;
        return $this->getConnection()->query($sql)->fetchColumn() ?? false;
    }


    /**
     * @param  int  $ramId
     * @param  int  $eweId
     * @param  bool  $recalculate
     */
    function add(int $ramId, int $eweId, bool $recalculate = false)
    {
        if (!$this->exists($ramId, $eweId, $recalculate)) {
            $task = new InbreedingCoefficientTaskSmall(
                $ramId, $eweId, $recalculate
            );
            $this->getManager()->persist($task);
        }
    }


    function next(): ?InbreedingCoefficientTaskSmall
    {
        return $this->findOneBy([],['id' => Criteria::ASC]);
    }


    function bumpSequence()
    {
        SqlUtil::bumpPrimaryKeySeq($this->getConnection(), $this->tableName());
    }

    function deleteTask(int $taskId)
    {
        $this->sqlDeleteById($this->tableName(), $taskId);
    }

    function purgeQueue()
    {
        $this->clearTableBase($this->tableName());
    }
}
