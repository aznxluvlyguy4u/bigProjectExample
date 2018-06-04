<?php


namespace AppBundle\Cache;


use AppBundle\Constant\BreedValueTypeConstant;
use AppBundle\Entity\BreedIndex;
use AppBundle\Entity\BreedValueType;
use AppBundle\Entity\NormalDistribution;
use AppBundle\Entity\ResultTableBreedGrades;
use AppBundle\Entity\ResultTableNormalizedBreedGrades;
use AppBundle\Enumerator\MixBlupType;
use AppBundle\Service\BreedIndexService;
use AppBundle\Service\BreedValueService;
use AppBundle\Service\NormalDistributionService;
use AppBundle\Util\SqlUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Monolog\Logger;

/**
 * Class BreedValuesResultTableUpdater
 *
 * @ORM\Entity(repositoryClass="AppBundle\Cache")
 * @package AppBundle\Cache
 */
class BreedValuesResultTableUpdater
{
    /** @var ObjectManager|EntityManagerInterface */
    private $em;
    /** @var Connection */
    private $conn;
    /** @var Logger */
    private $logger;
    /** @var BreedValueService */
    private $breedValueService;
    /** @var BreedIndexService */
    private $breedIndexService;
    /** @var NormalDistributionService */
    private $normalDistributionService;

    /** @var string */
    private $resultTableName;

    /** @var string */
    private $normalizedResultTableName;

    public function __construct(EntityManagerInterface $em,
                                Logger $logger,
                                BreedValueService $breedValueService,
                                BreedIndexService $breedIndexService,
                                NormalDistributionService $normalDistributionService)
    {
        $this->em = $em;
        $this->conn = $this->em->getConnection();
        $this->logger = $logger;

        $this->breedValueService = $breedValueService;
        $this->breedIndexService = $breedIndexService;
        $this->normalDistributionService = $normalDistributionService;

        $this->resultTableName = ResultTableBreedGrades::getTableName();
        $this->normalizedResultTableName = ResultTableNormalizedBreedGrades::getTableName();
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

        $sql = "SELECT
                  b.result_table_value_variable,
                  b.result_table_accuracy_variable,
                  b.use_normal_distribution,
                  a.nl as analysis_type_nl
                FROM breed_value_type b
                  LEFT JOIN mix_blup_analysis_type a ON b.analysis_type_id = a.id";
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


    /**
     * @param array $analysisTypes
     * @param boolean $updateBreedIndexes
     * @param boolean $updateNormalDistributions
     * @param string $generationDateString if null, then the generationDate of the latest inserted breedValue will be used
     * @throws \Exception
     */
    public function update(array $analysisTypes = [], $updateBreedIndexes = false, $updateNormalDistributions = false, $generationDateString = null)
    {
        $this->insertMissingBlankRecords();
        $generationDateString = $this->getGenerationDateString($generationDateString);

        /*
         * NOTE! Without genetic bases the corrected breedValues cannot be calculated, so do this first!
         */
        $this->breedValueService->initializeBlankGeneticBases();

        if ($updateBreedIndexes) {
            $this->updateBreedIndexesByOutputFileType($generationDateString, $analysisTypes);
        }

        if ($updateNormalDistributions) {
            $this->updateNormalDistributions($generationDateString, $analysisTypes);
        }


        $this->updateBreedValueResultTableValuesAndAccuraciesAndNormalizedValues($analysisTypes);
    }


    /**
     * @param $analysisTypes
     * @throws \Exception
     */
    private function updateBreedValueResultTableValuesAndAccuraciesAndNormalizedValues($analysisTypes)
    {
        $results = $this->getResultTableVariables();

        $totalBreedValueUpdateCount = 0;
        $totalNormalizedBreedValueUpdateCount = 0;
        foreach ($results as $result)
        {
            $valueVar = $result['result_table_value_variable'];
            $accuracyVar = $result['result_table_accuracy_variable'];
            $useNormalDistribution = $result['use_normal_distribution'];
            $analysisTypeNl = $result['analysis_type_nl'];

            if (count($analysisTypes) === 0 || in_array($analysisTypeNl, $analysisTypes)) {
                $totalBreedValueUpdateCount += $this->updateResultTableByBreedValueType($valueVar, $accuracyVar);
                if ($useNormalDistribution) {
                    $totalNormalizedBreedValueUpdateCount += $this->updateNormalizedResultTableByBreedValueType($valueVar, $accuracyVar);
                }
            }
        }

        foreach ([
            'breed Value&Accuracy sets' => $totalBreedValueUpdateCount,
            'normalized breedvalues' => $totalNormalizedBreedValueUpdateCount,
                 ] as $label => $count)
        {
            $messagePrefix = $count > 0 ? 'In total '.$count : 'In total NO';
            $this->write($messagePrefix. ' '.$label.' were updated');
        }

        $breedIndexUpdateCount = $this->updateResultTableByBreedValueIndexType();
        $messagePrefix = $breedIndexUpdateCount > 0 ? 'In total '.$breedIndexUpdateCount : 'In total NO';
        $this->write($messagePrefix. ' breed Index&Accuracy sets were updated');
    }


    /**
     * @param $generationDateString
     * @return mixed
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function getGenerationDateString($generationDateString)
    {
        if (is_string($generationDateString) && $generationDateString != '') {
            return $generationDateString;
        }

        $sql = "SELECT generation_date FROM breed_value WHERE id = (SELECT MAX(id) FROM breed_value) LIMIT 1";
        $generationDateString = $this->conn->query($sql)->fetch()['generation_date'];
        if ($generationDateString === null) {
            throw new \Exception('There are no breed_value records in the database');
        }

        return $generationDateString;
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
        $insertCount = 0;

        foreach ([$this->resultTableName, $this->normalizedResultTableName] as $tableName) {
            $this->write('Inserting blank records into '.$tableName.' table ...');

            $sql = "INSERT INTO $tableName (animal_id)
                     SELECT a.id as animal_id
                        FROM animal a
                        LEFT JOIN $tableName r ON r.animal_id = a.id
                        WHERE r.id ISNULL";

            $insertCount += SqlUtil::updateWithCount($this->conn, $sql);

            $message = $insertCount > 0 ? $insertCount . ' records inserted.': 'No records inserted.';
            $this->write($message);
        }

        return $insertCount;
    }


    /**
     * @param string $valueVar
     * @param string $accuracyVar
     * @return int
     */
    private function updateResultTableByBreedValueType($valueVar, $accuracyVar)
    {
        $this->write('Updating '.$valueVar.' and '.$accuracyVar. ' values in '.$this->resultTableName.' ... ');

        // Default: Using genetic base
        $sqlResultTableValues = "SELECT
                          b.animal_id,
                          b.value - gb.value as corrected_value,
                          SQRT(b.reliability) as accuracy
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
                         r.$valueVar ISNULL OR r.$accuracyVar ISNULL)";

        $sql = "UPDATE result_table_breed_grades
                SET $valueVar = v.corrected_value, $accuracyVar = v.accuracy
                FROM (
                      $sqlResultTableValues   
                ) as v(animal_id, corrected_value, accuracy)
                WHERE result_table_breed_grades.animal_id = v.animal_id";
        $updateCount = SqlUtil::updateWithCount($this->conn, $sql);

        /*
         * Update obsolete value to null
         * NOTE! This should be done BEFORE calculating the values for the children,
         * to prevent cascading calculation for children breedValues based on other calculated values
         */
        $removeCount = $this->setResultTableValueToNullWhereBreedValueIsMissing($valueVar, $accuracyVar);
        $updateCount += $removeCount;

        //Calculate breed values and accuracies of children without one, based on the values of both parents
        $childrenUpdateCount = $this->updateResultTableBreedValuesOfChildrenBasedOnValuesOfParents($valueVar, $accuracyVar);
        $updateCount += $childrenUpdateCount;

        $records = $valueVar.' and '.$accuracyVar. ' records';
        $message = $updateCount > 0 ? $updateCount . ' (children: '.$childrenUpdateCount.', removed: '.$removeCount.') '. $records. ' updated.': 'No '.$records.' updated.';
        $this->write($message);

        return $updateCount;
    }


    /**
     * @param $valueVar
     * @param $accuracyVar
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    private function setResultTableValueToNullWhereBreedValueIsMissing($valueVar, $accuracyVar)
    {
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
                      WHERE
                        i.id ISNULL AND 
                        (r.$valueVar NOTNULL OR r.$accuracyVar NOTNULL)
                      )";
        return SqlUtil::updateWithCount($this->conn, $sql);
    }


    /**
     * @param string $valueVar
     * @param string $accuracyVar
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    private function updateResultTableBreedValuesOfChildrenBasedOnValuesOfParents($valueVar, $accuracyVar)
    {
        $sql = "UPDATE result_table_breed_grades SET $valueVar = calc.breed_value, $accuracyVar = calc.accuracy
                FROM (
                  SELECT
                    ra.animal_id,
                    (rf.$valueVar + rm.$valueVar) / 2 as calculated_breed_value,
                    SQRT(0.25*rf.$accuracyVar*rf.$accuracyVar + 0.25*rm.$accuracyVar*rm.$accuracyVar) as calculated_accuracy
                  FROM result_table_breed_grades ra
                    INNER JOIN animal a ON ra.animal_id = a.id
                    INNER JOIN result_table_breed_grades rf ON a.parent_father_id = rf.animal_id
                    INNER JOIN result_table_breed_grades rm ON a.parent_mother_id = rm.animal_id
                  WHERE
                    a.parent_father_id NOTNULL AND
                    a.parent_mother_id NOTNULL AND
                    (ra.$valueVar ISNULL OR ra.$accuracyVar ISNULL) AND
                    (rf.$valueVar NOTNULL OR rf.$accuracyVar NOTNULL) AND
                    (rm.$valueVar NOTNULL OR rm.$accuracyVar NOTNULL) AND
                    SQRT(0.25*rf.$accuracyVar*rf.$accuracyVar + 0.25*rm.$accuracyVar*rm.$accuracyVar)
                    >= (SELECT SQRT(min_reliability) as min_accuracy FROM breed_value_type WHERE result_table_value_variable = '$valueVar')
                ) AS calc(animal_id, breed_value, accuracy)
                WHERE result_table_breed_grades.animal_id = calc.animal_id
                  AND (
                        result_table_breed_grades.$valueVar ISNULL OR
                        result_table_breed_grades.$accuracyVar ISNULL OR
                        result_table_breed_grades.$valueVar <> calc.breed_value OR
                        result_table_breed_grades.$accuracyVar <> calc.accuracy
                      )";
        return SqlUtil::updateWithCount($this->conn, $sql);
    }


    /**
     * @return int
     */
    private function updateResultTableByBreedValueIndexType()
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
     * @param string $valueVar
     * @param string $accuracyVar
     * @return int
     */
    private function updateNormalizedResultTableByBreedValueType($valueVar, $accuracyVar)
    {
        $this->write('Updating '.$valueVar. ' values in '.$this->normalizedResultTableName.' ... ');

        $sqlResultTableValues = "SELECT
                          b.animal_id,
                          ROUND(100 + (b.value - n.mean) * (t.standard_deviation_step_size / n.standard_deviation)) as corrected_value
                        FROM breed_value b
                          INNER JOIN breed_value_type t ON t.id = b.type_id
                          INNER JOIN (
                                       SELECT b.animal_id, b.type_id, max(generation_date) as max_generation_date
                                       FROM breed_value b
                                         INNER JOIN breed_value_type t ON t.id = b.type_id
                                       WHERE b.reliability >= t.min_reliability AND t.result_table_value_variable = '$valueVar'
                                       GROUP BY b.animal_id, b.type_id
                                     )g ON g.animal_id = b.animal_id AND g.type_id = b.type_id AND g.max_generation_date = b.generation_date
                          INNER JOIN $this->normalizedResultTableName nr ON nr.animal_id = b.animal_id
                          INNER JOIN $this->resultTableName r ON r.animal_id = b.animal_id
                          INNER JOIN normal_distribution n ON n.type = t.nl AND n.year = DATE_PART('year', b.generation_date)
                        WHERE
                          t.result_table_value_variable = '$valueVar' AND
                          (
                            100 + (b.value - n.mean) * (t.standard_deviation_step_size / n.standard_deviation) <> nr.$valueVar OR
                            SQRT(b.reliability) <> r.$accuracyVar OR
                            nr.$valueVar ISNULL
                          ) AND
                          t.use_normal_distribution AND
                          n.is_including_only_alive_animals = FALSE";

        $sql = "UPDATE $this->normalizedResultTableName
                SET $valueVar = v.corrected_value
                FROM (
                      $sqlResultTableValues   
                ) as v(animal_id, corrected_value)
                WHERE $this->normalizedResultTableName.animal_id = v.animal_id";
        $updateCount = SqlUtil::updateWithCount($this->conn, $sql);

        /*
         * Update obsolete value to null
         * NOTE! This should be done BEFORE calculating the values for the children,
         * to prevent cascading calculation for children breedValues based on other calculated values
         */
        $removeCount = $this->setNormalizedResultTableValueToNullWhereBreedValueIsMissing($valueVar);
        $updateCount += $removeCount;

        //Calculate breed values and accuracies of children without one, based on the values of both parents
        $childrenUpdateCount = $this->updateNormalizedResultTableBreedValuesOfChildrenBasedOnValuesOfParents($valueVar, $accuracyVar);
        $updateCount += $childrenUpdateCount;

        $records = $valueVar. ' records';
        $message = $updateCount > 0 ? $updateCount . ' (children: '.$childrenUpdateCount.', removed: '.$removeCount.') '. $records. ' updated.': 'No '.$records.' updated.';
        $this->write($message);

        return $updateCount;
    }


    /**
     * @param $valueVar
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    private function setNormalizedResultTableValueToNullWhereBreedValueIsMissing($valueVar)
    {
        $sql = "UPDATE $this->normalizedResultTableName
                    SET $valueVar = NULL
                    WHERE animal_id IN (
                      SELECT r.animal_id
                      FROM $this->normalizedResultTableName r
                        LEFT JOIN
                        (
                          SELECT b.id, b.animal_id FROM breed_value b
                            INNER JOIN breed_value_type t ON t.id = b.type_id
                          WHERE b.reliability >= t.min_reliability AND t.result_table_value_variable = '$valueVar'
                        )i ON r.animal_id = i.animal_id
                      WHERE
                        i.id ISNULL AND 
                        r.$valueVar NOTNULL
                      )";
        return SqlUtil::updateWithCount($this->conn, $sql);
    }


    /**
     * @param string $valueVar
     * @param string $accuracyVar
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    private function updateNormalizedResultTableBreedValuesOfChildrenBasedOnValuesOfParents($valueVar, $accuracyVar)
    {
        $sql = "UPDATE $this->normalizedResultTableName SET $valueVar = calc.normalized_breed_value
                FROM (
                  SELECT
                    ra.animal_id,
                    (nrf.$valueVar + nrm.$valueVar) / 2 as calculated_normalized_breed_value
                  FROM $this->resultTableName ra
                    INNER JOIN animal a ON ra.animal_id = a.id
                    INNER JOIN $this->resultTableName rf ON a.parent_father_id = rf.animal_id
                    INNER JOIN $this->resultTableName rm ON a.parent_mother_id = rm.animal_id
                    INNER JOIN $this->normalizedResultTableName nra ON nra.animal_id = a.id
                    INNER JOIN $this->normalizedResultTableName nrf ON nrf.animal_id = a.parent_father_id
                    INNER JOIN $this->normalizedResultTableName nrm ON nrm.animal_id = a.parent_mother_id
                  WHERE
                    a.parent_father_id NOTNULL AND
                    a.parent_mother_id NOTNULL AND
                    (nra.$valueVar ISNULL) AND
                    (nrf.$valueVar NOTNULL) AND
                    (nrm.$valueVar NOTNULL) AND
                    SQRT(0.25*rf.$accuracyVar*rf.$accuracyVar + 0.25*rm.$accuracyVar*rm.$accuracyVar)
                    >= (SELECT SQRT(min_reliability) as min_accuracy FROM breed_value_type WHERE result_table_value_variable = '$valueVar')
                ) AS calc(animal_id, normalized_breed_value)
                WHERE $this->normalizedResultTableName.animal_id = calc.animal_id
                  AND (
                        $this->normalizedResultTableName.$valueVar ISNULL OR
                        $this->normalizedResultTableName.$valueVar <> calc.normalized_breed_value
                      )";
        return SqlUtil::updateWithCount($this->conn, $sql);
    }
    

    /**
     * @param array $analysisTypes
     * @return bool
     */
    private function processLambMeatIndexAnalysis(array $analysisTypes)
    {
        return $this->processAnalysisType($analysisTypes, MixBlupType::LAMB_MEAT_INDEX);
    }

    /**
     * @param array $analysisTypes
     * @return bool
     */
    private function processExteriorAnalysis(array $analysisTypes)
    {
        return $this->processAnalysisType($analysisTypes, MixBlupType::EXTERIOR);
    }

    /**
     * @param array $analysisTypes
     * @return bool
     */
    private function processFertilityAnalysis(array $analysisTypes)
    {
        return $this->processAnalysisType($analysisTypes, MixBlupType::FERTILITY);
    }

    /**
     * @param array $analysisTypes
     * @return bool
     */
    private function processWormAnalysis(array $analysisTypes)
    {
        return $this->processAnalysisType($analysisTypes, MixBlupType::WORM);
    }

    /**
     * @param array $analysisTypesPresent
     * @param $analysisTypeToProcess
     * @return bool
     */
    private function processAnalysisType(array $analysisTypesPresent = [], $analysisTypeToProcess)
    {
        return count($analysisTypesPresent) === 0
            || in_array($analysisTypeToProcess, $analysisTypesPresent)
            || key_exists($analysisTypeToProcess, $analysisTypesPresent)
            ;
    }


    private function updateBreedIndexesByOutputFileType($generationDateString, $analysisTypes = [])
    {
        if ($generationDateString) {
            if ($this->processLambMeatIndexAnalysis($analysisTypes)) {
                // Make sure the following code already has run once
                // $this->breedValueService->initializeBlankGeneticBases();

                $this->logger->notice('Processing new LambMeatIndexes...');
                $this->breedIndexService->updateLambMeatIndexes($generationDateString);
            }
        }
    }


    /**
     * @param string $generationDateString
     * @param array $analysisTypes
     * @throws \Exception
     */
    private function updateNormalDistributions($generationDateString, $analysisTypes)
    {
        if ($generationDateString) {

            $processLambMeatIndexAnalysis = $this->processLambMeatIndexAnalysis($analysisTypes);
            $processFertilityAnalysis = $this->processFertilityAnalysis($analysisTypes);
            $processExteriorAnalysis = $this->processExteriorAnalysis($analysisTypes);
            $processWormAnalysis = $this->processWormAnalysis($analysisTypes);

            // Indexes

            if ($processLambMeatIndexAnalysis) {
                $this->logger->notice('Processing new LambMeatIndex NormalDistribution...');
                $this->normalDistributionService->persistLambMeatIndexMeanAndStandardDeviation($generationDateString);
            }


            // Breed Values

            foreach ($this->getBreedValueTypesWithNormalDistribution() as $breedValueType)
            {
                $analysisTypeNl = $breedValueType->getMixBlupAnalysisType()
                    ? $breedValueType->getMixBlupAnalysisType()->getNl() : null;

                switch ($analysisTypeNl) {
                    case MixBlupType::LAMB_MEAT_INDEX: $generate = $processLambMeatIndexAnalysis; break;
                    case MixBlupType::FERTILITY: $generate = $processFertilityAnalysis; break;
                    case MixBlupType::EXTERIOR: $generate = $processExteriorAnalysis; break;
                    case MixBlupType::WORM: $generate = $processWormAnalysis; break;
                    default: $generate = false; break;
                }

                if ($generate) {
                    $normalDistributionLabel = $breedValueType->getNl();
                    $this->logger->notice('Processing new '.$normalDistributionLabel.' NormalDistribution...');
                    $this->normalDistributionService
                        ->persistBreedValueTypeMeanAndStandardDeviation($normalDistributionLabel, $generationDateString);
                }
            }
        }
    }

    /**
     * @return array|BreedValueType[]
     */
    private function getBreedValueTypesWithNormalDistribution()
    {
        return $this->em->getRepository(BreedValueType::class)->findBy(['useNormalDistribution' => true]);
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