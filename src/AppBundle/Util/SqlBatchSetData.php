<?php


namespace AppBundle\Util;


use Doctrine\DBAL\Connection;

/**
 * Class SqlBatchSetData
 */
class SqlBatchSetData
{
    /** @var String */
    private $key;

    /** @var Connection */
    private $conn;
    /** @var int */
    private $batchSize;

    //Sql batch values
    /** @var string */
    private $sqlQueryBase;
    /** @var string */
    private $sqlQueryBaseEnd;
    /** @var string */
    private $valuesString;
    /** @var string */
    private $prefix;
    /** @var int */
    private $sqlBatchCount;

    //Overal batch counters
    /** @var int */
    private $recordsDoneCount;
    /** @var int */
    private $recordsSkipped;
    /** @var int */
    private $recordsAlreadyDone;

    /**
     * SqlBatchSetData constructor.
     * @param $key
     * @param $batchSize
     * @param $conn
     */
    public function __construct($key, $batchSize, $conn)
    {
        $this->key = $key;
        $this->batchSize = $batchSize;
        $this->conn = $conn;
        $this->sqlQueryBaseEnd = '';
        $this->resetOverallBatchCounters();
    }

    /**
     * @return String
     */
    public function getKey()
    {
        return $this->key;
    }


    /** @return SqlBatchSetData $this */
    public function resetOverallBatchCounters()
    {
        $this->recordsSkipped = 0;
        $this->recordsDoneCount = 0;
        $this->recordsAlreadyDone = 0;
        $this->sqlBatchCount = 0;
        return $this;
    }


    /** @return SqlBatchSetData $this */
    public function incrementDoneCount() {
        $this->recordsDoneCount++;
        return $this;
    }

    /** @return SqlBatchSetData $this */
    public function incrementSkippedCount() {
        $this->recordsSkipped++;
        return $this;
    }

    /** @return SqlBatchSetData $this */
    public function incrementAlreadyDoneCount() {
        $this->recordsAlreadyDone++;
        return $this;
    }

    /** @return SqlBatchSetData $this */
    public function incrementBatchCount() {
        $this->sqlBatchCount++;
        return $this;
    }


    /** @return SqlBatchSetData $this */
    public function resetSqlBatchValues()
    {
        $this->valuesString = '';
        $this->prefix = '';
        $this->recordsDoneCount += $this->sqlBatchCount;
        $this->sqlBatchCount = 0;
        return $this;
    }


    /**
     * @param $sqlBase
     * @return SqlBatchSetData $this
     */
    public function setSqlQueryBase($sqlBase)
    {
        $this->sqlQueryBase = $sqlBase;
        return $this;
    }

    /**
     * @return string
     */
    public function getSqlQueryBase()
    {
        return $this->sqlQueryBase;
    }

    /**
     * @return string
     */
    public function getSqlQueryBaseEnd()
    {
        return $this->sqlQueryBaseEnd;
    }

    /**
     * @param string $sqlQueryBaseEnd
     * @return SqlBatchSetData
     */
    public function setSqlQueryBaseEnd($sqlQueryBaseEnd)
    {
        $this->sqlQueryBaseEnd = $sqlQueryBaseEnd;
        return $this;
    }


    /**
     * @param $string
     * @return SqlBatchSetData $this
     */
    public function appendValuesString($string)
    {
        $this->sqlBatchCount++;
        $this->valuesString = $this->valuesString.$this->prefix.$string;
        $this->prefix = ',';
        return $this;
    }


    /**
     * @return SqlBatchSetData $this
     */
    public function processAtBatchSize()
    {
        //Inserting by Batch
        if($this->sqlBatchCount%$this->batchSize === 0 && $this->sqlBatchCount != 0) {
            $this->processAnimalMigrationTableRecordsByBatch();
        }
        return $this;
    }


    /**
     * @return int
     */
    public function processAnimalMigrationTableRecordsByBatch()
    {
        if(trim($this->valuesString) === '') { return 0; }
        $sql = $this->sqlQueryBase." ".$this->valuesString." ".$this->sqlQueryBaseEnd;
        $sqlBatchCount = SqlUtil::updateWithCount($this->conn, $sql);
        $this->resetSqlBatchValues();
        return $sqlBatchCount;
    }


    /**
     * @return string
     */
    public function getProgressBarMessage()
    {
        return $this->key.' skipped|already||inBatch|done:  '
            .$this->recordsSkipped.'|'.$this->recordsAlreadyDone.'||'.$this->sqlBatchCount.'|'.$this->recordsDoneCount;
    }
}