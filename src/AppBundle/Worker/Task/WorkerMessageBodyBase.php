<?php

namespace AppBundle\Worker\Task;

use JMS\Serializer\Annotation as JMS;
use AppBundle\Component\Utils;
use DateTime;

/**
 * Class WorkerMessageBodyBase
 *
 * @package AppBundle\Worker\Task
 */
abstract class WorkerMessageBodyBase
{
    /**
     * @var string
     * @JMS\Type("string")
     */
    protected $messageId;

    /**
     * @var string
     * @JMS\Type("string")
     */
    protected $taskType;
    
    /**
     * @var boolean
     * @JMS\Type("boolean")
     */
    protected $onlyProcessBlankRecords;
    
    /**
     * @var DateTime
     * @JMS\Type("DateTime")
     */
    protected $creationDate;

    /**
     * @var string
     * @JMS\Type("string")
     */
    protected $notes;

    /**
     * WorkerMessageBody constructor.
     */
    public function __construct()
    {
        $this->setMessageId(Utils::generatePersonId());
        $this->creationDate = new \DateTime();
    }

    /**
     * @return string
     */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     * @param string $messageId
     */
    public function setMessageId($messageId)
    {
        $this->messageId = $messageId;
    }

    /**
     * @return string
     */
    public function getTaskType()
    {
        return $this->taskType;
    }

    /**
     * @param string $taskType
     */
    public function setTaskType($taskType)
    {
        $this->taskType = $taskType;
    }

    /**
     * @return boolean
     */
    public function isOnlyProcessBlankRecords()
    {
        return $this->onlyProcessBlankRecords;
    }

    /**
     * @param boolean $onlyProcessBlankRecords
     */
    public function setOnlyProcessBlankRecords($onlyProcessBlankRecords)
    {
        $this->onlyProcessBlankRecords = $onlyProcessBlankRecords;
    }

    /**
     * @return DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param DateTime $creationDate
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;
    }

    /**
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @param string $notes
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;
    }


}