<?php


namespace AppBundle\Processor;


use AppBundle\Enumerator\ProcessType;
use AppBundle\Enumerator\SqsCommandType;
use AppBundle\Exception\FeatureNotAvailableHttpException;
use AppBundle\Exception\Sqs\SqsMessageMissingTaskTypeException;
use AppBundle\Service\AwsFeedbackQueueService;
use AppBundle\Service\AwsQueueServiceBase;
use AppBundle\Service\ProcessLockerInterface;
use AppBundle\Service\SettingsContainer;
use AppBundle\Service\Worker\SyncAnimalRelocationProcessor;
use AppBundle\Service\Worker\SyncHealthCheckProcessor;
use AppBundle\Util\ArrayUtil;
use Aws\Result;
use Monolog\Logger;
use Symfony\Component\Translation\TranslatorInterface;

class SqsFeedbackProcessor
{
    const ERROR_LOG_HEADER = '===== SQS FEEDBACK WORKER =====';
    const LOOP_DELAY_SECONDS = 10;

    /** @var Logger */
    private $logger;
    /** @var AwsFeedbackQueueService */
    private $feedbackQueueService;
    /** @var ProcessLockerInterface */
    private $processLocker;
    /** @var SettingsContainer */
    private $settingsContainer;
    /** @var TranslatorInterface */
    private $translator;

    /** @var SyncAnimalRelocationProcessor */
    private $syncAnimalRelocationProcessor;
    /** @var SyncHealthCheckProcessor */
    private $syncHealthCheckProcessor;

    /** @var int */
    private $processId;
    /** @var int */
    private $taskCount;

    public function __construct(AwsFeedbackQueueService $feedbackQueueService,
                                Logger $logger,
                                ProcessLockerInterface $processLocker,
                                SettingsContainer $settingsContainer,
                                TranslatorInterface $translator,
                                SyncAnimalRelocationProcessor $syncAnimalRelocationProcessor,
                                SyncHealthCheckProcessor $syncHealthCheckProcessor
    )
    {
        $this->feedbackQueueService = $feedbackQueueService;
        $this->logger = $logger;
        $this->processLocker = $processLocker;
        $this->settingsContainer = $settingsContainer;
        $this->translator = $translator;

        $this->syncAnimalRelocationProcessor = $syncAnimalRelocationProcessor;
        $this->syncHealthCheckProcessor = $syncHealthCheckProcessor;
    }

    /**
     * @inheritDoc
     */
    public function process()
    {
        $delayInSeconds = self::LOOP_DELAY_SECONDS;

        if (!$this->initializeProcessLocker()) {
            return;
        }

        while (true) {
            $this->processAllFoundMessages();

            /*
             * WARNING!
             * Removing this sleep will cause a huge amount of calls to the queue and a huge AWS bill!
             */
            $this->logger->info('Sleep '.$delayInSeconds.' seconds ...');
            sleep($delayInSeconds);
        }

        $this->unlockProcess();
    }


    private function processAllFoundMessages()
    {
        $this->taskCount = 0;

        try {
            while ($this->feedbackQueueService->getSizeOfQueue() > 0) {
                $response = $this->feedbackQueueService->getNextMessage();
                $messageBody = AwsQueueServiceBase::getMessageBodyFromResponse($response, false);
                if ($messageBody) {
                    $this->processNextMessage($response);
                }
            }

        } catch (\Throwable $e) {
            $this->logException($e);
            $this->unlockProcess();
        }

        $taskCountMessage = (empty($this->taskCount) ? 'No' : $this->taskCount)
            . ' ' . $this->getProcessType().' messages processed';
        if (empty($this->taskCount)) {
            $this->logger->debug($taskCountMessage);
        } else {
            $this->logger->info($taskCountMessage);
        }
    }


    /**
     * @param Result $queueMessage
     * @throws SqsMessageMissingTaskTypeException
     * @throws \Throwable
     */
    private function processNextMessage(Result $queueMessage)
    {
        $this->logger->debug('New '.$this->getProcessType().' message found!');
        $taskTypeName = !empty($queueMessage) ? AwsQueueServiceBase::getTaskType($queueMessage) : null;

        if (!$taskTypeName) {
            throw new SqsMessageMissingTaskTypeException();
        }

        $taskType = ArrayUtil::get($taskTypeName, SqsCommandType::getConstants(), null);

        switch ($taskType) {
            case SqsCommandType::SYNC_HEALTH_CHECK:
                $this->syncHealthCheckProcessor->process($queueMessage);
                break;
            case SqsCommandType::SYNC_ANIMAL_RELOCATION:
                $this->syncAnimalRelocationProcessor->process($queueMessage);
                break;
            default:
                throw new FeatureNotAvailableHttpException($this->translator, 'Given TaskType: '.$taskTypeName);
        }

        $this->feedbackQueueService->deleteMessage($queueMessage);

        $this->taskCount++;
    }


    private function logException(\Throwable $exception)
    {
        $this->logger->error(self::ERROR_LOG_HEADER);
        $this->logger->error($exception->getMessage());
        $this->logger->error($exception->getTraceAsString());
    }


    private function getProcessType(): string
    {
        return ProcessType::SQS_FEEDBACK_WORKER;
    }

    /**
     * @return bool
     */
    private function initializeProcessLocker(): bool
    {
        $maxWorkerCount = $this->settingsContainer->getMaxFeedbackWorkers();
        $this->processLocker->initializeProcessGroupValues($this->getProcessType());

        if ($this->processLocker->isProcessLimitReached($this->getProcessType())) {
            $this->logger->notice('Max process limit of '.$maxWorkerCount.' reached. '
                .'No new '.$this->getProcessType().' process started.');
            return false;
        }

        $this->processId = $this->processLocker->addProcess($this->getProcessType());
        $this->logger->debug('Initialized feedback processor lock with id: ' . $this->processId);
        return true;
    }


    private function unlockProcess()
    {
        $this->processLocker->removeProcess($this->getProcessType(), $this->processId);
        $this->logger->debug('Unlocked feedback processor lock with id: ' . $this->processId);
    }
}