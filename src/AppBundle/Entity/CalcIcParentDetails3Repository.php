<?php

namespace AppBundle\Entity;

use Psr\Log\LoggerInterface;

/**
 * Class CalcIcParentDetails3Repository
 * @package AppBundle\Entity
 */
class CalcIcParentDetails3Repository extends CalcIcParentDetailsRepository implements CalcIcParentDetailsRepositoryInterface {

    function tableName(): string
    {
        return CalcIcParentDetails3::getTableName();
    }

    function clearTable(?LoggerInterface $logger = null)
    {
        $this->logClearingTable($logger, $this->tableName());
        $this->clearTableBase($this->tableName());
    }


    function fill(?LoggerInterface $logger = null)
    {
        $this->fillBase(
            $logger,
            $this->tableName(),
            CalcIcParent3::getTableName()
        );
    }

}
