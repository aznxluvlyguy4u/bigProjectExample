<?php

namespace AppBundle\Entity;

use AppBundle\Setting\InbreedingCoefficientSetting;
use Psr\Log\LoggerInterface;

/**
 * Class CalcInbreedingCoefficientParentRepository
 * @package AppBundle\Entity
 */
class CalcInbreedingCoefficientParentRepository extends CalcInbreedingCoefficientBaseRepository implements CalcTableRepositoryInterface {

    function tableName(): string
    {
        return CalcInbreedingCoefficientParent::getTableName();
    }

    function truncate(?LoggerInterface $logger = null)
    {
        $this->logClearingTable($logger, $this->tableName());
        $this->truncateBase($this->tableName(), $logger);
    }

    function fill(string $filter = '', ?LoggerInterface $logger = null, string $logSuffix = '')
    {
        $this->logFillingTableStart($logger, $this->tableName(), $logSuffix);

        $maxGenerations = $this->maxGenerations();

        $sql = "INSERT INTO calc_inbreeding_coefficient_parent (animal_id, is_primary_animal)
                WITH RECURSIVE parents(animal_id, parents_array, depth)
                           AS (
                SELECT
                    a.id as animal_id,
                    (concat('{', a.parent_father_id, ',', a.parent_mother_id, '}') :: int []) as parents_array,
                    1 AS depth,
                    true as is_primary_animal
                FROM animal a
                         INNER JOIN animal mom ON mom.id = a.parent_mother_id
                         INNER JOIN animal dad ON dad.id = a.parent_father_id
                WHERE
                    a.id < 100 AND
                    a.parent_father_id NOTNULL AND a.parent_mother_id NOTNULL
                  AND a.date_of_birth NOTNULL AND mom.date_of_birth NOTNULL AND dad.date_of_birth NOTNULL
                UNION ALL
                SELECT
                    a.id as animal_id,
                    (concat('{', a.parent_father_id, ',', a.parent_mother_id, '}') :: int []) as parents_array,
                    p.depth + 1 AS depth,
                    false as is_primary_animal
                FROM parents AS p
                    INNER JOIN animal a ON a.id = ANY (p.parents_array)
                    INNER JOIN animal mom ON mom.id = a.parent_mother_id
                    INNER JOIN animal dad ON dad.id = a.parent_father_id
                WHERE a.parent_father_id NOTNULL AND a.parent_mother_id NOTNULL
                  AND a.date_of_birth NOTNULL AND mom.date_of_birth NOTNULL AND dad.date_of_birth NOTNULL
                  AND p.depth <= ($maxGenerations - 1)
            )
        SELECT
            animal_id,
            bool_or(is_primary_animal) as is_primary_animal
        FROM parents
        GROUP BY animal_id";
        $this->getConnection()->executeQuery($sql);

        $this->logFillingTableEnd($logger, $this->tableName());
    }


    function fillByYearAndMonth(int $year, int $month, ?LoggerInterface $logger = null)
    {
        $filter = $this->animalYearAndMonthFilter($year, $month) . " AND ";
        $logSuffix = " table for animal with a birth date within year-month $year-$month";
        return $this->fill($filter, $logger, $logSuffix);
    }
}
