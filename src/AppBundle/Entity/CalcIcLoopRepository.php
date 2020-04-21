<?php

namespace AppBundle\Entity;

use Psr\Log\LoggerInterface;

/**
 * Class CalcIcLoopRepository
 * @package AppBundle\Entity
 */
class CalcIcLoopRepository extends CalcInbreedingCoefficientBaseRepository implements CalcTableRepositoryInterface {

    function tableName(): string
    {
        return CalcIcLoop::getTableName();
    }

    function clearTable(?LoggerInterface $logger = null)
    {
        $this->logClearingTable($logger, $this->tableName());
        $this->clearTableBase($this->tableName(), $logger);
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
            CalcIcAscendantPath::getTableName(),
            CalcIcParentDetails::getTableName()
        );
    }


    /**
     * @param  int  $animalIdOrigin1
     * @param  int  $animalIdOrigin2
     * @param  LoggerInterface|null  $logger
     */
    protected function fillBase(
        int $animalIdOrigin1,
        int $animalIdOrigin2,
        ?LoggerInterface $logger = null,
        string $loopTableName,
        string $ascendantsTableName,
        string $parentDetailsTableName
    )
    {
        $logSuffix = '';
        $this->logFillingTableStart($logger, $this->tableName(), $logSuffix);

        $sql = "INSERT INTO $loopTableName 
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
                FROM $ascendantsTableName origin1
                         INNER JOIN $ascendantsTableName origin2 ON
                            origin1.last_parent_id = origin2.last_parent_id AND
                            origin1.animal_id <> origin2.animal_id
                         INNER JOIN (
                            SELECT
                                parent_id as animal_id,
                                MAX(parent_inbreeding_coefficient) as inbreeding_coefficient
                            FROM $parentDetailsTableName d
                            GROUP BY parent_id
                        )i ON i.animal_id = origin1.last_parent_id
                WHERE
                     origin1.animal_id = $animalIdOrigin1 AND origin2.animal_id = $animalIdOrigin2 AND
                --Make sure the test_ascendant_paths only include origins that start with the primary origin animals
                    origin1.animal_id <> origin2.animal_id AND
                    NOT (origin1.path = origin2.path AND origin1.depth = origin2.depth AND origin1.depth > 1) -- exclude paths with closed ends
                ORDER BY loop_size";

        $this->getConnection()->executeQuery($sql);

        $this->logFillingTableEnd($logger, $this->tableName());
    }


    public function calculateInbreedingCoefficientFromLoopsAndParentDetails(): float
    {
        return $this->calculateInbreedingCoefficientFromLoopsBase($this->tableName());
    }


    protected function calculateInbreedingCoefficientFromLoopsBase(string $loopsTableName): float
    {
        $precision = $this->precision();

        $sql = "SELECT
                    round(
                        CAST (sum(path_inbreeding_factor * parent_inbreeding_coefficient_factor) AS NUMERIC)
                    ,$precision) as inbreeding_coefficient
                FROM (
                         SELECT
                             -- loop details
                             1 as group_id,
                             power(0.5, loop_size + 1) as path_inbreeding_factor, -- (1/2)^(number of parents in loop)
                             1 + COALESCE(parent_inbreeding_coefficient, 0) as parent_inbreeding_coefficient_factor
                         FROM $loopsTableName l
                         WHERE
                             -- Remove loops for which another smaller loop already exists
                             -- where the last_parent_id of the smaller loop
                             -- does not exist in the middle of the bigger loop
                             NOT EXISTS (
                                     SELECT
                                         l3.id
                                     FROM $loopsTableName l3
                                              INNER JOIN $loopsTableName l2 ON
                                                 l.last_parent_id <> l2.last_parent_id AND
                                                 l2.last_parent_id = ANY (
                                                     -- The path of origin 1, excluding the last parent
                                                     (l.origin1parents::int[])[1:array_length(l.origin1parents::int[],1)-1]
                                                     ) AND
                                                 l2.last_parent_id = ANY (
                                                     -- The path of origin 2, excluding the last parent
                                                     (l.origin2parents::int[])[1:array_length(l.origin2parents::int[],1)-1]
                                                     ) AND
                                                 l2.loop_size < l.loop_size
                                     WHERE l.id = l3.id
                                 )
                         )d
                GROUP BY d.group_id";
        return $this->getConnection()->query($sql)->fetch()['inbreeding_coefficient'] ?? 0;
    }
}
