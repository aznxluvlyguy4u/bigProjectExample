<?php

namespace AppBundle\Entity;

use Psr\Log\LoggerInterface;

/**
 * Class CalcIcParentDetailsRepository
 * @package AppBundle\Entity
 */
class CalcIcParentDetailsRepository extends CalcInbreedingCoefficientBaseRepository implements CalcIcParentDetailsRepositoryInterface {

    function tableName(): string
    {
        return CalcIcParentDetails::getTableName();
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
            CalcIcParent::getTableName()
        );
    }


    protected function fillBase(
        ?LoggerInterface $logger = null,
        string $parentDetailsTableName,
        string $parentTableName
    )
    {
        $this->logFillingTableStart($logger, $this->tableName());

        $motherSubQuery = $this->subQuery('parent_mother_id', $parentTableName);
        $fatherSubQuery = $this->subQuery('parent_father_id', $parentTableName);

        $sql = "INSERT INTO $parentDetailsTableName
                    (animal_id, date_of_birth, parent_id, parent_date_of_birth, parent_type, parent_inbreeding_coefficient, is_primary_animal)
                $motherSubQuery
                UNION ALL
                $fatherSubQuery
                ";

        $this->getConnection()->executeQuery($sql);

        $this->logFillingTableEnd($logger, $this->tableName());
    }


    private function subQuery(string $animalParentColumn, string $parentTableName)
    {
        return "SELECT
                    a.id as animal_id,
                    a.date_of_birth,
                    a.$animalParentColumn as parent_id,
                    parent.date_of_birth as parent_date_of_birth,
                    parent.type as parent_type,
                    ic.value as parent_inbreeding_coefficient,
                    cp.is_primary_animal
                FROM animal a
                    INNER JOIN $parentTableName cp ON cp.animal_id = a.id
                    INNER JOIN animal parent ON parent.id = a.$animalParentColumn
                    LEFT JOIN inbreeding_coefficient ic on parent.inbreeding_coefficient_id = ic.id";
    }

}
