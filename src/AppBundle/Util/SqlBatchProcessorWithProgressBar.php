<?php


namespace AppBundle\Util;


use Doctrine\DBAL\Connection;

/**
 * Class SqlBatchProcessorWithProgressBar
 */
class SqlBatchProcessorWithProgressBar
{
    const BATCH_SIZE = 10000;

    /** @var CommandUtil */
    private $cmdUtil;
    /** @var Connection */
    private $conn;
    /** @var int */
    private $batchSize;

    /** @var array */
    private $sqlBatchSets;

    /**
     * SqlBatchProcessorWithProgressBar constructor.
     * @param Connection $conn
     * @param CommandUtil $cmdUtil
     * @param int $batchSize
     */
    public function __construct(Connection $conn, CommandUtil $cmdUtil, $batchSize = self::BATCH_SIZE)
    {
        $this->conn = $conn;
        $this->cmdUtil = $cmdUtil;
        $this->batchSize = $batchSize;
        $this->sqlBatchSets = [];
    }


    /**
     * @param string|int $key
     * @return SqlBatchProcessorWithProgressBar $this
     * @throws \Exception
     */
    public function createBatchSet($key)
    {
        if (key_exists($key, $this->sqlBatchSets)) {
            throw new \Exception('Key is already used for another batchSet', 428);
        }

        $this->sqlBatchSets[$key] = new SqlBatchSetData($key, $this->batchSize, $this->conn);
        return $this;
    }


    /**
     * @return SqlBatchProcessorWithProgressBar $this
     */
    public function purgeAllSets()
    {
        foreach ($this->sqlBatchSets as $key => $set) {
            $this->sqlBatchSets[$key] = null;
        }
        $this->sqlBatchSets = [];
        gc_collect_cycles();
        return $this;
    }


    /**
     * @param string|int $key
     * @return SqlBatchSetData
     * @throws \Exception
     */
    public function getSet($key)
    {
        if (!key_exists($key, $this->sqlBatchSets)) {
            throw new \Exception('Batch set for given key does not exist', 428);
        }

        return $this->sqlBatchSets[$key];
    }


    /**
     * @param int $recordsCount
     * @return SqlBatchProcessorWithProgressBar $this
     * @throws \Exception
     */
    public function start($recordsCount)
    {
        if (count($this->sqlBatchSets) == 0) {
            throw new \Exception('Sql batchSets are empty', 428);
        }

        /** @var SqlBatchSetData $set */
        foreach ($this->sqlBatchSets as $set) {
            $set->resetOverallBatchCounters();
            if ($set->getSqlQueryBase() === null) {
                throw new \Exception('SqlBatchQuery ISNULL for set: '.  $set->getKey(), 428);
            }
        }

        $this->cmdUtil->setStartTimeAndPrintIt($recordsCount+1, 1, 'Importing records ...');

        return $this;
    }



    /** @return SqlBatchProcessorWithProgressBar $this */
    public function advanceProgressBar()
    {
        $this->cmdUtil->advanceProgressBar(1,$this->getProgressBarMessage());
        return $this;
    }

    /**
     * @return string
     */
    private function getProgressBarMessage()
    {
        $prefix = '';
        $message = '';

        if (count($this->sqlBatchSets) > 2) {
            /** @var SqlBatchSetData $set */
            foreach ($this->sqlBatchSets as $set) {
                $message = $message . $prefix . $set->getMinimalProgressBarMessage();
                $prefix = '* ';
            }

        } else {
            /** @var SqlBatchSetData $set */
            foreach ($this->sqlBatchSets as $set) {
                $message = $message . $prefix . $set->getProgressBarMessage();
                $prefix = ' ** ';
            }
        }

        return $message;
    }


    /**
     * @return SqlBatchProcessorWithProgressBar $this
     */
    public function processAtBatchSize()
    {
        /** @var SqlBatchSetData $set */
        foreach ($this->sqlBatchSets as $set) {
            $set->processAtBatchSize();
        }
        return $this;
    }



    /**
     * @return SqlBatchProcessorWithProgressBar $this
     */
    public function end()
    {
        /** @var SqlBatchSetData $set */
        foreach ($this->sqlBatchSets as $set) {
            $set->processAnimalMigrationTableRecordsByBatch();
        }

        $this->cmdUtil->setProgressBarMessage($this->getProgressBarMessage());
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
        foreach ($this->sqlBatchSets as $set) {
            $set->resetOverallBatchCounters();
            $set->resetSqlBatchValues();
        }
        $this->cmdUtil->printClosingLine();
        return $this;
    }


}