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

    //Sql batch values
    /** @var string */
    private $sqlQueryBase;
    /** @var string */
    private $insertString;
    /** @var string */
    private $prefix;
    /** @var int */
    private $sqlBatchCount;

    //Overal batch counters
    /** @var int */
    private $recordsInsertedCount;
    /** @var int */
    private $recordsSkipped;
    /** @var int */
    private $recordsAlreadyImported;

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
    }


    /**
     * @param int $recordsCount
     * @return SqlBatchProcessorWithProgressBar $this
     * @throws \Exception
     */
    public function start($recordsCount)
    {
        if ($this->sqlQueryBase === null) {
            throw new \Exception('Sql QueryBase is empty', 428);
        }

        $this->resetOverallBatchCounters();

        $this->cmdUtil->setStartTimeAndPrintIt($recordsCount+1, 1, 'Importing records ...');

        return $this;
    }


    private function resetOverallBatchCounters()
    {
        $this->recordsSkipped = 0;
        $this->recordsInsertedCount = 0;
        $this->recordsAlreadyImported = 0;
    }

    /** @return SqlBatchProcessorWithProgressBar $this */
    public function incrementInsertedCount() {
        $this->recordsInsertedCount++;
        return $this;
    }

    /** @return SqlBatchProcessorWithProgressBar $this */
    public function incrementSkippedCount() {
        $this->recordsSkipped++;
        return $this;
    }

    /** @return SqlBatchProcessorWithProgressBar $this */
    public function incrementAlreadyImportedCount() {
        $this->recordsAlreadyImported++;
        return $this;
    }

    /** @return SqlBatchProcessorWithProgressBar $this */
    public function incrementBatchCount() {
        $this->sqlBatchCount++;
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
        return 'Records skipped|alreadyImported||inBatch|inserted:  '
            .$this->recordsSkipped.'|'.$this->recordsAlreadyImported.'||'.$this->sqlBatchCount.'|'.$this->recordsInsertedCount;
    }


    private function resetSqlBatchValues()
    {
        $this->insertString = '';
        $this->prefix = '';
        $this->recordsInsertedCount += $this->sqlBatchCount;
        $this->sqlBatchCount = 0;
    }


    /**
     * @param $sqlBase
     * @return SqlBatchProcessorWithProgressBar $this
     */
    public function setSqlQueryBase($sqlBase)
    {
        $this->sqlQueryBase = $sqlBase;
        return $this;
    }


    /**
     * @param $string
     * @return SqlBatchProcessorWithProgressBar $this
     */
    public function appendInsertString($string)
    {
        $this->sqlBatchCount++;
        $this->insertString = $this->insertString.$this->prefix.$string;
        $this->prefix = ',';
        return $this;
    }


    /**
     * @return SqlBatchProcessorWithProgressBar $this
     */
    public function insertAtBatchSize()
    {
        //Inserting by Batch
        if($this->sqlBatchCount%$this->batchSize === 0 && $this->sqlBatchCount != 0) {
            $this->insertAnimalMigrationTableRecordsByBatch();
        }
        return $this;
    }


    /**
     * @return int
     */
    private function insertAnimalMigrationTableRecordsByBatch()
    {
        if($this->insertString === '') { return 0; }
        $sql = $this->sqlQueryBase." ".$this->insertString;
        $sqlBatchCount = SqlUtil::updateWithCount($this->conn, $sql);
        $this->resetSqlBatchValues();
        return $sqlBatchCount;
    }

    /**
     * @return SqlBatchProcessorWithProgressBar $this
     */
    public function end()
    {
        $this->insertAnimalMigrationTableRecordsByBatch();

        $this->cmdUtil->setProgressBarMessage($this->getProgressBarMessage());
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
        $this->resetOverallBatchCounters();
        $this->resetSqlBatchValues();
        $this->cmdUtil->printClosingLine();
        return $this;
    }


}