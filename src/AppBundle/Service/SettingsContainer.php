<?php


namespace AppBundle\Service;


class SettingsContainer
{
    /** @var int */
    private $maxFeedbackWorkers;
    /** @var int */
    private $maxRawExternalWorkers;
    /** @var int */
    private $maxRawInternalWorkers;

    public function __construct(
        int $maxFeedbackWorkers = 1,
        int $maxRawExternalWorkers = 1,
        int $maxRawInternalWorkers = 1
    )
    {
        $this->maxFeedbackWorkers = $maxFeedbackWorkers;
        $this->maxRawExternalWorkers = $maxRawExternalWorkers;
        $this->maxRawInternalWorkers = $maxRawInternalWorkers;
    }

    /**
     * @return int
     */
    public function getMaxFeedbackWorkers(): int
    {
        return $this->maxFeedbackWorkers;
    }

    /**
     * @return int
     */
    public function getMaxRawExternalWorkers(): int
    {
        return $this->maxRawExternalWorkers;
    }

    /**
     * @return int
     */
    public function getMaxRawInternalWorkers(): int
    {
        return $this->maxRawInternalWorkers;
    }


}