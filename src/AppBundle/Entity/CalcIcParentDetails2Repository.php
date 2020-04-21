<?php

namespace AppBundle\Entity;

use Psr\Log\LoggerInterface;

/**
 * Class CalcIcParentDetails2Repository
 * @package AppBundle\Entity
 */
class CalcIcParentDetails2Repository extends CalcIcParentDetailsRepository implements CalcTableRepositoryInterface {

    function tableName(): string
    {
        return CalcIcParentDetails2::getTableName();
    }

    function clearTable(?LoggerInterface $logger = null)
    {
        $this->logClearingTable($logger, $this->tableName());
        $this->clearTableBase($this->tableName(), $logger);
    }


    function fill(?LoggerInterface $logger = null)
    {
        $this->fillBase(
            $logger,
            $this->tableName(),
            CalcIcParent2::getTableName()
        );
    }

}
