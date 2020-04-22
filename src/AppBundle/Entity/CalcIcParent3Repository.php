<?php

namespace AppBundle\Entity;

use Psr\Log\LoggerInterface;

/**
 * Class CalcIcParent3Repository
 * @package AppBundle\Entity
 */
class CalcIcParent3Repository extends CalcIcParentRepository implements CalcIcParentRepositoryInterface {

    function tableName(): string
    {
        return CalcIcParent3::getTableName();
    }

    function clearTable(?LoggerInterface $logger = null)
    {
        $this->logClearingTable($logger, $this->tableName());
        $this->clearTableBase($this->tableName());
    }

    function fillByParentPairs(array $parentIdsPairs, ?LoggerInterface $logger = null)
    {
        $this->fillByParentPairsBase($this->tableName(), $parentIdsPairs, $logger);
    }

}
