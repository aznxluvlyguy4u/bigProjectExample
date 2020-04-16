<?php

namespace AppBundle\Entity;

use Psr\Log\LoggerInterface;

/**
 * Class CalcInbreedingCoefficientLoopRepository
 * @package AppBundle\Entity
 */
class CalcInbreedingCoefficientLoopRepository extends CalcInbreedingCoefficientBaseRepository implements CalcTableRepositoryInterface {

    function tableName(): string
    {
        return CalcInbreedingCoefficientLoop::getTableName();
    }

    function truncate(?LoggerInterface $logger = null)
    {
        $this->logClearingTable($logger, $this->tableName());
        $this->truncateBase($this->tableName(), $logger);
    }

    function fillByAnimalIds(int $animalIdOrigin1, int $animalIdOrigin2, ?LoggerInterface $logger = null)
    {
        $this->fill(
            "origin1.animal_id = $animalIdOrigin1 AND origin2.animal_id = $animalIdOrigin2 AND",
            $logger
        );
    }

    /**
     *
     * Example of $filter:
     * - AND animal_id IN (1,10)
     * - AND animal_id IN (SELECT id FROM animal a WHERE .... )
     *
     * @param  string  $filter
     * @param  LoggerInterface|null  $logger
     * @param  string  $logSuffix
     * @throws \Doctrine\DBAL\DBALException
     */
    function fill(string $filter = '', ?LoggerInterface $logger = null, string $logSuffix = '')
    {
        $this->logFillingTableStart($logger, $this->tableName(), $logSuffix);

        $sql = "INSERT INTO calc_inbreeding_coefficient_loop 
                    (loop_size, last_parent_id, parent_inbreeding_coefficient, origin1path, origin2path, origin1parents, origin2parents)  
                SELECT
                    -- origin1.animal_id as origin1_animal_id,
                    -- origin2.animal_id as origin2_animal_id,
                    -- origin1.depth as origin1_depth,
                    -- origin2.depth as origin2_depth,
                    origin1.depth + origin2.depth as loop_size,
                    origin1.last_parent_id,
                    i.inbreeding_coefficient as parent_inbreeding_coefficient,
                    origin1.path as origin1path,
                    origin2.path as origin2path,
                    origin1.parents as origin1parents,
                    origin2.parents as origin2parents
                FROM calc_inbreeding_coefficient_ascendant_path origin1
                         INNER JOIN calc_inbreeding_coefficient_ascendant_path origin2 ON
                            origin1.last_parent_id = origin2.last_parent_id AND
                            origin1.animal_id <> origin2.animal_id
                         INNER JOIN (
                            SELECT
                                parent_id as animal_id,
                                MAX(parent_inbreeding_coefficient) as inbreeding_coefficient
                            FROM calc_inbreeding_coefficient_parent_details d
                            GROUP BY parent_id
                        )i ON i.animal_id = origin1.last_parent_id
                WHERE
                     $filter
                --Make sure the test_ascendant_paths only include origins that start with the primary origin animals
                    origin1.animal_id <> origin2.animal_id AND
                    NOT (origin1.path = origin2.path AND origin1.depth = origin2.depth AND origin1.depth > 1) -- exclude paths with closed ends
                ORDER BY loop_size";

        $this->getConnection()->executeQuery($sql);

        $this->logFillingTableEnd($logger, $this->tableName());
    }
}
