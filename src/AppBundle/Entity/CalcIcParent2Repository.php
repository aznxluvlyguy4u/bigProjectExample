<?php

namespace AppBundle\Entity;

use Psr\Log\LoggerInterface;

/**
 * Class CalcIcParent2Repository
 * @package AppBundle\Entity
 */
class CalcIcParent2Repository extends CalcIcParentRepository implements CalcIcParentRepositoryInterface {

    function tableName(): string
    {
        return CalcIcParent2::getTableName();
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
