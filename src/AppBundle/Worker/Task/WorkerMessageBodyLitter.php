<?php

namespace AppBundle\Worker\Task;

use AppBundle\Enumerator\WorkerLevelType;
use AppBundle\Enumerator\WorkerTaskType;
use JMS\Serializer\Annotation as JMS;

/**
 * Class WorkerMessageBodyLitter
 *
 * @package AppBundle\Worker\Task
 */
class WorkerMessageBodyLitter extends WorkerMessageBody
{
    /**
     * @var int
     * @JMS\Type("integer")
     */
    private $motherId;

    /**
     * @var int
     * @JMS\Type("integer")
     */
    private $father;

    /**
     * @var array
     * @JMS\Type("array")
     */
    private $childrenIds;

    /**
     * WorkerMessageBody constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTaskType(WorkerTaskType::GENERATE_RESULT_TABLE_RECORDS);
        $this->setTaskType(WorkerLevelType::LITTER);
    }

    /**
     * @return int
     */
    public function getMotherId()
    {
        return $this->motherId;
    }

    /**
     * @param int $motherId
     */
    public function setMotherId($motherId)
    {
        $this->motherId = $motherId;
    }

    /**
     * @return int
     */
    public function getFather()
    {
        return $this->father;
    }

    /**
     * @param int $father
     */
    public function setFather($father)
    {
        $this->father = $father;
    }

    /**
     * @return array
     */
    public function getChildrenIds()
    {
        return $this->childrenIds;
    }

    /**
     * @param array $childrenIds
     */
    public function setChildrenIds($childrenIds)
    {
        $this->childrenIds = $childrenIds;
    }


    
}