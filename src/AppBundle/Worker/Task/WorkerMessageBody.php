<?php

namespace AppBundle\Worker\Task;

use JMS\Serializer\Annotation as JMS;
use AppBundle\Component\Utils;
use DateTime;

/**
 * Class WorkerMessageBody
 *
 * @package AppBundle\Worker\Task
 */
class WorkerMessageBody
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
     * @var string
     * @JMS\Type("string")
     */
    protected $levelType;

    /**
     * @var string
     * @JMS\Type("string")
     */
    protected $levelId;

    /**
     * @var string
     * @JMS\Type("string")
     */
    protected $scope;
    
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
     * @return string
     */
    public function getLevelType()
    {
        return $this->levelType;
    }

    /**
     * @param string $levelType
     */
    public function setLevelType($levelType)
    {
        $this->levelType = $levelType;
    }

    /**
     * @return string
     */
    public function getLevelId()
    {
        return $this->levelId;
    }

    /**
     * @param string $levelId
     */
    public function setLevelId($levelId)
    {
        $this->levelId = $levelId;
    }

    /**
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param string $scope
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
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