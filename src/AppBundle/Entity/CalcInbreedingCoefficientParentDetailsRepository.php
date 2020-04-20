<?php

namespace AppBundle\Entity;

use Psr\Log\LoggerInterface;

/**
 * Class CalcInbreedingCoefficientParentRepository
 * @package AppBundle\Entity
 */
class CalcInbreedingCoefficientParentDetailsRepository extends CalcInbreedingCoefficientBaseRepository implements CalcTableRepositoryInterface {

    function tableName(): string
    {
        return CalcInbreedingCoefficientParentDetails::getTableName();
    }

    function truncate(?LoggerInterface $logger = null)
    {
        $this->logClearingTable($logger, $this->tableName());
        $this->truncateBase($this->tableName(), $logger);
    }

    private function subQuery(string $animalParentColumn)
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
                    INNER JOIN calc_inbreeding_coefficient_parent cp ON cp.animal_id = a.id
                    INNER JOIN animal parent ON parent.id = a.$animalParentColumn
                    LEFT JOIN inbreeding_coefficient ic on parent.inbreeding_coefficient_id = ic.id";
    }

    function fill(?LoggerInterface $logger = null)
    {
        $this->logFillingTableStart($logger, $this->tableName());

        $motherSubQuery = $this->subQuery('parent_mother_id');
        $fatherSubQuery = $this->subQuery('parent_father_id');

        $sql = "INSERT INTO calc_inbreeding_coefficient_parent_details
                    (animal_id, date_of_birth, parent_id, parent_date_of_birth, parent_type, parent_inbreeding_coefficient, is_primary_animal)
                $motherSubQuery
                UNION ALL
                $fatherSubQuery
                ";

        $this->getConnection()->executeQuery($sql);

        $this->logFillingTableEnd($logger, $this->tableName());
    }

}
