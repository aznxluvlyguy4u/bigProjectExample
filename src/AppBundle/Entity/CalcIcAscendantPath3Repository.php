<?php

namespace AppBundle\Entity;

use Psr\Log\LoggerInterface;

/**
 * Class CalcIcAscendantPath3Repository
 * @package AppBundle\Entity
 */
class CalcIcAscendantPath3Repository extends CalcIcAscendantPathRepository implements CalcIcAscendantPathRepositoryInterface {

    function tableName(): string
    {
        return CalcIcAscendantPath3::getTableName();
    }

    function clearTable(?LoggerInterface $logger = null)
    {
        $this->logClearingTable($logger, $this->tableName());
        $this->clearTableBase($this->tableName());
    }

    /**
     * @param  LoggerInterface|null  $logger
     */
    function fill(?LoggerInterface $logger = null)
    {
        $this->fillBase(
            $logger,
            $this->tableName(),
            CalcIcParentDetails3::getTableName()
        );
    }

}

