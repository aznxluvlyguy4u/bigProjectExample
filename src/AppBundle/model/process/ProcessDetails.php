<?php


namespace AppBundle\model\process;


class ProcessDetails
{
    /** @var int */
    private $total;

    /** @var int */
    private $processed;

    /** @var int */
    private $new;

    /** @var int */
    private $skipped;

    /** @var int */
    private $updated;

    /** @var string|null */
    private $logMessage;

    /**
     * @return int
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * @param  int  $total
     * @return ProcessDetails
     */
    public function setTotal(int $total): ProcessDetails
    {
        $this->total = $total;
        return $this;
    }

    /**
     * @return int
     */
    public function getProcessed(): int
    {
        return $this->processed;
    }

    /**
     * @param  int  $processed
     * @return ProcessDetails
     */
    public function setProcessed(int $processed): ProcessDetails
    {
        $this->processed = $processed;
        return $this;
    }

    /**
     * @return int
     */
    public function getNew(): int
    {
        return $this->new;
    }

    /**
     * @param  int  $new
     * @return ProcessDetails
     */
    public function setNew(int $new): ProcessDetails
    {
        $this->new = $new;
        return $this;
    }

    /**
     * @return int
     */
    public function getSkipped(): int
    {
        return $this->skipped;
    }

    /**
     * @param  int  $skipped
     * @return ProcessDetails
     */
    public function setSkipped(int $skipped): ProcessDetails
    {
        $this->skipped = $skipped;
        return $this;
    }

    /**
     * @return int
     */
    public function getUpdated(): int
    {
        return $this->updated;
    }

    /**
     * @param  int  $updated
     * @return ProcessDetails
     */
    public function setUpdated(int $updated): ProcessDetails
    {
        $this->updated = $updated;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getLogMessage(): ?string
    {
        return $this->logMessage;
    }

    /**
     * @param  string|null  $logMessage
     * @return ProcessDetails
     */
    public function setLogMessage(?string $logMessage): ProcessDetails
    {
        $this->logMessage = $logMessage;
        return $this;
    }


}
