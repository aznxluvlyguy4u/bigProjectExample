<?php

namespace AppBundle\Entity;

use Psr\Log\LoggerInterface;

/**
 * Class CalcIcAscendantPath2Repository
 * @package AppBundle\Entity
 */
class CalcIcAscendantPath2Repository extends CalcIcAscendantPathRepository implements CalcTableRepositoryInterface {

    function tableName(): string
    {
        return CalcIcAscendantPath2::getTableName();
    }

    function clearTable(?LoggerInterface $logger = null)
    {
        $this->logClearingTable($logger, $this->tableName());
        $this->clearTableBase($this->tableName(), $logger);
    }

    /**
     * @param  LoggerInterface|null  $logger
     */
    function fill(?LoggerInterface $logger = null)
    {
        $this->fillBase(
            $logger,
            $this->tableName(),
            CalcIcParentDetails2::getTableName()
        );
    }

}

