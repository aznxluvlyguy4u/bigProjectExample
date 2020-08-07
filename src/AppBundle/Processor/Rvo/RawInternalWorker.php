<?php


namespace AppBundle\Processor\Rvo;


use AppBundle\Enumerator\ProcessType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Exception\FeatureNotAvailableHttpException;
use AppBundle\Service\AwsQueueServiceBase;
use AppBundle\Service\AwsRawInternalSqsService;
use AppBundle\Worker\Logic\DeclareAnimalFlagAction;
use Aws\Result;
use Psr\Log\LoggerInterface;
use Throwable;

class RawInternalWorker extends SqsRvoProcessorBase
{
    const PROCESS_TYPE = ProcessType::SQS_RAW_INTERNAL_WORKER;
    const LOOP_DELAY_SECONDS = 1;

    // This number should not be too big, too prevent too severe memory leaks
    const MAX_MESSAGES_PER_PROCESS_LIMIT = 5;

    /** @var AwsRawInternalSqsService */
    private $rawInternalQueueService;

    /** @var DeclareAnimalFlagAction  */
    private $declareAnimalFlagAction;

    public function __construct(
        AwsRawInternalSqsService $rawInternalQueueService,
        LoggerInterface $logger
    )
    {
        $this->queueLogger = $logger;
        $this->rawInternalQueueService = $rawInternalQueueService;
    }

    /**
     * @required
     *
     * @param DeclareAnimalFlagAction $service
     */
    public function setDeclareAnimalFlagAction(DeclareAnimalFlagAction $service)
    {
        $this->declareAnimalFlagAction = $service;
    }


    public function run()
    {
        if (!$this->initializeProcessLocker()) {
            return;
        }

        if ($this->rawInternalQueueService->isQueueEmpty()) {

            /*
             * WARNING!
             * Removing this sleep while calling this function in a loop
             * will cause a huge amount of calls to the queue and a huge AWS bill!
             */
            sleep(self::LOOP_DELAY_SECONDS);

        } else {

            $this->process();

        }

        $this->unlockProcess();
    }


    public function process()
    {
        $this->messageCount = 0;

        try {

            do {

                $response = $this->rawInternalQueueService->getNextMessage();
                $xmlRequestBody = AwsQueueServiceBase::getMessageBodyFromResponse($response, false);
                if ($xmlRequestBody) {
                    $this->processNextMessage($response);
                } else {
                    $this->processEmptyMessage($this->rawInternalQueueService, $response);
                }

            } while (
                $xmlRequestBody &&
                $this->messageCount <= self::MAX_MESSAGES_PER_PROCESS_LIMIT &&
                !$this->rawInternalQueueService->isQueueEmpty()
            );

        } catch (Throwable $e) {
            $this->logException($e);
            $this->unlockProcess();
        }

    }

    private function processNextMessage(Result $queueMessage)
    {
        $requestType = $this->getRequestType($queueMessage);
        $rvoXmlResponseContent = AwsQueueServiceBase::getMessageBodyFromResponse($queueMessage, false);

        switch ($requestType) {
            case RequestType::DECLARE_ANIMAL_FLAG:
                $this->declareAnimalFlagAction->process($rvoXmlResponseContent);
                break;
            default:
                $errorMessage = 'Unsupported request type for raw internal worker '.$requestType;
                $this->queueLogger->emergency($errorMessage);
                throw new FeatureNotAvailableHttpException($this->translator, 'Given RequestType: '.$requestType);
        }

        $this->messageCount++;
    }

}