<?php

namespace AppBundle\Entity;

use AppBundle\model\metadata\YearMonthData;
use AppBundle\Util\SqlUtil;
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

    private function fill(string $filter = '', ?LoggerInterface $logger = null, string $logSuffix = '')
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
                    $filter
                      a.parent_father_id NOTNULL AND a.parent_mother_id NOTNULL AND 
                      a.date_of_birth NOTNULL AND mom.date_of_birth NOTNULL AND dad.date_of_birth NOTNULL
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
        $alias = 'child';
        $yearMonthFilter = $this->animalYearAndMonthFilter($year, $month, $alias);
        $filter = "(
            EXISTS (
                    SELECT
                        $alias.parent_father_id
                    FROM animal $alias
                    WHERE $yearMonthFilter
                        AND $alias.parent_father_id = a.id
                )
            OR
            EXISTS (
                    SELECT
                        $alias.parent_mother_id
                    FROM animal $alias
                    WHERE $yearMonthFilter
                      AND $alias.parent_mother_id = a.id
                )
            ) AND ";

        $logSuffix = " table for animal with a birth date within year-month $year-$month";
        return $this->fill($filter, $logger, $logSuffix);
    }


    function fillByParentPairs(array $parentIdsPairs, ?LoggerInterface $logger = null)
    {
        $count = count($parentIdsPairs);
        $filter = SqlUtil::getParentsAsAnimalIdsFilterFromParentIdsPairs($parentIdsPairs, 'a') . " AND ";
        $logSuffix = " table for $count custom parent pairs";
        return $this->fill($filter, $logger, $logSuffix);
    }


    /**
     * @return array|YearMonthData[]
     */
    function getAllYearsAndMonths(): array
    {
        $sql = "SELECT date_part('YEAR', a.date_of_birth) as year,
                       date_part('MONTH', a.date_of_birth) as month,
                       COUNT(*) as count
                FROM animal a
                WHERE date_of_birth NOTNULL AND inbreeding_coefficient_match_updated_at ISNULL

                GROUP BY date_part('YEAR', a.date_of_birth), date_part('MONTH', a.date_of_birth)
                ORDER BY date_part('YEAR', a.date_of_birth), date_part('MONTH', a.date_of_birth)";
        $results = $this->getConnection()->query($sql)->fetchAll();
        return array_map(function ($result) {
            return new YearMonthData(
                $result['year'],
                $result['month'],
                $result['count']
            );
        }, $results);
    }
}
