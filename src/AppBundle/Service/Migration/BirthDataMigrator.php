<?php


namespace AppBundle\Service\Migration;

use AppBundle\Cache\TailLengthCacher;
use AppBundle\Cache\WeightCacher;
use AppBundle\Constant\MeasurementConstant;
use AppBundle\Entity\Measurement;
use AppBundle\Enumerator\QueryType;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\MeasurementsUtil;
use AppBundle\Util\NumberUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class BirthDataMigrator
 */
class BirthDataMigrator extends Migrator2017JunServiceBase implements IMigratorService
{
    /** @var array */
    private $dateOfBirthByAnimalIds;
    /** @var array */
    private $englishByDutchBirthProgress;

    /** @inheritdoc */
    public function __construct(ObjectManager $em, $rootDir)
    {
        parent::__construct($em, $rootDir);
    }

    /** @inheritDoc */
    function run(CommandUtil $cmdUtil)
    {
        parent::run($cmdUtil);

        $this->writeLn('====== PRE migration fixes ======');
        $this->data = $this->parseCSV(self::BIRTH);
        MeasurementsUtil::generateAnimalIdAndDateValues($this->conn, false, $cmdUtil);
        $this->createAnimalSearchArrays();

        $this->writeln('====== BIRTH WEIGHTS ======');
        $birthWeightInsertCount = $this->migrateBirthWeights();

        $this->writeln('====== TAIL LENGTHS ======');
        $tailLengthInsertCount = $this->migrateTailLengths();

        $this->writeln('====== BIRTH PROGRESS ======');
        $this->migrateBirthProgress();

        $this->writeLn('====== POST migration updates ======');
        if ($birthWeightInsertCount > 0) {
            $this->cmdUtil->writeln( WeightCacher::updateAllBirthWeights($this->conn) . ' birth weight cache records updated');
        }

        if ($tailLengthInsertCount > 0) {
            $this->cmdUtil->writeln( TailLengthCacher::updateAll($this->conn) . ' tailLength cache records updated');
        }
    }


    private function createAnimalSearchArrays()
    {
        $this->writeLn('Create animal_id by vsmId search array');
        $this->animalIdsByVsmId = $this->animalRepository->getAnimalPrimaryKeysByVsmIdArray();

        $this->writeLn('Create date_of_birth by animal_id search array');
        $sql = "SELECT id, DATE(date_of_birth) FROM animal WHERE date_of_birth NOTNULL";
        $results = $this->conn->query($sql)->fetchAll();
        $this->dateOfBirthByAnimalIds = SqlUtil::groupSqlResultsOfKey1ByKey2('date_of_birth','id',$results,false, true);

        $this->writeLn('Create english by dutch birthProgress search array');
        $sql = "SELECT dutch_description, description FROM birth_progress";
        $results = $this->conn->query($sql)->fetchAll();
        $this->englishByDutchBirthProgress = SqlUtil::groupSqlResultsOfKey1ByKey2('description', 'dutch_description', $results);

    }


    private function migrateBirthWeights()
    {
        $this->writeLn('=== Migrating NEW birth weight measurements ===');

        DoctrineUtil::updateTableSequence($this->conn, [Measurement::TABLE_NAME]);

        $this->sqlBatchProcessor
            ->purgeAllSets()
            ->createBatchSet(QueryType::BASE_INSERT)
            ->createBatchSet(QueryType::INSERT)
        ;

        $baseInsertBatchSet = $this->sqlBatchProcessor->getSet(QueryType::BASE_INSERT);
        $insertBatchSet = $this->sqlBatchProcessor->getSet(QueryType::INSERT);

        $baseInsertBatchSet->setSqlQueryBase("INSERT INTO measurement (id, log_date, inspector_id, measurement_date, 
                                                              type, animal_id_and_date, action_by_id) VALUES ");

        $insertBatchSet->setSqlQueryBase("INSERT INTO weight (id, animal_id, weight, is_birth_weight) VALUES ");

        $id = SqlUtil::getMaxId($this->conn, Measurement::TABLE_NAME);
        $firstMaxId = $id + 1;


        $this->writeLn('Create current birthWeights search array ...');
        $sql = "SELECT w.animal_id
                FROM weight w
                  INNER JOIN measurement m ON m.id = w.id
                INNER JOIN animal a ON w.animal_id = a.id
                WHERE m.is_active AND (w.is_birth_weight = TRUE OR (a.date_of_birth NOTNULL
                       AND DATE(measurement_date) - DATE(date_of_birth) <= 3
                       AND DATE(measurement_date) - DATE(date_of_birth) >= 0
                ))";
        $results = $this->conn->query($sql)->fetchAll();
        $animalIdsOrCurrentBirthWeights = SqlUtil::getSingleValueGroupedSqlResults('animal_id',$results, true);

        $this->sqlBatchProcessor->start(count($this->data));

        $logDate = TimeUtil::getLogDateString();

        $newBirthWeights = [];

        try {

            foreach ($this->data as $record) {
                $vsmId = $record[0];
                //$birthWeight = $record[1];
                //$tailLength = $record[2];
                //$birthProgress = $record[3];

                $birthWeight = $this->getValidatedBirthWeight($record);
                $animalId = ArrayUtil::get($vsmId, $this->animalIdsByVsmId);

                //The computationally easier validation checks are done first to speed up the process
                if ($animalId === null || $birthWeight === null) {
                    $baseInsertBatchSet->incrementSkippedCount();
                    $insertBatchSet->incrementSkippedCount();
                    $this->sqlBatchProcessor->advanceProgressBar();
                    continue;
                }

                if (!key_exists($animalId, $animalIdsOrCurrentBirthWeights) && !key_exists($animalId, $newBirthWeights)) {
                    $baseInsertBatchSet->incrementAlreadyDoneCount();
                    $insertBatchSet->incrementAlreadyDoneCount();
                    $this->sqlBatchProcessor->advanceProgressBar();
                    continue;
                }

                //MeasurementDate = DateOfBirth
                $measurementDateString = TimeUtil::getTimeStampForSqlFromAnyDateString(
                    ArrayUtil::get($animalId, $this->dateOfBirthByAnimalIds), false);
                $animalIdAndDate = $animalId.'_'.$measurementDateString;

                if ($measurementDateString === null) {
                    $baseInsertBatchSet->incrementSkippedCount();
                    $insertBatchSet->incrementSkippedCount();
                    $this->sqlBatchProcessor->advanceProgressBar();
                    continue;
                }

                //Insert new BirthWeight
                $baseInsertBatchSet->appendValuesString('('.++$id.",'".$logDate."',NULL,'"
                    .$measurementDateString."','Weight','".$animalIdAndDate."',".self::DEVELOPER_PRIMARY_KEY.")");

                $insertBatchSet->appendValuesString('('.$id.",".$animalId.",'".$birthWeight ."',TRUE)");

                $newBirthWeights[$animalId] = $animalId;

                $this->sqlBatchProcessor
                    ->processAtBatchSize()
                    ->advanceProgressBar()
                ;
            }
            $this->sqlBatchProcessor->end();

        } catch (\Exception $exception) {
            $sql = "DELETE FROM measurement WHERE type = 'Weight' AND DATE(log_date) = '$logDate')";
            $this->conn->exec($sql);

        } finally {
            $insertCount = $insertBatchSet->getRecordsDoneCount();
            $this->cmdUtil->writeln('First measurement Id inserted: '.$firstMaxId);
            $this->cmdUtil->writeln('Imported measurement logDate: '.$logDate);
            $this->sqlBatchProcessor->purgeAllSets();
        }
        return $insertCount;
    }


    /**
     * @param $record
     * @return float|null|string
     */
    private function getValidatedBirthWeight($record)
    {
        $birthWeight = trim(strtr($record[1], [',' => '.']));

        if (is_numeric($birthWeight)) {
            $birthWeight = floatval($birthWeight);
            if (NumberUtil::isFloatZero($birthWeight)
             || (MeasurementConstant::BIRTH_WEIGHT_MIN_VALUE <= $birthWeight
                 && $birthWeight <= MeasurementConstant::BIRTH_WEIGHT_MAX_VALUE)) {
                    return $birthWeight;
                }
        }

        return null;
    }


    /**
     * Note that tailLengths are only measured during birth, so date_of_birth = measurement_date
     */
    private function migrateTailLengths()
    {
        $this->writeLn('=== Migrating NEW tailLength (at birth) measurements ===');

        DoctrineUtil::updateTableSequence($this->conn, [Measurement::TABLE_NAME]);

        $this->sqlBatchProcessor
            ->purgeAllSets()
            ->createBatchSet(QueryType::BASE_INSERT)
            ->createBatchSet(QueryType::INSERT)
        ;

        $baseInsertBatchSet = $this->sqlBatchProcessor->getSet(QueryType::BASE_INSERT);
        $insertBatchSet = $this->sqlBatchProcessor->getSet(QueryType::INSERT);

        $baseInsertBatchSet->setSqlQueryBase("INSERT INTO measurement (id, log_date, inspector_id, measurement_date, 
                                                              type, animal_id_and_date, action_by_id) VALUES ");

        $insertBatchSet->setSqlQueryBase("INSERT INTO tail_length (id, animal_id, length) VALUES ");

        $id = SqlUtil::getMaxId($this->conn, Measurement::TABLE_NAME);
        $firstMaxId = $id + 1;

        $this->writeLn('Create current tailLengths search array ...');
        $sql = "SELECT t.animal_id
                FROM tail_length t
                  INNER JOIN measurement m ON m.id = t.id
                  INNER JOIN animal a ON t.animal_id = a.id
                WHERE m.is_active AND (a.date_of_birth NOTNULL
                                       AND (DATE(measurement_date) - DATE(date_of_birth) <= 3
                                       AND DATE(measurement_date) - DATE(date_of_birth) >= 0)
                )";
        $results = $this->conn->query($sql)->fetchAll();
        $animalIdsOrCurrentBirthWeights = SqlUtil::getSingleValueGroupedSqlResults('animal_id',$results, true);

        $this->sqlBatchProcessor->start(count($this->data));

        $logDate = TimeUtil::getLogDateString();

        $newTailLengths = [];

        try {

            foreach ($this->data as $record) {
                $vsmId = $record[0];
                //$birthWeight = $record[1];
                //$tailLength = $record[2];
                //$birthProgress = $record[3];

                $tailLength = $this->getValidatedTailLength($record);
                $animalId = ArrayUtil::get($vsmId, $this->animalIdsByVsmId);

                //The computationally easier validation checks are done first to speed up the process
                if ($animalId === null || $tailLength === null) {
                    $baseInsertBatchSet->incrementSkippedCount();
                    $insertBatchSet->incrementSkippedCount();
                    $this->sqlBatchProcessor->advanceProgressBar();
                    continue;
                }

                if (!key_exists($animalId, $animalIdsOrCurrentBirthWeights) && !key_exists($animalId, $newTailLengths)) {
                    $baseInsertBatchSet->incrementAlreadyDoneCount();
                    $insertBatchSet->incrementAlreadyDoneCount();
                    $this->sqlBatchProcessor->advanceProgressBar();
                    continue;
                }

                //MeasurementDate = DateOfBirth
                $measurementDateString = TimeUtil::getTimeStampForSqlFromAnyDateString(
                    ArrayUtil::get($animalId, $this->dateOfBirthByAnimalIds), false);
                $animalIdAndDate = $animalId.'_'.$measurementDateString;

                if ($measurementDateString === null) {
                    $baseInsertBatchSet->incrementSkippedCount();
                    $insertBatchSet->incrementSkippedCount();
                    $this->sqlBatchProcessor->advanceProgressBar();
                    continue;
                }

                //Insert new TailLength
                $baseInsertBatchSet->appendValuesString('('.++$id.",'".$logDate."',NULL,'"
                    .$measurementDateString."','TailLength','".$animalIdAndDate."',".self::DEVELOPER_PRIMARY_KEY.")");

                $insertBatchSet->appendValuesString('('.$id.",".$animalId.",'".$tailLength ."')");

                $newTailLengths[$animalId] = $animalId;

                $this->sqlBatchProcessor
                    ->processAtBatchSize()
                    ->advanceProgressBar()
                ;
            }
            $this->sqlBatchProcessor->end();

        } catch (\Exception $exception) {
            $sql = "DELETE FROM measurement WHERE type = 'TailLength' AND DATE(log_date) = '$logDate')";
            $this->conn->exec($sql);

        } finally {
            $insertCount = $insertBatchSet->getRecordsDoneCount();
            $this->cmdUtil->writeln('First measurement Id inserted: '.$firstMaxId);
            $this->cmdUtil->writeln('Imported measurement logDate: '.$logDate);
            $this->sqlBatchProcessor->purgeAllSets();
        }
        return $insertCount;
    }


    /**
     * @param $record
     * @return float|null|string
     */
    private function getValidatedTailLength($record)
    {
        $tailLength = trim(strtr($record[2], [',' => '.']));

        if (is_numeric($tailLength)) {
            $tailLength = floatval($tailLength);
            if (NumberUtil::isFloatZero($tailLength)
                || (MeasurementConstant::TAIL_LENGTH_MIN <= $tailLength
                    && $tailLength <= MeasurementConstant::TAIL_LENGTH_MAX)) {
                return $tailLength;
            }
        }

        return null;
    }


    private function migrateBirthProgress()
    {
        $this->writeLn('=== Migrating BirthProgress values ===');

        $updateBatchSet = $this->sqlBatchProcessor
            ->purgeAllSets()
            ->createBatchSet(QueryType::UPDATE)
            ->getSet(QueryType::UPDATE)
        ;

        $updateBatchSet->setSqlQueryBase("UPDATE animal SET birth_progress = v.birth_progress
                                          FROM ( VALUES ");
        $updateBatchSet->setSqlQueryBaseEnd(") AS v(animal_id, birth_progress) 
                               WHERE animal.id = v.animal_id AND birth_progress ISNULL");

        $this->writeLn('Create search array of animalIds with empty birth_progress ...');
        $sql = "SELECT id, name FROM animal WHERE name NOTNULL AND birth_progress ISNULL";
        $results = $this->conn->query($sql)->fetchAll();
        $vsmIdByAvailableAnimalIds = SqlUtil::groupSqlResultsOfKey1ByKey2('name', 'id',$results, true, true);

        $this->sqlBatchProcessor->start(count($this->data));

        foreach ($this->data as $record) {
            $vsmId = $record[0];
            //$birthWeight = $record[1];
            //$tailLength = $record[2];
            //$birthProgress = $record[3];

            $birthProgress = $this->getTranslatedBirthProgress($record);

            if ($birthProgress == null) {
                $updateBatchSet->incrementSkippedCount();
                $this->sqlBatchProcessor->advanceProgressBar();
            }

            $animalId = ArrayUtil::get($vsmId, $vsmIdByAvailableAnimalIds);

            if ($animalId === null) {
                if (key_exists($vsmId, $this->animalIdsByVsmId)) {
                    $updateBatchSet->incrementAlreadyDoneCount();
                } else {
                    $updateBatchSet->incrementSkippedCount();
                }
                $this->sqlBatchProcessor->advanceProgressBar();
                continue;
            }

            //Update birthProgress value in Animal
            $updateBatchSet->appendValuesString("(".$animalId.",'".$birthProgress ."')");

            //Don't allow multiple updates for the same animal
            unset($vsmIdByAvailableAnimalIds[$animalId]);

            $this->sqlBatchProcessor
                ->processAtBatchSize()
                ->advanceProgressBar()
            ;
        }
        $this->sqlBatchProcessor->end();
        $updateCount = $updateBatchSet->getRecordsDoneCount();
        $this->sqlBatchProcessor->purgeAllSets();

        return $updateCount;
    }


    /**
     * @param array $record
     * @return string|null
     * @throws \Exception
     */
    private function getTranslatedBirthProgress($record)
    {
        if ($record[3] === '') { return null; }

        $dutchBirthProgress = strtr(strtolower($record[3]), [' (' => ', ', ')' => '']);
        $birthProgress = ArrayUtil::get($dutchBirthProgress, $this->englishByDutchBirthProgress);

        if ($birthProgress === null) {
            throw new \Exception('Birth Progress translation error for dutch birthProgress: '.$dutchBirthProgress);
        }

        return $birthProgress;
    }
}