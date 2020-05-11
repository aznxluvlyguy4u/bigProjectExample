<?php

namespace AppBundle\Entity;

use AppBundle\Util\SqlUtil;
use Doctrine\Common\Collections\Criteria;

/**
 * Class InbreedingCoefficientTaskReportRepository
 * @package AppBundle\Entity
 */
class InbreedingCoefficientTaskReportRepository extends BaseRepository {

    private function tableName(): string
    {
        return InbreedingCoefficientTaskReport::getTableName();
    }

    /**
     * @param  array|int[]  $ramIds
     * @param  array|int[]  $eweIds
     */
    function add(array $ramIds, array $eweIds)
    {
        $task = new InbreedingCoefficientTaskReport(
            $ramIds,$eweIds
        );
        $this->getManager()->persist($task);
        $this->getManager()->flush();
    }


    function next(): ?InbreedingCoefficientTaskReport
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
