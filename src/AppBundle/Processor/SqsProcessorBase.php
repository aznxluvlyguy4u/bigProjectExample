<?php


namespace AppBundle\Processor;


use AppBundle\Service\ProcessLockerInterface;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;

abstract class SqsProcessorBase implements SqsProcessorInterface
{
    // Set this value in the child class!
    const PROCESS_TYPE = null;

    // These values can be optionally overwritten in the child class.
    const LOOP_DELAY_SECONDS = 1;
    const MAX_MESSAGES_PER_PROCESS_LIMIT = 10;

    const ERROR_LOG_HEADER = '===== SQS FEEDBACK WORKER =====';

    // Set this logger in the child class!
    /** @var LoggerInterface */
    protected $queueLogger;

    /** @var Logger */
    protected $exceptionLogger;
    /** protected ProcessLockerInterface */
    protected $processLocker;
    /** @var TranslatorInterface */
    protected $translator;

    /** @var int */
    private $processId;
    /** @var int */
    protected $messageCount;

    /**
     * @required
     *
     * @param Logger $exceptionLogger
     * @param ProcessLockerInterface $processLocker
     * @param TranslatorInterface $translator
     */
    public function setBaseServices(

        Logger $exceptionLogger,
        ProcessLockerInterface $processLocker,
        TranslatorInterface $translator
    )
    {
        $this->exceptionLogger = $exceptionLogger;
        $this->processLocker = $processLocker;
        $this->translator = $translator;
    }

    /**
     * @return bool
     */
    protected function initializeProcessLocker(): bool
    {
        $isLockedResult = $this->processLocker->isProcessLimitNotReachedCheckForQueueService(
            static::PROCESS_TYPE,
            $this->queueLogger
        );

        $this->processId = key($isLockedResult);
        return reset($isLockedResult); // get the first value: is process limit not reached yet = true
    }


    protected function unlockProcess()
    {
        $this->processLocker->unlockProcessForQueueService(
            static::PROCESS_TYPE,
            $this->processId,
            $this->queueLogger
        );
    }

    protected function logException(\Throwable $exception)
    {
        $this->queueLogger->error(static::ERROR_LOG_HEADER);
        $this->queueLogger->error($exception->getMessage());
        $this->queueLogger->error($exception->getTraceAsString());

        $this->exceptionLogger->error(static::ERROR_LOG_HEADER);
        $this->exceptionLogger->error($exception->getMessage());
        $this->exceptionLogger->error($exception->getTraceAsString());
    }
}