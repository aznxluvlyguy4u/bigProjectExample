<?php


namespace AppBundle\Service;


class SettingsContainer
{
    /** @var int */
    private $maxFeedbackWorkers;

    public function __construct(int $maxFeedbackWorkers = 1)
    {
        $this->maxFeedbackWorkers = $maxFeedbackWorkers;
    }

    /**
     * @return int
     */
    public function getMaxFeedbackWorkers(): int
    {
        return $this->maxFeedbackWorkers;
    }


}