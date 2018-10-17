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
    private $fatherId;

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
        $this->setLevelType(WorkerLevelType::LITTER);
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
    public function getFatherId()
    {
        return $this->fatherId;
    }

    /**
     * @param int $fatherId
     */
    public function setFatherId($fatherId)
    {
        $this->fatherId = $fatherId;
    }

    /**
     * @return array
     */
    public function getChildrenIds()
    {
        return $this->childrenIds ? $this->childrenIds : [];
    }

    /**
     * @param array $childrenIds
     */
    public function setChildrenIds($childrenIds)
    {
        $this->childrenIds = $childrenIds;
    }


    /**
     * @return array
     */
    public function getAllAnimalIds(): array
    {
        return array_merge($this->getChildrenIds(), $this->getParentIds());
    }


    /**
     * @return array
     */
    public function getParentIds(): array
    {
        $ids = [];
        if ($this->fatherId) {
            $ids[] = $this->fatherId;
        }

        if ($this->motherId) {
            $ids[] = $this->motherId;
        }
        return $ids;
    }
}