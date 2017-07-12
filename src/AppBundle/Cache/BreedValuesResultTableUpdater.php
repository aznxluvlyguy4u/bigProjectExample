<?php


namespace AppBundle\Cache;


use AppBundle\Entity\BreedIndex;
use AppBundle\Entity\ResultTableBreedGrades;
use AppBundle\Service\BreedValueService;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\SqlUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Bridge\Monolog\Logger;

/**
 * Class BreedValuesResultTableUpdater
 *
 * @ORM\Entity(repositoryClass="AppBundle\Cache")
 * @package AppBundle\Cache
 */
class BreedValuesResultTableUpdater
{
    /** @var ObjectManager */
    private $em;
    /** @var Connection */
    private $conn;
    /** @var Logger */
    private $logger;
    /** @var BreedValueService */
    private $breedValueService;

    /** @var string */
    private $resultTableName;

    /**
     * BreedValuesResultTableUpdater constructor.
     * @param ObjectManager $em
     * @param Logger $logger
     */
    public function __construct(ObjectManager $em, Logger $logger)
    {
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->logger = $logger;

        $this->breedValueService = new BreedValueService($em, $logger);

        $this->resultTableName = ResultTableBreedGrades::TABLE_NAME;
    }


    /**
     * @return array
     * @throws \Exception
     */
    private function getResultTableVariables()
    {
        $sql = "SELECT column_name
                FROM information_schema.columns
                WHERE table_name = '".$this->resultTableName."'";
        $existingColumnNameResults = $this->conn->query($sql)->fetchAll();

        $existingColumnNames = array_keys(SqlUtil::createSearchArrayByKey('column_name', $existingColumnNameResults));

        $sql = "SELECT result_table_value_variable, result_table_accuracy_variable FROM breed_value_type";
        $variableResults = $this->conn->query($sql)->fetchAll();

        $searchArray = [];
        foreach ($variableResults as $result)
        {
            $valueVar = $result['result_table_value_variable'];
            $accuracyVar = $result['result_table_accuracy_variable'];

            if(in_array($valueVar, $existingColumnNames)) {
                if(in_array($accuracyVar, $existingColumnNames)) {
                    $searchArray[] = $result;
                } else {
                    throw new \Exception("Result table value columnName '".$valueVar
                        ."' exists but its accuracy columnName '".$accuracyVar. "' does not exist");
                }
            }
        }

        return $searchArray;
    }


    public function update()
    {
        $this->insertMissingBlankRecords();

        /*
         * NOTE! Without genetic bases the corrected breedValues cannot be calculated, so do this first!
         */
        $this->breedValueService->initializeBlankGeneticBases();

        /*
         * Updating breedValue result table values and accuracies
         */
        $results = $this->getResultTableVariables();

        $totalBreedValueUpdateCount = 0;
        foreach ($results as $result)
        {
            $totalBreedValueUpdateCount += $this->updateByBreedValueType($result);
        }

        $messagePrefix = $totalBreedValueUpdateCount > 0 ? 'In total '.$totalBreedValueUpdateCount : 'In total NO';
        $this->write($messagePrefix. ' breed Value&Accuracy sets were updated');


        $breedIndexUpdateCount = $this->updateByBreedValueIndexType();
        $messagePrefix = $breedIndexUpdateCount > 0 ? 'In total '.$breedIndexUpdateCount : 'In total NO';
        $this->write($messagePrefix. ' breed Index&Accuracy sets were updated');
    }


    /**
     * Note breedValueIndices are aggregated from the breedValues.
     * Thus it is sufficient to only check the breedValue table for inserting blank records
     * for those animals that need it.
     *
     * @return int
     */
    private function insertMissingBlankRecords()
    {
        $this->write('Inserting blank records into '.$this->resultTableName.' table ...');

        $sql = "INSERT INTO result_table_breed_grades (animal_id)
                     SELECT a.id as animal_id
                        FROM animal a
                        LEFT JOIN result_table_breed_grades r ON r.animal_id = a.id
                        WHERE r.id ISNULL";

        $insertCount = SqlUtil::updateWithCount($this->conn, $sql);

        $message = $insertCount > 0 ? $insertCount . ' records inserted.': 'No records inserted.';
        $this->write($message);

        return $insertCount;
    }


    /**
     * @param array $result
     * @return int
     */
    private function updateByBreedValueType($result)
    {
        $valueVar = $result['result_table_value_variable'];
        $accuracyVar = $result['result_table_accuracy_variable'];

        $this->write('Updating '.$valueVar.' and '.$accuracyVar. ' values in '.$this->resultTableName.' ... ');

        $sql = "UPDATE result_table_breed_grades
                SET $valueVar = v.corrected_value, $accuracyVar = v.accuracy
                FROM (
                       SELECT b.animal_id, b.value - gb.value as corrected_value, b.reliability, SQRT(b.reliability) as accuracy
                       FROM breed_value b
                         INNER JOIN breed_value_type t ON t.id = b.type_id
                         INNER JOIN (
                                      SELECT b.animal_id, b.type_id, max(generation_date) as max_generation_date
                                      FROM breed_value b
                                        INNER JOIN breed_value_type t ON t.id = b.type_id
                                      WHERE b.reliability >= t.min_reliability AND t.result_table_value_variable = '$valueVar'
                                      GROUP BY b.animal_id, b.type_id
                                    )g ON g.animal_id = b.animal_id AND g.type_id = b.type_id AND g.max_generation_date = b.generation_date
                         INNER JOIN result_table_breed_grades r ON r.animal_id = b.animal_id
                         INNER JOIN breed_value_genetic_base gb ON gb.breed_value_type_id = t.id AND gb.year = DATE_PART('year', b.generation_date)
                       WHERE
                         t.result_table_value_variable = '$valueVar' AND
                         (b.value - gb.value <> r.$valueVar OR SQRT(b.reliability) <> r.$accuracyVar OR
                         r.$valueVar ISNULL OR r.$accuracyVar ISNULL)
                ) as v(animal_id, corrected_value, reliabilty, accuracy)
                WHERE result_table_breed_grades.animal_id = v.animal_id";
        $updateCount = SqlUtil::updateWithCount($this->conn, $sql);

        //Update obsolete value to null
        $sql = "UPDATE result_table_breed_grades
                    SET $valueVar = NULL, $accuracyVar = NULL
                    WHERE animal_id IN (
                      SELECT r.animal_id
                      FROM result_table_breed_grades r
                        LEFT JOIN
                        (
                          SELECT b.id, b.animal_id FROM breed_value b
                            INNER JOIN breed_value_type t ON t.id = b.type_id
                          WHERE b.reliability >= t.min_reliability AND t.result_table_value_variable = '$valueVar'
                        )i ON r.animal_id = i.animal_id
                      WHERE i.id ISNULL AND (r.$valueVar NOTNULL OR r.$accuracyVar NOTNULL)
                    )";
        $updateCount += SqlUtil::updateWithCount($this->conn, $sql);

        $records = $valueVar.' and '.$accuracyVar. ' records';
        $message = $updateCount > 0 ? $updateCount . ' '. $records. ' updated.': 'No '.$records.' updated.';
        $this->write($message);

        return $updateCount;
    }


    /**
     * @return int
     */
    private function updateByBreedValueIndexType()
    {
        $totalUpdateCount = 0;

        foreach (BreedIndex::getTypes() as $snakeCaseType => $camelCaseType) {
            $valueVar = $snakeCaseType . '_index';
            $accuracyVar = $snakeCaseType . '_accuracy';

            $this->write('Updating '.$valueVar.' and '.$accuracyVar. ' values in '.$this->resultTableName.' ... ');

            //Update new values
            $sql = "UPDATE result_table_breed_grades
                    SET $valueVar = v.index, $accuracyVar = v.accuracy
                    FROM (
                           SELECT i.animal_id, i.index, i.accuracy, r.$accuracyVar, r.$valueVar, i.index <> r.$valueVar, i.accuracy <> r.$accuracyVar
                           FROM breed_index i
                             INNER JOIN (
                                          SELECT ii.animal_id, ii.type, MAX(ii.generation_date) as max_generation_date, MAX(ii.id) as max_id
                                          FROM breed_index ii
                                              INNER JOIN (
                                                  SELECT animal_id, type, generation_date, MAX(id) as max_id
                                                  FROM breed_index
                                                  WHERE type = '$camelCaseType'
                                                  GROUP BY animal_id, type, generation_date
                                                )gg ON gg.animal_id = ii.animal_id AND gg.type = ii.type
                                                       AND gg.generation_date = ii.generation_date AND ii.id = max_id
                                          WHERE ii.type = '$camelCaseType'
                                          GROUP BY ii.animal_id, ii.type
                                        )g ON g.max_generation_date = i.generation_date AND i.animal_id = g.animal_id
                                              AND i.type = g.type AND i.id = g.max_id
                             INNER JOIN result_table_breed_grades r ON r.animal_id = i.animal_id
                           WHERE i.type = '$camelCaseType' AND
                                 (i.index <> r.$valueVar OR i.accuracy <> r.$accuracyVar
                                  OR r.$valueVar ISNULL OR r.$accuracyVar ISNULL)
                    ) as v(animal_id, index, accuracy)
                    WHERE result_table_breed_grades.animal_id = v.animal_id";
            $updateCount = SqlUtil::updateWithCount($this->conn, $sql);

            //Update obsolete value to null
            $sql = "UPDATE result_table_breed_grades
                    SET $valueVar = NULL, $accuracyVar = NULL
                    WHERE animal_id IN (
                      SELECT r.animal_id
                      FROM result_table_breed_grades r
                        LEFT JOIN
                        (
                          SELECT * FROM breed_index
                          WHERE type = '$camelCaseType'
                        )i ON r.animal_id = i.animal_id
                      WHERE i.id ISNULL AND (r.$valueVar NOTNULL OR r.$accuracyVar NOTNULL)
                    )";
            $updateCount += SqlUtil::updateWithCount($this->conn, $sql);

            $records = $valueVar.' and '.$accuracyVar. ' records';
            $message = $updateCount > 0 ? $updateCount . ' '. $records. ' updated.': 'No '.$records.' updated.';
            $this->write($message);

            $totalUpdateCount += $updateCount;
        }

        return $totalUpdateCount;
    }


    /**
     * @param $line
     */
    private function write($line)
    {
        if($this->logger) {
            $this->logger->notice($line);
        }
    }
}