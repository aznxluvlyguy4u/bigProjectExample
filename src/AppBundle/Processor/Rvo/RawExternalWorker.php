<?php


namespace AppBundle\Processor\Rvo;


use AppBundle\Enumerator\HttpMethod;
use AppBundle\Enumerator\ProcessType;
use AppBundle\Exception\FeatureNotAvailableHttpException;
use AppBundle\Exception\Rvo\RvoExternalWorkerException;
use AppBundle\Service\AwsQueueServiceBase;
use AppBundle\Service\AwsRawExternalSqsService;
use AppBundle\Service\AwsRawInternalSqsService;
use AppBundle\Util\CurlUtil;
use AppBundle\Util\RvoUtil;
use Aws\Result;
use Curl\Curl;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * The Raw External Worker only deals with the raw xml request body and response body.
 * Therefore it can be kept very simple.
 *
 * Class RawExternalWorker
 * @package AppBundle\Processor\Rvo
 */
class RawExternalWorker extends SqsRvoProcessorBase
{
    const PROCESS_TYPE = ProcessType::SQS_RAW_EXTERNAL_WORKER;
    const LOOP_DELAY_SECONDS = 1;

    // This number should not be too big, too prevent too severe memory leaks
    const MAX_MESSAGES_PER_PROCESS_LIMIT = 50;

    /** @var AwsRawExternalSqsService */
    private $rawExternalQueueService;
    /** @var AwsRawInternalSqsService */
    private $rawInternalQueueService;

    /** @var string */
    protected $rvoIrBaseUrl;
    /** @var string */
    private $rvoIrUserName;
    /** @var string */
    private $rvoIrPassword;

    public function __construct(
        AwsRawExternalSqsService $rawExternalQueueService,
        AwsRawInternalSqsService $rawInternalQueueService,
        LoggerInterface $logger,
        string $rvoIrBaseUrl,
        string $rvoIrUserName,
        string $rvoIrPassword
    )
    {
        $this->queueLogger = $logger;

        $this->rawExternalQueueService = $rawExternalQueueService;
        $this->rawInternalQueueService = $rawInternalQueueService;

        $this->rvoIrBaseUrl = $rvoIrBaseUrl;
        $this->rvoIrUserName = $rvoIrUserName;
        $this->rvoIrPassword = $rvoIrPassword;
    }


    public function run()
    {
        if (!$this->initializeProcessLocker()) {
            return;
        }

        if ($this->rawExternalQueueService->isQueueEmpty()) {

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


    private function process()
    {
        $this->messageCount = 0;
        $response = null;

        try {

            do {

                $xmlRequestBody = null;
                $response = $this->rawExternalQueueService->getNextMessage();

                if ($response) {

                    $xmlRequestBody = AwsQueueServiceBase::getMessageBodyFromResponse($response, false);
                    if ($xmlRequestBody) {
                        $this->processNextMessage($response);
                    } else {
                        $this->processEmptyMessage($this->rawExternalQueueService, $response);
                    }

                }

            } while (
                $xmlRequestBody &&
                $this->messageCount <= self::MAX_MESSAGES_PER_PROCESS_LIMIT &&
                !$this->rawExternalQueueService->isQueueEmpty()
            );

        } catch (Throwable $e) {

            if ($response) {
                $this->rawExternalQueueService->moveMessageToErrorQueue($response);
            }

            $this->logException($e);
            $this->unlockProcess();
        }
    }


    private function processNextMessage(Result $queueMessage)
    {
        $this->queueLogger->debug($this->logPrefix().'New message found!');

        $requestType = $this->getRequestType($queueMessage);
        $xmlRequestBody = AwsQueueServiceBase::getMessageBodyFromResponse($queueMessage, false);
        $requestId = AwsQueueServiceBase::getMessageId($queueMessage);

        $this->queueLogger->debug($this->logPrefix()."RequestId $requestId");
        $this->queueLogger->debug($this->logPrefix()."RequestBody: $xmlRequestBody");

        $httpMethod = RvoUtil::getHttpMethod($requestType);
        $url = RvoUtil::getRvoUrl($requestType, $this->rvoIrBaseUrl);

        switch ($httpMethod) {
            case HttpMethod::GET:
                $curl = $this->get($url);
                break;
            case HttpMethod::POST:
                $curl = $this->post($url, $xmlRequestBody);
                break;
            default:
                $errorMessage = 'Unsupported RVO HTTP Method for raw external worker '.$httpMethod;
                $this->queueLogger->emergency($this->logPrefix().$errorMessage);
                throw new FeatureNotAvailableHttpException($this->translator, 'Given HttpMethod: '.$httpMethod);
        }

        if (CurlUtil::is200Response($curl)) {
            // Success response
            $this->queueLogger->debug($this->logPrefix()."Sending success response to raw internal queue");
            $this->queueLogger->debug($this->logPrefix()."ResponseBody: ".$curl->getResponse());
            $this->rawInternalQueueService->send($curl->getResponse(), $requestType, $requestId);

        } else {
            // Failed response
            $this->logException(new RvoExternalWorkerException($curl->response, $curl->getHttpStatus()));
            $this->rawExternalQueueService->sendToErrorQueue($xmlRequestBody, $requestType, $requestId);
        }

        $this->rawExternalQueueService->deleteMessage($queueMessage);

        $this->messageCount++;
    }


    private function createCurl(): Curl
    {
        $curl = new Curl();
        $curl->setBasicAuthentication($this->rvoIrUserName, $this->rvoIrPassword);
        $curl->setHeader('SOAPAction', 'true');
        return $curl;
    }


    private function post(string $url, string $xmlBody): Curl
    {
        $curl = $this->createCurl();
        $curl->post($url, $xmlBody,false);
        return $curl;
    }


    private function get(string $url, $queryParameters = []): Curl
    {
        $curl = $this->createCurl();
        $curl->get($url, $queryParameters);
        return $curl;
    }

}