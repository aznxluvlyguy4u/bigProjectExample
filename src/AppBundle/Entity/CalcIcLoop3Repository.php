<?php

namespace AppBundle\Entity;

use Psr\Log\LoggerInterface;

/**
 * Class CalcIcLoop3Repository
 * @package AppBundle\Entity
 */
class CalcIcLoop3Repository extends CalcIcLoopRepository implements CalcIcLoopRepositoryInterface {

    function tableName(): string
    {
        return CalcIcLoop3::getTableName();
    }

    function clearTable(?LoggerInterface $logger = null)
    {
        $this->logClearingTable($logger, $this->tableName());
        $this->clearTableBase($this->tableName());
    }


    /**
     * @param  int  $animalIdOrigin1
     * @param  int  $animalIdOrigin2
     * @param  LoggerInterface|null  $logger
     */
    function fill(int $animalIdOrigin1, int $animalIdOrigin2, ?LoggerInterface $logger = null)
    {
        $this->fillBase(
            $animalIdOrigin1,
            $animalIdOrigin2,
            $logger,
            $this->tableName(),
            CalcIcAscendantPath3::getTableName(),
            CalcIcParentDetails3::getTableName()
        );
    }


    public function calculateInbreedingCoefficientFromLoopsAndParentDetails(): float
    {
        return $this->calculateInbreedingCoefficientFromLoopsBase($this->tableName());
    }

}
