<?php

namespace AppBundle\Worker\Task;

use AppBundle\Enumerator\WorkerTaskType;
use JMS\Serializer\Annotation as JMS;

/**
 * Class WorkerMessageBody
 *
 * @package AppBundle\Worker\Task
 */
class WorkerMessageBody extends WorkerMessageBodyBase
{
    /**
     * @var string
     * @JMS\Type("string")
     */
    private $levelType;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $levelId;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $scope;

    /**
     * WorkerMessageBody constructor.
     */
    public function __construct()
    {
        parent::__construct();
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


}