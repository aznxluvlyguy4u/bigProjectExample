<?php

namespace AppBundle\Service\Migration;

use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\DeclareNsfoBase;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\QueryType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
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
    /** @var array */
    private $animalIdsByVsmId;

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

        $this->writeln('=== Migrate litters ===');

        DoctrineUtil::updateTableSequence($this->conn, [DeclareNsfoBase::TABLE_NAME]);

        $this->animalIdsByVsmId = $this->animalRepository->getAnimalPrimaryKeysByVsmIdArray(GenderType::FEMALE);

        $this->writeln('Retrieving current litter data by vsmId and litterDate ...');

        $doubleUnderscore = "'".self::DOUBLE_UNDERSCORE."'";
        $sql = "SELECT
                    CONCAT(mom.name,$doubleUnderscore,DATE(l.litter_date)) as key,
                    mom.name AS vsm_id, DATE(l.litter_date) as litter_date, born_alive_count, stillborn_count, request_state
                FROM litter l
                  INNER JOIN animal mom ON mom.id = l.animal_mother_id
                  INNER JOIN declare_nsfo_base b ON b.id = l.id
                WHERE request_state <> '".RequestStateType::REVOKED."'";
        $this->currentLittersByVsmIdAndLitterDate = SqlUtil::createGroupedSearchArrayFromSqlResults($this->conn->query($sql)->fetchAll(),'key');

        /*
         * NOTE! The batches are processed alphabetically. So make sure the base insert batch starts first alphabetically.
         */
        $this->sqlBatchProcessor
            ->purgeAllSets()
            ->createBatchSet(QueryType::BASE_INSERT)
            ->createBatchSet(QueryType::INSERT)
//            ->createBatchSet(QueryType::UPDATE)
        ;

        $baseInsertBatchSet = $this->sqlBatchProcessor->getSet(QueryType::BASE_INSERT);
        $insertBatchSet = $this->sqlBatchProcessor->getSet(QueryType::INSERT);
        //$updateBatchSet = $this->sqlBatchProcessor->getSet(QueryType::UPDATE);

        $baseInsertBatchSet->setSqlQueryBase("INSERT INTO declare_nsfo_base (id, log_date, request_state, type) VALUES ");

        $insertBatchSet->setSqlQueryBase("INSERT INTO litter (id, animal_mother_id, litter_date,
                                            stillborn_count, born_alive_count, status, is_abortion, is_pseudo_pregnancy) VALUES ");
//
//        $updateBatchSet->setSqlQueryBase('INSERT INTO tag (id, animal_id, tag_status, animal_order_number, order_date, uln_country_code, uln_number) VALUES ');

        $this->writeln('Importing new litterData from csv ...');
        $this->data = $this->parseCSV(self::LITTERS);

        $id = SqlUtil::getMaxId($this->conn, DeclareNsfoBase::TABLE_NAME);
        $firstId = $id+1;
        $logDate = TimeUtil::getLogDateString();
        $imported = "'".RequestStateType::IMPORTED."'";

        $this->missingLitterDateCount = 0;
        $this->missingMotherIdCount = 0;
        $this->duplicateLittersWithDifferentLitterCountsCount = 0;
        $this->newLittersByVsmIdAndLitterDate = [];
        $this->duplicateLitters = [];

        try {

            $this->sqlBatchProcessor->start(count($this->data));
            foreach ($this->data as $record) {
                $litterData = $this->getLitterDataInArray($record);

                if ($litterData === null) {
                    $baseInsertBatchSet->incrementSkippedCount();
                    $insertBatchSet->incrementSkippedCount();
                } else {
                    $motherId = $litterData[JsonInputConstant::MOTHER_ID];
                    $litterDate = $litterData[JsonInputConstant::LITTER_DATE];
                    $stillbornCount = $litterData[JsonInputConstant::STILLBORN_COUNT];
                    $bornAliveCount = $litterData[JsonInputConstant::BORN_ALIVE_COUNT];
                    $litterAlreadyExists = $litterData[JsonInputConstant::ENTITY_ALREADY_EXISTS];

                    if ($litterAlreadyExists) {
                        //Update current litter if update criteria match
                        $baseInsertBatchSet->incrementAlreadyDoneCount();
                        $insertBatchSet->incrementAlreadyDoneCount();

                    } else {
                        //Insert new litter
                        //$this->sqlBatchProcessor->advanceProgressBar();
                        $baseInsertBatchSet->appendValuesString("(".++$id.",'".$logDate."',$imported,'Litter')");
                        $insertBatchSet->appendValuesString("($id," .$motherId.",'".$litterDate."',"
                            .$stillbornCount.",".$bornAliveCount.",$imported,false,false)");
                        $baseInsertBatchSet->incrementBatchCount();
                        $insertBatchSet->incrementBatchCount();
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
        $entityAlreadyInBatch = key_exists($key, $this->newLittersByVsmIdAndLitterDate);
        $concattedCounts = $this->getConcatCounts($bornAliveCount, $stillbornCount);

        //Hard validation check
        if (!ctype_digit($vsmId) && !is_int($vsmId)) {
            throw new \Exception('Incorrect vsmId found: '.$vsmId.' |key '.$key);

        } elseif (!ctype_digit($bornAliveCount) && !is_int($bornAliveCount)) {
            throw new \Exception('Incorrect bornAliveCount found: '.$bornAliveCount.' |key '.$key);

        } elseif (!ctype_digit($stillbornCount) && !is_int($stillbornCount)) {
            throw new \Exception('Incorrect stillbornCount found: '.$stillbornCount.' |key '.$key);
        }


        $values = [
            JsonInputConstant::MOTHER_ID => $motherId,
            JsonInputConstant::LITTER_DATE => $litterDateString,
            JsonInputConstant::STILLBORN_COUNT => intval($stillbornCount),
            JsonInputConstant::BORN_ALIVE_COUNT => intval($bornAliveCount),
            JsonInputConstant::ENTITY_ALREADY_EXISTS => $entityAlreadyExists
        ];


        //Soft validation check
        if ($motherId == null) {
            $this->missingMotherIdCount++;
            return null;

        } elseif ($litterDateString === null) {
            $this->missingLitterDateCount++;
            return null;

        } elseif ($entityAlreadyInBatch) {
            $inBatchConcattedCounts = $this->getConcatCounts($this->newLittersByVsmIdAndLitterDate[$key]);
            if ($inBatchConcattedCounts === $concattedCounts) {
                //Skip exact duplicates
                return null;
            } else {
                /*
                TODO Decide on what to do with these, duplicate litters with a different count.
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
}