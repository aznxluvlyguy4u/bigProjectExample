<?php

namespace AppBundle\Service\Migration;

use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\DeclareNsfoBase;
use AppBundle\Enumerator\QueryType;
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

    /** @inheritdoc */
    public function __construct(ObjectManager $em, $rootDir)
    {
        parent::__construct($em, $rootDir);
        $this->newLittersByVsmIdAndLitterDate = [];
    }

    /** @inheritDoc */
    function run(CommandUtil $cmdUtil)
    {
        parent::run($cmdUtil);

        $this->writeln('=== Migrate litters ===');

        DoctrineUtil::updateTableSequence($this->conn, [DeclareNsfoBase::TABLE_NAME]);

        $this->animalIdsByVsmId = $this->animalRepository->getAnimalPrimaryKeysByVsmIdArray();

        $this->writeln('Retrieving current litter data by vsmId and litterDate ...');

        $doubleUnderscore = "'".self::DOUBLE_UNDERSCORE."'";
        $sql = "SELECT
                    CONCAT(mom.name,$doubleUnderscore,DATE(l.litter_date)) as key,
                    mom.name AS vsm_id, DATE(l.litter_date) as litter_date, born_alive_count, stillborn_count, request_state
                FROM litter l
                  INNER JOIN animal mom ON mom.id = l.animal_mother_id
                  INNER JOIN declare_nsfo_base b ON b.id = l.id";
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

//        $baseInsertBatchSet->setSqlQueryBase("INSERT INTO declare_base_nsfo (id, log_date, request_id, message_id, request_state,
//                                      action, recovery_indicator, relation_number_keeper, ubn, type, action_by_id) VALUES ");
//
//        $insertBatchSet->setSqlQueryBase("INSERT INTO declare_tag_replace (id, animal_id, animal_type, replace_date,
//                                 uln_country_code_to_replace, uln_number_to_replace, animal_order_number_to_replace,
//                                 uln_country_code_replacement, uln_number_replacement, animal_order_number_replacement)
//                    VALUES ");
//
//        $updateBatchSet->setSqlQueryBase('INSERT INTO tag (id, animal_id, tag_status, animal_order_number, order_date, uln_country_code, uln_number) VALUES ');

        $this->writeln('Importing new litterData from csv ...');
        $this->data = $this->parseCSV(self::LITTERS);

        $maxDeclareNsfoBaseId = SqlUtil::getMaxId($this->conn, DeclareNsfoBase::TABLE_NAME);
        $this->missingLitterDateCount = 0;
        $this->missingMotherIdCount = 0;
        //$this->sqlBatchProcessor->start(count($this->data));
        foreach ($this->data as $record) {
            $litterData = $this->getLitterDataInArray($record);
            //$this->sqlBatchProcessor->advanceProgressBar();
        }
        //$this->sqlBatchProcessor->end();
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
        $litterDateString = TimeUtil::getTimeStampForSqlFromAnyDateString($record[6]);
        $bornAliveCount = $record[7]; //always has a number value as string
        $stillbornCount = $record[8]; //always has a number value as string
        //$suckleCount = $record[10]; //is always 0 in this file;

        //Indirect data
        $motherId = ArrayUtil::get($vsmId, $this->animalIdsByVsmId);
        $key = $vsmId.self::DOUBLE_UNDERSCORE.$litterDateString;
        $entityAlreadyExists = key_exists($key, $this->currentLittersByVsmIdAndLitterDate);
        $entityAlreadyInBatch = key_exists($key, $this->newLittersByVsmIdAndLitterDate);

        //Hard validation check
        if (!ctype_digit($vsmId) && !is_int($vsmId)) {
            throw new \Exception('Incorrect vsmId found: '.$vsmId.' |key '.$key);

        } elseif (!ctype_digit($bornAliveCount) && !is_int($bornAliveCount)) {
            throw new \Exception('Incorrect bornAliveCount found: '.$bornAliveCount.' |key '.$key);

        } elseif (!ctype_digit($stillbornCount) && !is_int($stillbornCount)) {
            throw new \Exception('Incorrect stillbornCount found: '.$stillbornCount.' |key '.$key);

        } elseif ($entityAlreadyInBatch) {
            throw new \Exception('Duplicate litter found: '.$key.' |key '.$key);
        }


        //Soft validation check
        if ($motherId == null) {
            $this->missingMotherIdCount++;
            return null;

        } elseif ($litterDateString === null) {
            $this->missingLitterDateCount++;
            return null;
        }


        return [
            JsonInputConstant::MOTHER_ID => $motherId,
            JsonInputConstant::LITTER_DATE => $litterDateString,
            JsonInputConstant::STILLBORN_COUNT => $stillbornCount,
            JsonInputConstant::BORN_ALIVE_COUNT => $bornAliveCount,
            JsonInputConstant::ENTITY_ALREADY_EXISTS => $entityAlreadyExists
        ];
    }
}