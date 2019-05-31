<?php


namespace AppBundle\Cache;


use AppBundle\Constant\BreedIndexDiscriminatorTypeConstant;
use AppBundle\Entity\BreedIndex;
use AppBundle\Entity\BreedValueType;
use AppBundle\Entity\ProcessLog;
use AppBundle\Entity\ProcessLogRepository;
use AppBundle\Entity\ResultTableBreedGrades;
use AppBundle\Entity\ResultTableNormalizedBreedGrades;
use AppBundle\Enumerator\MixBlupType;
use AppBundle\Service\BreedIndexService;
use AppBundle\Service\BreedValueService;
use AppBundle\Service\NormalDistributionService;
use AppBundle\Util\LoggerUtil;
use AppBundle\Util\SqlUtil;
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

    /** @var ProcessLog */
    private $processLog;

    const USE_BATCH_PROCESSING = false;
    const BATCH_SIZE = 250000;
    const MAX_REPEAT_IDENTICAL_BATCH_LOOP_COUNT = 3;

    const PROCESSING = "Processing ";
    const MISSING_GENERATION_DATE_LABEL = 'missing_generation_date';

    const GENERATION_DATE = 'generation_date';

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
     * @param Connection $connection
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function getResultTableVariables(Connection $connection)
    {
        $sql = "SELECT column_name
                FROM information_schema.columns
                WHERE table_name = '".ResultTableBreedGrades::getTableName()."'";
        $existingColumnNameResults = $connection->query($sql)->fetchAll();

        $existingColumnNames = array_keys(SqlUtil::createSearchArrayByKey('column_name', $existingColumnNameResults));

        $sql = "SELECT
                  b.result_table_value_variable,
                  b.result_table_accuracy_variable,
                  b.use_normal_distribution,
                  a.nl as analysis_type_nl
                FROM breed_value_type b
                  LEFT JOIN mix_blup_analysis_type a ON b.analysis_type_id = a.id";
        $variableResults = $connection->query($sql)->fetchAll();

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
     * @param boolean $ignorePreviouslyFinishedProcesses
     * @param boolean $insertMissingResultTableAndGeneticBaseRecords
     * @param string $generationDateString if null, then the generationDate of the latest inserted breedValue will be used
     * @throws \Exception
     */
    public function update(array $analysisTypes = [],
                           $insertMissingResultTableAndGeneticBaseRecords = true,
                           $ignorePreviouslyFinishedProcesses = false,
                           $updateBreedIndexes = false, $updateNormalDistributions = false,
                           $generationDateString = null)
    {
        if ($insertMissingResultTableAndGeneticBaseRecords) {
            $this->insertMissingBlankRecords();
            /*
             * NOTE! Without genetic bases the corrected breedValues cannot be calculated, so do this first!
             */
            $this->breedValueService->initializeBlankGeneticBases();
        } else {
            $this->logger->notice("Skip insert missing blank resultTable records");
            $this->logger->notice("Skip initializing blank genetic base records");
        }

        if ($updateBreedIndexes) {
            $generationDateStringForBenchMarkValues = $this->getGenerationDateString($generationDateString);
            $this->updateBreedIndexesByOutputFileType($generationDateStringForBenchMarkValues, $analysisTypes);
        } else {
            $this->logger->notice("Skip updating breed indexes");
        }

        if ($updateNormalDistributions) {
            $this->updateNormalDistributions($analysisTypes);
        } else {
            $this->logger->notice("Skip updating normal distributions");
        }

        $generationDateStringForResultTableValues = $generationDateString;
        if (empty($generationDateString)) {
            $generationDateStringForResultTableValues = null;
            $this->logger->notice("=== Using per breedValueType max generation string ===");
        } else {
            $this->logger->notice("=== Using ".$generationDateString." generation string for all breedValueTypes ===");
        }

        $this->updateBreedValueResultTableValuesAndAccuraciesAndNormalizedValues(
            $analysisTypes, $ignorePreviouslyFinishedProcesses, $generationDateStringForResultTableValues);
    }


    /**
     * @param $analysisTypes
     * @param bool $ignorePreviouslyFinishedProcesses
     * @param string|null $generationDateString
     * @throws \Exception
     */
    private function updateBreedValueResultTableValuesAndAccuraciesAndNormalizedValues(
        $analysisTypes, bool $ignorePreviouslyFinishedProcesses = false, $generationDateString = null)
    {
        $results = self::getResultTableVariables($this->conn, $this->resultTableName);

        $totalBreedValueUpdateCount = 0;
        $totalNormalizedBreedValueUpdateCount = 0;

        $processLogRepository = $this->em->getRepository(ProcessLog::class);

        $previousProcessLogs = [];
        if (!empty($generationDateString)) {
            $previousProcessLogs = $processLogRepository
                ->findBreedValuesResultTableUpdaterProcessLogs($generationDateString,true);
        }

        foreach ($results as $result)
        {
            $valueVar = $result['result_table_value_variable'];
            $accuracyVar = $result['result_table_accuracy_variable'];
            $useNormalDistribution = $result['use_normal_distribution'];
            $analysisTypeNl = $result['analysis_type_nl'];

            if (count($analysisTypes) === 0 || in_array($analysisTypeNl, $analysisTypes)) {

                $this->write(self::PROCESSING.$valueVar);

                $startDate = new \DateTime();

                $generationDate = empty($generationDateString) ?
                    $this->maxGenerationDate($valueVar, $previousProcessLogs) :
                    $generationDateString
                ;

                if (empty($generationDate)) {
                    $this->logMissingBreedValues($processLogRepository, $valueVar,
                        self::MISSING_GENERATION_DATE_LABEL, $startDate);
                    continue;
                }

                /** @var ProcessLog $previousProcessLog */
                $previousProcessLog = $processLogRepository->findBreedValuesResultTableUpdaterProcessLog(
                    $previousProcessLogs,
                    $valueVar, $generationDate, true);
                if ($previousProcessLog) {
                    $this->printPreviousLogData($valueVar, $previousProcessLog, $ignorePreviouslyFinishedProcesses);
                    if (!$ignorePreviouslyFinishedProcesses) {
                        continue;
                    }
                }

                $breedValuesExist = $this->breedValueRecordsExist($valueVar, $generationDate);
                if (!$breedValuesExist) {
                    $this->logMissingBreedValues($processLogRepository, $valueVar, $generationDate, $startDate);
                    continue;
                }

                $this->write('(Max) generation_date found and used for all '.$valueVar.' breed_values: '.$generationDate);

                $this->processLog = $processLogRepository
                    ->startBreedValuesResultTableUpdaterProcessLog($valueVar, $generationDate, $startDate);

                $totalBreedValueUpdateCount += $this->updateResultTableByBreedValueType($valueVar, $accuracyVar, $generationDate);
                if ($useNormalDistribution) {
                    $totalNormalizedBreedValueUpdateCount += $this->updateNormalizedResultTableByBreedValueType($valueVar, $accuracyVar, $generationDate);
                }

                $this->processLog = $processLogRepository->endProcessLog($this->processLog);
                $this->writeFinalLine($valueVar, $this->processLog->duration());
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


    private function writeFinalLine($valueVar, $duration = null) {
        $durationText = $duration != null ? ', duration: '.$duration : '';
        $this->write('Finished process for '.$valueVar.$durationText);
    }


    private function printPreviousLogData($valueVar, ProcessLog $log, bool $ignorePreviouslyFinishedProcesses) {
        $message = sprintf('The breedValueType %s for generation date %s has already been processed, duration: %s'
            .' [%s -> %s]',$valueVar, $log->getSubCategory(), $log->duration(),
            $log->getStartDateAsString(), $log->getEndDateAsString());
        $this->write($message);
        if ($ignorePreviouslyFinishedProcesses) {
            $this->write("Still redoing the process for ".$valueVar);
        }
    }

    private function logMissingBreedValues(ProcessLogRepository $processLogRepository, $valueVar,
                                           $generationDate, $startDate) {
        $this->write('No breed values found for breed_value_type '.$valueVar);

        if ($generationDate == self::MISSING_GENERATION_DATE_LABEL) {
            $previousProcessLog = $processLogRepository->findBreedValuesResultTableUpdaterProcessLog(
                [],
                $valueVar, $generationDate, true);
            if ($previousProcessLog) {
                $this->writeFinalLine($valueVar);
                return;
            }
        }

        $this->processLog = $processLogRepository
            ->startBreedValuesResultTableUpdaterProcessLog($valueVar, $generationDate, $startDate);
        $this->processLog = $processLogRepository->endProcessLog($this->processLog);
        $this->writeFinalLine($valueVar);
    }

    /**
     * @param string|null $generationDateString
     * @return mixed
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function getGenerationDateString($generationDateString = null)
    {
        if (is_string($generationDateString) && $generationDateString != '') {
            return $generationDateString;
        }

        $sql = "SELECT generation_date FROM breed_value WHERE id = (SELECT MAX(id) FROM breed_value) LIMIT 1";
        $generationDateString = $this->conn->query($sql)->fetch()[self::GENERATION_DATE];
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
     * @param string $breedTypeValueVar
     * @param array|ProcessLog[] $previousProcessLogs
     * @return string|null
     * @throws \Doctrine\DBAL\DBALException
     */
    private function maxGenerationDate($breedTypeValueVar, $previousProcessLogs): ?string {
        if (key_exists($breedTypeValueVar, $previousProcessLogs)) {
            $generationDate = $previousProcessLogs[$breedTypeValueVar]->getSubCategory();
            if (!empty($generationDate)) {
                return $generationDate;
            }
        }

        $sql = "SELECT
                    MAX(generation_date)
                FROM breed_value WHERE type_id = (
                    SELECT id FROM breed_value_type WHERE result_table_value_variable = '$breedTypeValueVar'
                    )";
        return $this->conn->query($sql)->fetch()['max'];
    }


    private function breedValueRecordsExist($breedTypeValueVar, $generationDateString): bool {
        $sql = "SELECT
                    id
                FROM breed_value WHERE generation_date = '$generationDateString' AND type_id = (
                    SELECT id FROM breed_value_type WHERE result_table_value_variable = '$breedTypeValueVar'
                    )
                LIMIT 1";
        return !empty($this->conn->query($sql)->fetchAll());
    }


    /**
     * @param $valueVar
     * @param $accuracyVar
     * @param $generationDate
     * @param $useBatchProcessing
     * @return string
     */
    private function updateResultTableQuery($valueVar, $accuracyVar, $generationDate, $useBatchProcessing): string {
        // Default: Using genetic base
        $sqlResultTableValues = "SELECT
                          b.animal_id,
                          b.value - gb.value as corrected_value,
                          SQRT(b.reliability) as accuracy
                       FROM breed_value b
                         INNER JOIN breed_value_type t ON t.id = b.type_id
                         INNER JOIN result_table_breed_grades r ON r.animal_id = b.animal_id
                         INNER JOIN breed_value_genetic_base gb ON gb.breed_value_type_id = t.id AND gb.year = DATE_PART('year', b.generation_date)
                       WHERE
                         b.generation_date = '$generationDate' AND
                         t.result_table_value_variable = '$valueVar' AND
                         b.reliability >= t.min_reliability AND
                         (b.value - gb.value <> r.$valueVar OR SQRT(b.reliability) <> r.$accuracyVar OR
                         r.$valueVar ISNULL OR r.$accuracyVar ISNULL) "
                         .($useBatchProcessing ? ' LIMIT '.self::BATCH_SIZE : '');

        return "UPDATE result_table_breed_grades
                SET $valueVar = v.corrected_value, $accuracyVar = v.accuracy
                FROM (
                      $sqlResultTableValues   
                ) as v(animal_id, corrected_value, accuracy)
                WHERE result_table_breed_grades.animal_id = v.animal_id";

    }


    /**
     * @param string $valueVar
     * @param string $accuracyVar
     * @param string $generationDate
     * @return int
     */
    private function updateResultTableByBreedValueType($valueVar, $accuracyVar, $generationDate)
    {
        $this->writeUpdatingBreedTypeLine($valueVar, $accuracyVar, $this->resultTableName);

        $updateCount = 0;

        if (self::USE_BATCH_PROCESSING) {
            $loopCount = 0;
            $lastLocalUpdateCount = 0;
            $repeatedLastLocalUpdateCount = 0;

            $this->logger->notice("Batch processing ".$valueVar);
            $this->logger->notice("...");
            do {
                $sql = $this->updateResultTableQuery($valueVar, $accuracyVar, $generationDate, true);
                $localUpdateCount = SqlUtil::updateWithCount($this->conn, $sql);
                $updateCount += $localUpdateCount;

                $loopCount++;
                LoggerUtil::overwriteNotice($this->logger, "Processed ".$updateCount.' batch '.$loopCount);

                if ($localUpdateCount != self::BATCH_SIZE && $localUpdateCount == $lastLocalUpdateCount) {
                    $repeatedLastLocalUpdateCount++;
                }

                if ($repeatedLastLocalUpdateCount >= self::MAX_REPEAT_IDENTICAL_BATCH_LOOP_COUNT) {
                    $errorMessage = "Breaking identical loop that was repeated ".$repeatedLastLocalUpdateCount."x";
                    if ($this->processLog instanceof ProcessLog) {
                        $this->processLog->addToErrorMessage($errorMessage);
                        $this->processLog->addToDebuggingData("base64encoded resultTable sql query: " . base64_encode($sql));
                    }
                    $this->logger->error($errorMessage);
                    break;
                }

                $lastLocalUpdateCount = $localUpdateCount;

            } while ($localUpdateCount > 0);
        } else {
            $sql = $this->updateResultTableQuery($valueVar, $accuracyVar, $generationDate, false);
            $updateCount = SqlUtil::updateWithCount($this->conn, $sql);
        }

        $this->logger->notice("Total processed ".$updateCount);

        /*
         * Update obsolete value to null
         * NOTE! This should be done BEFORE calculating the values for the children,
         * to prevent cascading calculation for children breedValues based on other calculated values
         */
        $removeCount = $this->setResultTableValueToNullWhereBreedValueIsMissingIncludingForAnyParent($valueVar, $accuracyVar, $generationDate);
        $updateCount += $removeCount;

        //Calculate breed values and accuracies of children without one, based on the values of both parents
        $childrenUpdateCount = $this->updateResultTableBreedValuesOfChildrenBasedOnValuesOfParents($valueVar, $accuracyVar, $generationDate);
        $updateCount += $childrenUpdateCount;

        $records = $valueVar.' and '.$accuracyVar. ' records';
        $message = $updateCount > 0 ? $updateCount . ' (children: '.$childrenUpdateCount.', removed: '.$removeCount.') '. $records. ' updated.': 'No '.$records.' updated';
        $this->write($message);

        if ($this->processLog) {
            $this->processLog->addToDescription('ResultTable: ' . $message);
        }

        return $updateCount;
    }


    /**
     * @param $valueVar
     * @param $accuracyVar
     * @param $generationDate
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    private function setResultTableValueToNullWhereBreedValueIsMissingIncludingForAnyParent($valueVar, $accuracyVar, $generationDate)
    {
        $sql = "UPDATE result_table_breed_grades
                    SET $valueVar = NULL, $accuracyVar = NULL
                    WHERE animal_id IN (
                      SELECT r.animal_id
                      FROM result_table_breed_grades r
                        INNER JOIN animal a ON r.animal_id = a.id
                        LEFT JOIN
                        (
                          SELECT b.id, b.animal_id FROM breed_value b
                            INNER JOIN breed_value_type t ON t.id = b.type_id
                          WHERE b.reliability >= t.min_reliability AND t.result_table_value_variable = '$valueVar'
                            AND b.generation_date = '$generationDate'
                        )i ON r.animal_id = i.animal_id
                        LEFT JOIN
                        (
                          SELECT b.id, b.animal_id FROM breed_value b
                            INNER JOIN breed_value_type t ON t.id = b.type_id
                          WHERE b.reliability >= t.min_reliability AND t.result_table_value_variable = '$valueVar'
                            AND b.generation_date = '$generationDate'
                        )im ON a.parent_mother_id = im.animal_id
                        LEFT JOIN
                        (
                          SELECT b.id, b.animal_id FROM breed_value b
                            INNER JOIN breed_value_type t ON t.id = b.type_id
                          WHERE b.reliability >= t.min_reliability AND t.result_table_value_variable = '$valueVar'
                            AND b.generation_date = '$generationDate'
                        )if ON a.parent_father_id = if.animal_id                      
                      WHERE
                        i.id ISNULL AND 
                        (r.$valueVar NOTNULL OR r.$accuracyVar NOTNULL) AND
                        (
                            if.id ISNULL OR im.id ISNULL
                        )
                      )";
        return SqlUtil::updateWithCount($this->conn, $sql);
    }


    /**
     * @param string $valueVar
     * @param string $accuracyVar
     * @param string $generationDate
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    private function updateResultTableBreedValuesOfChildrenBasedOnValuesOfParents($valueVar, $accuracyVar, $generationDate)
    {
        $sql = "UPDATE result_table_breed_grades SET $valueVar = calc.breed_value, $accuracyVar = calc.accuracy
                FROM (
                  SELECT
                    ra.animal_id,
                    (if.corrected_value + im.corrected_value) / 2 as calculated_breed_value,
                    SQRT(0.25*if.accuracy*if.accuracy + 0.25*im.accuracy*im.accuracy) as calculated_accuracy
                  FROM result_table_breed_grades ra
                    LEFT JOIN
                     (
                         SELECT b.id, b.animal_id
                         FROM breed_value b
                                  INNER JOIN breed_value_type t ON t.id = b.type_id
                         WHERE b.reliability >= t.min_reliability AND t.result_table_value_variable = '$valueVar'
                           AND b.generation_date = '$generationDate'
                     )i ON ra.animal_id = i.animal_id                      
                    INNER JOIN animal a ON ra.animal_id = a.id
                    INNER JOIN
                    (
                      SELECT
                             b.id, b.animal_id, SQRT(b.reliability) as accuracy, b.value - gb.value as corrected_value
                      FROM breed_value b
                        INNER JOIN breed_value_type t ON t.id = b.type_id
                        INNER JOIN breed_value_genetic_base gb ON gb.breed_value_type_id = t.id AND gb.year = DATE_PART('year', b.generation_date)
                      WHERE b.reliability >= t.min_reliability AND t.result_table_value_variable = '$valueVar'
                        AND b.generation_date = '$generationDate'
                    )im ON a.parent_mother_id = im.animal_id
                    INNER JOIN
                    (
                      SELECT
                             b.id, b.animal_id, SQRT(b.reliability) as accuracy, b.value - gb.value as corrected_value
                      FROM breed_value b
                        INNER JOIN breed_value_type t ON t.id = b.type_id
                        INNER JOIN breed_value_genetic_base gb ON gb.breed_value_type_id = t.id AND gb.year = DATE_PART('year', b.generation_date)
                      WHERE b.reliability >= t.min_reliability AND t.result_table_value_variable = '$valueVar'
                        AND b.generation_date = '$generationDate'
                    )if ON a.parent_father_id = if.animal_id                     
                  WHERE
                    i.id ISNULL AND -- ONLY OVERWRITE VALUES IF ANIMAL DOES NOT ALREADY HAVE IT'S OWN BREED VALUE
                    a.parent_father_id NOTNULL AND
                    a.parent_mother_id NOTNULL AND
                    (if.accuracy NOTNULL OR if.accuracy NOTNULL) AND
                    (im.accuracy NOTNULL OR im.accuracy NOTNULL) AND
                    SQRT(0.25*if.accuracy*if.accuracy + 0.25*im.accuracy*im.accuracy)                        
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


    private function writeUpdatingBreedTypeLine($valueVar, $accuracyVar, $tableName)
    {
        $this->write('Updating '.$valueVar.' and '.$accuracyVar. ' values in '.$tableName.' ... ');
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

            $this->writeUpdatingBreedTypeLine($valueVar, $accuracyVar, $this->resultTableName);

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


    private function updateNormalizedResultTableQuery($valueVar, $generationDate, bool $useBatchProcessing): string {
        $sqlResultTableValues = "SELECT
                          b.animal_id,
                          ROUND(100 + (b.value - n.mean) * (t.standard_deviation_step_size / n.standard_deviation) 
                                      * (CASE WHEN t.invert_normal_distribution THEN -1 ELSE 1 END)
                               ) as corrected_value
                        FROM breed_value b
                          INNER JOIN breed_value_type t ON t.id = b.type_id
                          INNER JOIN $this->normalizedResultTableName nr ON nr.animal_id = b.animal_id
                          INNER JOIN (
                              SELECT * FROM normal_distribution WHERE is_including_only_alive_animals = FALSE 
                           )n ON n.type = t.nl AND n.year = DATE_PART('year', b.generation_date)
                        WHERE
                          b.generation_date = '$generationDate' AND
                          t.result_table_value_variable = '$valueVar' AND
                          b.reliability >= t.min_reliability AND
                          (
                            (ROUND(100 + (b.value - n.mean) * (t.standard_deviation_step_size / n.standard_deviation)) 
                                * (CASE WHEN t.invert_normal_distribution THEN -1 ELSE 1 END)) <> nr.$valueVar OR
                            nr.$valueVar ISNULL
                          ) AND
                          t.use_normal_distribution "
                        .($useBatchProcessing ? ' LIMIT '.self::BATCH_SIZE : '');

        return "UPDATE $this->normalizedResultTableName
                SET $valueVar = v.corrected_value
                FROM (
                      $sqlResultTableValues   
                ) as v(animal_id, corrected_value)
                WHERE $this->normalizedResultTableName.animal_id = v.animal_id";
    }


    /**
     * @param string $valueVar
     * @param string $accuracyVar
     * @param string $generationDate
     * @return int
     */
    private function updateNormalizedResultTableByBreedValueType($valueVar, $accuracyVar, $generationDate)
    {
        $this->writeUpdatingBreedTypeLine($valueVar, $accuracyVar, $this->normalizedResultTableName);

        $updateCount = 0;

        if (self::USE_BATCH_PROCESSING) {

            $loopCount = 0;
            $lastLocalUpdateCount = 0;
            $repeatedLastLocalUpdateCount = 0;

            $this->logger->notice("Batch processing ".$valueVar);
            $this->logger->notice("...");
            do {
                $sql = $this->updateNormalizedResultTableQuery($valueVar, $generationDate, true);
                $localUpdateCount = SqlUtil::updateWithCount($this->conn, $sql);
                $updateCount += $localUpdateCount;

                $loopCount++;
                LoggerUtil::overwriteNotice($this->logger, "Processed ".$updateCount.' batch '.$loopCount);

                if ($localUpdateCount != self::BATCH_SIZE && $localUpdateCount == $lastLocalUpdateCount) {
                    $repeatedLastLocalUpdateCount++;
                }

                if ($repeatedLastLocalUpdateCount >= self::MAX_REPEAT_IDENTICAL_BATCH_LOOP_COUNT) {
                    $errorMessage = "Breaking identical loop that was repeated ".$repeatedLastLocalUpdateCount."x";
                    if ($this->processLog instanceof ProcessLog) {
                        $this->processLog->addToErrorMessage($errorMessage);
                        $this->processLog->addToDebuggingData("base64encoded normalizedResultTable sql query: " . base64_encode($sql));
                    }
                    $this->logger->error($errorMessage);
                    break;
                }

                $lastLocalUpdateCount = $localUpdateCount;

            } while ($localUpdateCount > 0);

        } else {
            $sql = $this->updateNormalizedResultTableQuery($valueVar, $generationDate, false);
            $updateCount = SqlUtil::updateWithCount($this->conn, $sql);
        }

        $this->logger->notice("Total processed ".$updateCount);

        /*
         * Update obsolete value to null
         * NOTE! This should be done BEFORE calculating the values for the children,
         * to prevent cascading calculation for children breedValues based on other calculated values
         */
        $removeCount = $this->setNormalizedResultTableValueToNullWhereBreedValueIsMissing($valueVar, $generationDate);
        $updateCount += $removeCount;

        //Calculate breed values and accuracies of children without one, based on the values of both parents
        $childrenUpdateCount = $this->updateNormalizedResultTableBreedValuesOfChildrenBasedOnValuesOfParents($valueVar, $generationDate);
        $updateCount += $childrenUpdateCount;

        $records = $valueVar. ' records';
        $message = $updateCount > 0 ? $updateCount . ' (children: '.$childrenUpdateCount.', removed: '.$removeCount.') '. $records. ' updated.': 'No '.$records.' updated';
        $this->write($message);

        if ($this->processLog) {
            $this->processLog->addToDescription('NormalizedResultTable: ' . $message);
        }

        return $updateCount;
    }


    /**
     * @param $valueVar
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    private function setNormalizedResultTableValueToNullWhereBreedValueIsMissing($valueVar, $generationDate)
    {
        $sql = "UPDATE $this->normalizedResultTableName
                    SET $valueVar = NULL
                    WHERE animal_id IN (
                      SELECT r.animal_id
                      FROM $this->normalizedResultTableName r
                        INNER JOIN animal a ON r.animal_id = a.id
                        LEFT JOIN
                        (
                          SELECT b.id, b.animal_id FROM breed_value b
                            INNER JOIN breed_value_type t ON t.id = b.type_id
                          WHERE b.reliability >= t.min_reliability AND t.result_table_value_variable = '$valueVar'
                        )i ON r.animal_id = i.animal_id
                        LEFT JOIN
                        (
                          SELECT b.id, b.animal_id FROM breed_value b
                            INNER JOIN breed_value_type t ON t.id = b.type_id
                          WHERE b.reliability >= t.min_reliability AND t.result_table_value_variable = '$valueVar'
                            AND b.generation_date = '$generationDate'
                        )im ON a.parent_mother_id = im.animal_id
                        LEFT JOIN
                        (
                          SELECT b.id, b.animal_id FROM breed_value b
                            INNER JOIN breed_value_type t ON t.id = b.type_id
                          WHERE b.reliability >= t.min_reliability AND t.result_table_value_variable = '$valueVar'
                            AND b.generation_date = '$generationDate'
                        )if ON a.parent_father_id = if.animal_id                         
                      WHERE
                        i.id ISNULL AND 
                        r.$valueVar NOTNULL AND
                        (
                            if.id ISNULL OR im.id ISNULL
                        )                            
                      )";
        return SqlUtil::updateWithCount($this->conn, $sql);
    }


    /**
     * @param string $valueVar
     * @param string $accuracyVar
     * @param string $generationDate
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    private function updateNormalizedResultTableBreedValuesOfChildrenBasedOnValuesOfParents($valueVar, $generationDate)
    {
        $sql = "UPDATE $this->normalizedResultTableName SET $valueVar = calc.normalized_breed_value
                FROM (
                  SELECT
                    ra.animal_id,
                    (nf.normalized_value + nm.normalized_value) / 2 as calculated_normalized_breed_value
                  FROM $this->resultTableName ra
                    INNER JOIN animal a ON ra.animal_id = a.id
                    INNER JOIN $this->normalizedResultTableName nra ON nra.animal_id = a.id
                    INNER JOIN (
                        SELECT
                            b.animal_id,
                            ROUND(100 + (b.value - n.mean) * (t.standard_deviation_step_size / n.standard_deviation) 
                                      * (CASE WHEN t.invert_normal_distribution THEN -1 ELSE 1 END)
                               ) as normalized_value,
                            SQRT(b.reliability) as accuracy
                        FROM breed_value b
                          INNER JOIN breed_value_type t ON t.id = b.type_id
                          INNER JOIN (
                              SELECT * FROM normal_distribution WHERE is_including_only_alive_animals = FALSE 
                           )n ON n.type = t.nl AND n.year = DATE_PART('year', b.generation_date)
                        WHERE
                          b.generation_date = '$generationDate' AND
                          t.result_table_value_variable = '$valueVar' AND
                          b.reliability >= t.min_reliability AND
                          t.use_normal_distribution
                    )nm ON nm.animal_id = a.parent_mother_id
                    INNER JOIN (
                        SELECT
                            b.animal_id,
                            ROUND(100 + (b.value - n.mean) * (t.standard_deviation_step_size / n.standard_deviation) 
                                      * (CASE WHEN t.invert_normal_distribution THEN -1 ELSE 1 END)
                               ) as normalized_value,
                            SQRT(b.reliability) as accuracy
                        FROM breed_value b
                          INNER JOIN breed_value_type t ON t.id = b.type_id
                          INNER JOIN (
                              SELECT * FROM normal_distribution WHERE is_including_only_alive_animals = FALSE 
                           )n ON n.type = t.nl AND n.year = DATE_PART('year', b.generation_date) 
                        WHERE
                          b.generation_date = '$generationDate' AND
                          t.result_table_value_variable = '$valueVar' AND
                          b.reliability >= t.min_reliability AND
                          t.use_normal_distribution                        
                    )nf ON nf.animal_id = a.parent_father_id            
                  WHERE
                    a.parent_father_id NOTNULL AND
                    a.parent_mother_id NOTNULL AND
                    nra.$valueVar ISNULL AND
                    SQRT(0.25*nf.accuracy*nf.accuracy + 0.25*nm.accuracy*nm.accuracy)
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

                $this->logger->notice(self::PROCESSING.'new LambMeatIndexes...');
                $this->breedIndexService->updateLambMeatIndexes($generationDateString);
            }
        }
    }


    /**
     * @param array $analysisTypes
     * @throws \Exception
     */
    private function updateNormalDistributions($analysisTypes)
    {
        $processLambMeatIndexAnalysis = $this->processLambMeatIndexAnalysis($analysisTypes);
        $processFertilityAnalysis = $this->processFertilityAnalysis($analysisTypes);
        $processExteriorAnalysis = $this->processExteriorAnalysis($analysisTypes);
        $processWormAnalysis = $this->processWormAnalysis($analysisTypes);

        // Indexes

        if ($processLambMeatIndexAnalysis) {
            $this->logger->notice(self::PROCESSING.'LambMeatIndex NormalDistribution...');
            $generationDateString = $this->getLatestBreedIndexGenerationDateString(BreedIndexDiscriminatorTypeConstant::LAMB_MEAT);
            if ($generationDateString) {
                $this->normalDistributionService->persistLambMeatIndexMeanAndStandardDeviation($generationDateString, false);
            }
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
                $this->logger->notice(self::PROCESSING.$normalDistributionLabel.' NormalDistribution...');

                $generationDateString = $this->getLatestBreedValueGenerationDateString($breedValueType->getId());
                if (!$generationDateString) {
                    continue;
                }

                $this->normalDistributionService
                    ->persistBreedValueTypeMeanAndStandardDeviation($normalDistributionLabel, $generationDateString, false);
            }
        }
    }


    /**
     * @param bool $overwriteExisting
     * @throws \Exception
     */
    public function updateAllNormalDistributions(bool $overwriteExisting = false): void
    {
        $this->updateAllBreedIndexNormalDistributions($overwriteExisting);
        $this->updateAllBreedValueNormalDistributions($overwriteExisting);
    }


    /**
     * @param bool $overwriteExisting
     * @throws \Exception
     */
    public function updateAllBreedValueNormalDistributions(bool $overwriteExisting = false): void
    {
        foreach ($this->getBreedValueTypesWithNormalDistribution() as $breedValueType)
        {
            $generationDateString = $this->getLatestBreedValueGenerationDateString($breedValueType->getId());
            if (!$generationDateString) {
                $this->logger->warn('No generationDateString found for '.$breedValueType->getId());
                continue;
            }

            $normalDistributionLabel = $breedValueType->getNl();
            $this->logger->notice(self::PROCESSING.$normalDistributionLabel.' NormalDistribution...');
            $this->normalDistributionService
                ->persistBreedValueTypeMeanAndStandardDeviation($normalDistributionLabel, $generationDateString, $overwriteExisting);
        }
    }


    /**
     * @param int $breedValueId
     * @return string
     */
    public function getLatestBreedValueGenerationDateString($breedValueId): ?string
    {
        if (!is_int($breedValueId)) {
            $this->logger->error('Given BreedValueId is not an integer: '.$breedValueId);
            return null;
        }

        try {
            $sql = 'SELECT
                    generation_date
                FROM breed_value
                WHERE type_id = '.$breedValueId.'
                ORDER BY id DESC
                LIMIT 1';
            $result = $this->em->getConnection()->query($sql)->fetch();
            if (!$result) {
                $this->logger->warn('No breedValue records exist for breedValueType: '.$breedValueId);
                return null;
            }
            return $result[self::GENERATION_DATE];

        } catch (\Exception $exception) {
            $this->logger->error($exception->getTraceAsString());
            return null;
        }
    }


    /**
     * @param bool $overwriteOldValues
     * @throws \Exception
     */
    public function updateAllBreedIndexNormalDistributions(bool $overwriteOldValues = false): void
    {
        $this->logger->notice(self::PROCESSING.'LambMeatIndex NormalDistribution...');
        $generationDateString = $this->getLatestBreedIndexGenerationDateString(BreedIndexDiscriminatorTypeConstant::LAMB_MEAT);
        if ($generationDateString) {
            $this->normalDistributionService->persistLambMeatIndexMeanAndStandardDeviation($generationDateString, $overwriteOldValues);
        }
    }


    /**
     * @param int $breedIndexType
     * @return string
     */
    private function getLatestBreedIndexGenerationDateString($breedIndexType): ?string
    {
        if (!is_string($breedIndexType)) {
            $this->logger->error('Given BreedIndexType is not a string: '.$breedIndexType);
            return null;
        }

        try {
            $sql = "SELECT
                    generation_date
                FROM breed_index
                WHERE type = '".$breedIndexType."'
                ORDER BY id DESC
                LIMIT 1";

            $result = $this->em->getConnection()->query($sql)->fetch();
            if (!$result) {
                $this->logger->warn('No breedIndex records exist for BreedIndexType: '.$breedIndexType);
                return null;
            }
            return $result[self::GENERATION_DATE];

        } catch (\Exception $exception) {
            $this->logger->error($exception->getTraceAsString());
            return null;
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