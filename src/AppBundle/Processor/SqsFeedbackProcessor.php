<?php


namespace AppBundle\Processor;


use AppBundle\Enumerator\ProcessType;
use AppBundle\Enumerator\SqsCommandType;
use AppBundle\Exception\FeatureNotAvailableHttpException;
use AppBundle\Exception\Sqs\SqsMessageMissingTaskTypeException;
use AppBundle\Service\AwsFeedbackQueueService;
use AppBundle\Service\AwsQueueServiceBase;
use AppBundle\Service\Invoice\BatchInvoiceService;
use AppBundle\Service\Worker\SyncAnimalRelocationProcessor;
use AppBundle\Service\Worker\SyncHealthCheckProcessor;
use AppBundle\Util\ArrayUtil;
use Aws\Result;
use Monolog\Logger;
use Symfony\Component\Translation\TranslatorInterface;

class SqsFeedbackProcessor extends SqsProcessorBase
{
    const PROCESS_TYPE = ProcessType::SQS_FEEDBACK_WORKER;
    const LOOP_DELAY_SECONDS = 10;

    /** @var AwsFeedbackQueueService */
    private $feedbackQueueService;
    /** @var TranslatorInterface */
    private $translator;

    /** @var SyncAnimalRelocationProcessor */
    private $syncAnimalRelocationProcessor;
    /** @var SyncHealthCheckProcessor */
    private $syncHealthCheckProcessor;

    /** @var BatchInvoiceService */
    private $batchInvoiceService;

    public function __construct(AwsFeedbackQueueService $feedbackQueueService,
                                Logger $queueLogger,
                                TranslatorInterface $translator,
                                SyncAnimalRelocationProcessor $syncAnimalRelocationProcessor,
                                SyncHealthCheckProcessor $syncHealthCheckProcessor,
                                BatchInvoiceService $batchInvoiceService
    )
    {
        $this->queueLogger = $queueLogger;

        $this->feedbackQueueService = $feedbackQueueService;
        $this->translator = $translator;

        $this->syncAnimalRelocationProcessor = $syncAnimalRelocationProcessor;
        $this->syncHealthCheckProcessor = $syncHealthCheckProcessor;
        $this->batchInvoiceService = $batchInvoiceService;
    }

    /**
     * @inheritDoc
     */
    public function run()
    {
        if (!$this->initializeProcessLocker()) {
            return;
        }

        $this->processAllFoundMessages();

        /*
         * WARNING!
         * Removing this sleep while calling this function in a loop
         * will cause a huge amount of calls to the queue and a huge AWS bill!
         */
        sleep(self::LOOP_DELAY_SECONDS);

        $this->unlockProcess();
    }


    private function processAllFoundMessages()
    {
        $this->messageCount = 0;

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

        $taskCountMessage = (empty($this->messageCount) ? 'No' : $this->messageCount)
            . ' ' . self::PROCESS_TYPE.' messages processed';
        if (!empty($this->messageCount)) {
            $this->queueLogger->debug($taskCountMessage);
        }
    }


    /**
     * @param Result $queueMessage
     * @throws SqsMessageMissingTaskTypeException
     * @throws \Throwable
     */
    private function processNextMessage(Result $queueMessage)
    {
        $this->queueLogger->debug('New '.self::PROCESS_TYPE.' message found!');
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
            case SqsCommandType::BATCH_INVOICE_GENERATION:
                $this->batchInvoiceService->createBatchInvoices(AwsQueueServiceBase::getMessageBodyFromResponse($queueMessage, false));
                break;
            default:
                throw new FeatureNotAvailableHttpException($this->translator, 'Given TaskType: '.$taskTypeName);
        }

        $this->feedbackQueueService->deleteMessage($queueMessage);

        $this->messageCount++;
    }
}