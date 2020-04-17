<?php

namespace AppBundle\Entity;

use Psr\Log\LoggerInterface;

/**
 * Class CalcInbreedingCoefficientAscendantPathRepository
 * @package AppBundle\Entity
 */
class CalcInbreedingCoefficientAscendantPathRepository extends CalcInbreedingCoefficientBaseRepository implements CalcTableRepositoryInterface {

    function tableName(): string
    {
        return CalcInbreedingCoefficientAscendantPath::getTableName();
    }

    function truncate(?LoggerInterface $logger = null)
    {
        $this->logClearingTable($logger, $this->tableName());
        $this->truncateBase($this->tableName(), $logger);
    }

    /**
     * @param  LoggerInterface|null  $logger
     */
    function fill(?LoggerInterface $logger = null)
    {
        $this->logFillingTableStart($logger, $this->tableName());

        $maxGenerations = $this->maxGenerations();

        $sql = "INSERT INTO calc_inbreeding_coefficient_ascendant_path (animal_id, last_parent_id, depth, path, parents) 
                WITH recursive ctetable(animal_id, last_parent_id, depth, path, parents) as
                    (
                        SELECT
                            c.animal_id,
                            c.parent_id as last_parent_id,
                            1 AS depth,
                            CAST(c.parent_id AS TEXT) AS path,
                            CONCAT('{',c.parent_id,'}')::int[] as parents
                        FROM calc_inbreeding_coefficient_parent_details as c
                        WHERE parent_id NOTNULL AND
                          -- Make sure to check that the child is always younger than the parent
                                c.date_of_birth > c.parent_date_of_birth
                                AND c.is_primary_animal

                        UNION ALL

                        SELECT
                            c.animal_id, -- use the original source
                            p.parent_id as last_parent_id,
                            c.depth + 1 AS depth,
                            CAST((concat(
                                    RTRIM(c.path),'->',CAST(p.parent_id AS TEXT)
                                )) AS TEXT) AS path,
                            c.parents || ARRAY [p.parent_id] AS parents
                        FROM ctetable AS c JOIN calc_inbreeding_coefficient_parent_details as p on c.last_parent_id = p.animal_id
                             -- p = ascendant (parent)
                             -- c = descendant (child)
                        WHERE c.last_parent_id is not null AND p.parent_id IS NOT NULL AND
                          -- Make sure to check that the child is always younger than the parent
                                p.date_of_birth > p.parent_date_of_birth AND
                                depth <= ($maxGenerations - 1) -- LOOP PROTECTION
                    )
                 SELECT
                     DISTINCT animal_id, last_parent_id, depth, path, parents
                 FROM ctetable
                 ORDER BY animal_id, last_parent_id";

        $this->getConnection()->executeQuery($sql);

        $this->logFillingTableEnd($logger, $this->tableName());
    }
}
