<?php

namespace AppBundle\Entity;

use AppBundle\model\metadata\YearMonthData;
use AppBundle\Util\SqlUtil;
use Doctrine\Common\Collections\Criteria;
use DateTime;

/**
 * Class InbreedingCoefficientTaskAdminRepository
 * @package AppBundle\Entity
 */
class InbreedingCoefficientTaskAdminRepository extends BaseRepository {

    private function tableName(): string
    {
        return InbreedingCoefficientTaskAdmin::getTableName();
    }

    /**
     * @param  array|YearMonthData[]  $yearsAndMonths
     * @param  DateTime $startedAt
     */
    function add(array $yearsAndMonths, DateTime $startedAt)
    {
        foreach ($yearsAndMonths as $yearMonthData) {
            $task = new InbreedingCoefficientTaskAdmin(
                $yearMonthData->getYear(),
                $yearMonthData->getMonth(),
                $startedAt
            );
            $this->getManager()->persist($task);
        }
        $this->getManager()->flush();
    }


    function next(): ?InbreedingCoefficientTaskAdmin
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
