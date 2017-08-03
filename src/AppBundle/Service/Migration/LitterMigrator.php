<?php

namespace AppBundle\Service\Migration;

use AppBundle\Cache\NLingCacher;
use AppBundle\Cache\ProductionCacher;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\DeclareNsfoBase;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\QueryType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\LitterUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class LitterMigrator
 */
class LitterMigrator extends Migrator2017JunServiceBase implements IMigratorService
{
    /** @var array*/
    private $currentLittersByVsmIdAndLitterDate;
    /** @var array*/
    private $newLittersByVsmIdAndLitterDate;
    /** @var array*/
    private $littersToUpdateByVsmIdAndLitterDate;

    /** @var int */
    private $missingMotherIdCount;
    /** @var int */
    private $missingLitterDateCount;
    /** @var int */
    private $duplicateLittersWithDifferentLitterCountsCount;
    /** @var array */
    private $duplicateLitters;

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
        $this->fixNullPseudoPregnancyAndAbortionValues();

        $this->writeln('====== Migrate litters ======');
        $this->migrateNewLittersAndUpdateOldBlankLitters();

        $this->writeLn('====== POST migration updates ======');
        $this->cmdUtil->writeln(LitterUtil::updateLitterOrdinals($this->conn).' litterOrdinals updated');
        $this->cmdUtil->writeln( ProductionCacher::updateAllProductionValues($this->conn) . ' production values updated');
        $this->cmdUtil->writeln( NLingCacher::updateAllNLingValues($this->conn) . ' n-ling values updated');
    }


    private function migrateNewLittersAndUpdateOldBlankLitters()
    {
        DoctrineUtil::updateTableSequence($this->conn, [DeclareNsfoBase::TABLE_NAME]);

        $this->animalIdsByVsmId = $this->animalRepository->getAnimalPrimaryKeysByVsmIdArray(GenderType::FEMALE);

        $this->writeln('Retrieving current litter data by vsmId and litterDate ...');

        $doubleUnderscore = "'".self::DOUBLE_UNDERSCORE."'";
        $sql = "SELECT
                    CONCAT(mom.name,$doubleUnderscore,DATE(l.litter_date)) as key,
                    l.id as litter_id,
                    mom.name AS mother_id,
                    DATE(l.litter_date) as litter_date,
                    born_alive_count,
                    stillborn_count,
                    request_state
                FROM litter l
                  INNER JOIN animal mom ON mom.id = l.animal_mother_id
                  INNER JOIN declare_nsfo_base b ON b.id = l.id
                WHERE is_pseudo_pregnancy = FALSE AND is_abortion = FALSE AND request_state <> '".RequestStateType::REVOKED."'";
        $this->currentLittersByVsmIdAndLitterDate = SqlUtil::createGroupedSearchArrayFromSqlResults($this->conn->query($sql)->fetchAll(),'key');

        /*
         * NOTE! The batches are processed alphabetically. So make sure the base insert batch starts first alphabetically.
         */
        $this->sqlBatchProcessor
            ->purgeAllSets()
            ->createBatchSet(QueryType::BASE_INSERT)
            ->createBatchSet(QueryType::INSERT)
            ->createBatchSet(QueryType::UPDATE)
        ;

        $baseInsertBatchSet = $this->sqlBatchProcessor->getSet(QueryType::BASE_INSERT);
        $insertBatchSet = $this->sqlBatchProcessor->getSet(QueryType::INSERT);
        $updateBatchSet = $this->sqlBatchProcessor->getSet(QueryType::UPDATE);

        $baseInsertBatchSet->setSqlQueryBase("INSERT INTO declare_nsfo_base (id, log_date, request_state, type) VALUES ");

        $insertBatchSet->setSqlQueryBase("INSERT INTO litter (id, animal_mother_id, litter_date,
                                            stillborn_count, born_alive_count, status, is_abortion, is_pseudo_pregnancy) VALUES ");

        $updateSqlBaseStart = "UPDATE litter 
                                SET stillborn_count = v.stillborn_count, born_alive_count = v.born_alive_count,
                                    is_pseudo_pregnancy = false, is_abortion = false
                                FROM ( VALUES ";
        $updateSqlBaseEnd = ") AS v(litter_id, stillborn_count, born_alive_count) 
                               WHERE litter.id = v.litter_id";
        $updateBatchSet->setSqlQueryBase($updateSqlBaseStart);
        $updateBatchSet->setSqlQueryBaseEnd($updateSqlBaseEnd);

        $this->data = $this->parseCSV(self::LITTERS);

        $id = SqlUtil::getMaxId($this->conn, DeclareNsfoBase::TABLE_NAME);
        $firstId = $id+1;
        $logDate = TimeUtil::getLogDateString();
        $imported = "'".RequestStateType::IMPORTED."'";

        $this->missingLitterDateCount = 0;
        $this->missingMotherIdCount = 0;
        $this->duplicateLittersWithDifferentLitterCountsCount = 0;
        $this->newLittersByVsmIdAndLitterDate = [];
        $this->littersToUpdateByVsmIdAndLitterDate = [];
        $this->duplicateLitters = [];

        try {

            $this->sqlBatchProcessor->start(count($this->data));
            foreach ($this->data as $record) {
                $litterData = $this->getLitterDataInArray($record);

                if ($litterData === null) {
                    $baseInsertBatchSet->incrementSkippedCount();
                    $insertBatchSet->incrementSkippedCount();
                    $updateBatchSet->incrementSkippedCount();
                } else {
                    $motherId = $litterData[JsonInputConstant::MOTHER_ID];
                    $litterDate = $litterData[JsonInputConstant::LITTER_DATE];
                    $stillbornCount = $litterData[JsonInputConstant::STILLBORN_COUNT];
                    $bornAliveCount = $litterData[JsonInputConstant::BORN_ALIVE_COUNT];
                    $litterAlreadyExists = $litterData[JsonInputConstant::ENTITY_ALREADY_EXISTS];
                    $litterId = $litterData[JsonInputConstant::LITTER_ID];

                    if ($litterAlreadyExists) {
                        //Update current litter if update criteria match
                        if ($litterId !== null) {
                            $updateBatchSet->appendValuesString('('.$litterId.','.$stillbornCount.','.$bornAliveCount.')');
                            $updateBatchSet->incrementBatchCount();
                        } else {
                            $updateBatchSet->incrementSkippedCount();
                        }

                        $baseInsertBatchSet->incrementAlreadyDoneCount();
                        $insertBatchSet->incrementAlreadyDoneCount();

                    } else {
                        //Insert new litter
                        $baseInsertBatchSet->appendValuesString("(".++$id.",'".$logDate."',$imported,'Litter')");
                        $insertBatchSet->appendValuesString("($id," .$motherId.",'".$litterDate."',"
                            .$stillbornCount.",".$bornAliveCount.",$imported,false,false)");
                        $baseInsertBatchSet->incrementBatchCount();
                        $insertBatchSet->incrementBatchCount();
                        $updateBatchSet->incrementSkippedCount();
                    }
                }

                $this->sqlBatchProcessor->processAtBatchSize();
                $this->sqlBatchProcessor->advanceProgressBar();

            }
            $this->sqlBatchProcessor->end();

        } catch (\Exception $exception) {
            $logDateTimeStamp = SqlUtil::castAsTimeStamp($logDate);
            $sql = "DELETE FROM declare_nsfo_base WHERE log_date = $logDateTimeStamp 
            AND request_state = $imported AND type = 'Litter'";
            $this->conn->exec($sql);

            throw new \Exception($exception);

        } finally {
            DoctrineUtil::updateTableSequence($this->conn, [DeclareNsfoBase::TABLE_NAME]);
            $this->cmdUtil->writeln('First DeclareNsfoBase Id inserted: '.$firstId);
            $this->cmdUtil->writeln('Imported DeclareNsfoBase logDate: '.$logDate);
            $this->cmdUtil->writeln('Missing litterDates: '.$this->missingLitterDateCount);
            $this->cmdUtil->writeln('Missing vsmIds belonging to animal ewe Id: '.$this->missingMotherIdCount);
            $this->cmdUtil->writeln('Duplicate litters with different counts: '.$this->duplicateLittersWithDifferentLitterCountsCount);
            $this->cmdUtil->writeln('MotherId & litter date sets count: '.count($this->duplicateLitters));
            $this->cmdUtil->printClosingLine();
        }

    }


    private function fixNullPseudoPregnancyAndAbortionValues()
    {
        $sql = "UPDATE litter SET is_pseudo_pregnancy = FALSE, is_abortion = FALSE
                WHERE (is_pseudo_pregnancy ISNULL OR is_abortion ISNULL) AND (status = 'IMPORTED' OR status = 'REVOKED')";
        $this->updateBySql('Fix NULL isPseudoPregnancy and isAbortion values ...', $sql);
    }


    /**
     * @param array $record
     * @return array|null
     * @throws \Exception
     */
    private function getLitterDataInArray($record)
    {
        //Raw data
        $vsmId = $record[0];
        //$worpNr = $record[1]; //is always 0 in this file;
        $litterDateString = TimeUtil::getTimeStampForSqlFromAnyDateString($record[6], false);
        $bornAliveCount = $record[7]; //always has a number value as string
        $stillbornCount = $record[8]; //always has a number value as string
        //$suckleCount = $record[10]; //is always 0 in this file;

        //Indirect data
        $motherId = ArrayUtil::get($vsmId, $this->animalIdsByVsmId);
        $key = $vsmId.self::DOUBLE_UNDERSCORE.$litterDateString;
        $entityAlreadyExists = key_exists($key, $this->currentLittersByVsmIdAndLitterDate);
        $entityAlreadyInInsertBatch = key_exists($key, $this->newLittersByVsmIdAndLitterDate);
        $entityAlreadyInUpdateBatch = key_exists($key, $this->littersToUpdateByVsmIdAndLitterDate);
        $concattedCounts = $this->getConcatCounts($bornAliveCount, $stillbornCount);

        //Hard validation check
        if (!ctype_digit($vsmId) && !is_int($vsmId)) {
            throw new \Exception('Incorrect vsmId found: '.$vsmId.' |key '.$key);

        } elseif (!ctype_digit($bornAliveCount) && !is_int($bornAliveCount)) {
            throw new \Exception('Incorrect bornAliveCount found: '.$bornAliveCount.' |key '.$key);

        } elseif (!ctype_digit($stillbornCount) && !is_int($stillbornCount)) {
            throw new \Exception('Incorrect stillbornCount found: '.$stillbornCount.' |key '.$key);
        }


        $bornAliveCount = intval($bornAliveCount);
        $stillbornCount = intval($stillbornCount);


        //Update check
        $currentLitterId = null;
        //Only use the first values found in the csv file to update the empty existing litterCount values
        if ($entityAlreadyExists && !$entityAlreadyInUpdateBatch) {
            //The litters are grouped by key, so select the first litterData in it, key = 0
            //There should actually only be one in the set, because there should not be any duplicate litters.
            $currentLitterData = $this->currentLittersByVsmIdAndLitterDate[$key][0];
            $currentStillbornCount = $currentLitterData[JsonInputConstant::STILLBORN_COUNT];
            $currentBornAliveCount = $currentLitterData[JsonInputConstant::BORN_ALIVE_COUNT];
            $currentRequestState = $currentLitterData[JsonInputConstant::REQUEST_STATE];

            if (($currentStillbornCount !== $stillbornCount || $currentBornAliveCount !== $bornAliveCount)
                //NOTE Only update IMPORTED litters to be safe!
                //Litters inserted by the new/current NSFO webapp are already validated.
             && $currentRequestState === RequestStateType::IMPORTED
            ) {
                //Include litterId IF values need to be updated
                $currentLitterId = $currentLitterData[JsonInputConstant::LITTER_ID];
            }
        }


        $values = [
            JsonInputConstant::MOTHER_ID => $motherId,
            JsonInputConstant::LITTER_DATE => $litterDateString,
            JsonInputConstant::STILLBORN_COUNT => $stillbornCount,
            JsonInputConstant::BORN_ALIVE_COUNT => $bornAliveCount,
            JsonInputConstant::ENTITY_ALREADY_EXISTS => $entityAlreadyExists,
            JsonInputConstant::LITTER_ID => $currentLitterId,
        ];


        //if it is an update, add it to the update collection
        if ($currentLitterId !== null) {
            $this->littersToUpdateByVsmIdAndLitterDate[$key] = $values;
        }


        //Soft validation check
        if ($motherId == null) {
            $this->missingMotherIdCount++;
            return null;

        } elseif ($litterDateString === null) {
            $this->missingLitterDateCount++;
            return null;

        } elseif ($entityAlreadyInInsertBatch) {
            $inBatchConcattedCounts = $this->getConcatCounts($this->newLittersByVsmIdAndLitterDate[$key]);
            if ($inBatchConcattedCounts === $concattedCounts) {
                //Skip exact duplicates
                return null;
            } else {
                /*
                Duplicate litters with a different count.
                Currently there are only 5 cases like this. So they are skipped to save time.
                */
                $this->duplicateLittersWithDifferentLitterCountsCount++;

                $keyGroup = ArrayUtil::get($key, $this->duplicateLitters, []);
                $keyGroup[] = $values;
                $this->duplicateLitters[$key] = $keyGroup;

                return null;
            }
        }

        $this->newLittersByVsmIdAndLitterDate[$key] = $values;

        return $values;
    }


    /**
     * @param int|string $bornAliveCountOrArray
     * @param null|int|string $stillbornCount
     * @return string
     * @throws \Exception
     */
    private function getConcatCounts($bornAliveCountOrArray, $stillbornCount = null)
    {
        if (is_array($bornAliveCountOrArray) && $stillbornCount === null) {
            $bornAliveCount = $bornAliveCountOrArray[JsonInputConstant::BORN_ALIVE_COUNT];
            $stillbornCount = $bornAliveCountOrArray[JsonInputConstant::STILLBORN_COUNT];
        } elseif ($stillbornCount !== null) {
            $bornAliveCount = $bornAliveCountOrArray;
        } else {
            throw new \Exception('Invalid concatCounts input: '.$bornAliveCountOrArray.' | '.$stillbornCount);
        }

        return $bornAliveCount.self::DOUBLE_UNDERSCORE.$stillbornCount;
    }


    /**
     * @param CommandUtil $cmdUtil
     */
    public function update(CommandUtil $cmdUtil)
    {
        parent::run($cmdUtil);

        $this->writeLn('===== Update parent values in animal and litter tables =====');

        $queries = [
            'Match children with existing litters by mother and dateOfBirth = litterDate ...' =>
                "UPDATE animal SET litter_id = v.litter_id
                    FROM (
                      SELECT a.id as animal_id, l.id as litter_id
                      FROM animal a
                        INNER JOIN litter l ON l.animal_mother_id = a.parent_mother_id AND DATE(date_of_birth) = DATE(litter_date)
                      WHERE a.litter_id ISNULL
                    ) AS v(animal_id, litter_id) WHERE animal.id = v.animal_id",

            'Set missing (unique) father in litters ...' =>
                "UPDATE litter SET animal_father_id = v.parent_father_id
                    FROM (
                      SELECT l.id as litter_id, parent_father_id
                      FROM litter l
                        INNER JOIN (
                                     SELECT litter_id, parent_father_id,
                                       DENSE_RANK() OVER (PARTITION BY litter_id ORDER BY parent_father_id ASC) AS rank
                                     FROM animal
                                     WHERE parent_father_id NOTNULL
                                     GROUP BY litter_id, parent_father_id
                                   )g ON g.litter_id = l.id
                      WHERE
                        g.rank = 1 -- Get only litters where all the children have the same father, or don't have a father
                        AND l.animal_father_id ISNULL
                    ) AS v(litter_id, parent_father_id) WHERE litter.id = v.litter_id",

            'Set missing (unique) father in children of litters ...' =>
                "UPDATE animal SET parent_father_id = v.parent_father_id
                    FROM (
                      SELECT
                        a.id as animal_id, gg.parent_father_id--, a.litter_id
                      FROM animal a
                        INNER JOIN (
                                     SELECT l.id as litter_id, parent_father_id
                                     FROM litter l
                                       INNER JOIN (
                                                    SELECT litter_id, parent_father_id,
                                                      DENSE_RANK() OVER (PARTITION BY litter_id ORDER BY parent_father_id ASC) AS rank
                                                    FROM animal
                                                    WHERE parent_father_id NOTNULL
                                                    GROUP BY litter_id, parent_father_id
                                                  )g ON g.litter_id = l.id AND l.animal_father_id = g.parent_father_id
                                     --Father in the litter must match the unique father of the children
                                     WHERE
                                       g.rank = 1 -- Get only litters where all the children have the same father, or don't have a father
                                   )gg ON gg.litter_id = a.litter_id
                      WHERE a.parent_father_id ISNULL
                    ) AS v(animal_id, parent_father_id) WHERE animal.id = v.animal_id",
        ];

        foreach ($queries as $title => $sql) {
            $this->updateBySql($title, $sql);
        }
    }
}