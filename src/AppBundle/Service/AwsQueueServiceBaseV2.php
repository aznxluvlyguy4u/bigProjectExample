<?php

namespace AppBundle\Service;

use AppBundle\Constant\AwsSqs;
use AppBundle\Constant\Environment;
use AppBundle\Service\Aws\AwsSqsService;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\EnvUtil;
use Aws\Api\AbstractModel;
use Aws\Sqs\SqsClient;

/**
 * V2 has a new format for the queue names
 * new format: nsfo_<env>_<slot>_<animalType>_<queue-type>_<error>
 *
 * Class AWSQueueServiceBase
 * @package AppBundle\Service
 */
abstract class AwsQueueServiceBaseV2 implements QueueServiceInterface
{
    // Set the queue type in the implemented service
    const QUEUE_TYPE = null;

    const TASK_TYPE = 'TaskType';
    const MESSAGE_ID = 'MessageId';

    /** @var SqsClient */
    protected $queueService;

    /** @var string */
    protected $queueUrl;
    /** @var string */
    protected $errorQueueUrl;

    /** @var string */
    protected $queueId;
    /** @var string */
    protected $errorQueueId;

    /** @var string */
    protected $selectedEnvironment;

    /**
     * AWSQueueServiceBase constructor, initialize SQS config
     * @param AwsSqsService $sqsService
     * @param string $selectedEnvironment
     * @param string $currentEnvironment
     * @param string $animalEnvType
     * @param string $slot
     */
    public function __construct($sqsService,
                                $selectedEnvironment, $currentEnvironment,
                                $animalEnvType, $slot
    )
    {
        $this->queueService = $sqsService;

        if ($currentEnvironment === Environment::TEST) { $selectedEnvironment = $currentEnvironment; }
        $this->selectedEnvironment = $selectedEnvironment;

        $this->queueId = $this->parseQueueId($animalEnvType, $this->selectedEnvironment, $slot);
        $this->queueUrl = $this->createQueueUrl($this->queueId);

        $this->errorQueueId = $this->parseErrorQueueId($this->queueId);
        $this->errorQueueUrl = $this->createQueueUrl($this->errorQueueId);
    }


    /**
     * @param string $queueId
     * @return string|null
     */
    public function createQueueUrl($queueId)
    {
        $result = $this->queueService->createQueue(array('QueueName' => $queueId));
        return $result->get('QueueUrl');
    }


    /**
     * Format: nsfo_<env>_<slot>_<animalType>_<queue-type>_<error>
     *
     * animalType = sheep/goat
     * env = dev/stage/prod
     * slot = 1/2/3/4/etc.   <= is necessary to differentiate different development or staging environments
     * queue-type = ext/ext-raw/int/int-raw/feedback/mixblup-input/mixblup-output/etc.
     *
     * @param string $animalEnvType
     * @param string $environment
     * @param int $slot
     * @return string
     */
    protected function parseQueueId(string $animalEnvType, string $environment, int $slot): string
    {
        EnvUtil::validateAnimalTypeEnv($animalEnvType);
        EnvUtil::validateEnvironment($environment);
        return sprintf('nsfo_%s_%s_%s_%s', $environment, $slot, $animalEnvType, self::QUEUE_TYPE);
    }


    /**
     * @param string $queueId
     * @return string
     */
    protected function parseErrorQueueId($queueId): string
    {
        return $queueId.'_error';
    }


    /**
     * Send a request message to Queue.
     *
     * @param string $requestId
     * @param string $messageBody
     * @param string $requestType
     * @return array|null
     */
    public function send($messageBody, $requestType, $requestId)
    {
        return $this->sendBase($messageBody, $requestType, $requestId, $this->queueUrl);
    }


    /**
     * Send a request message to error Queue.
     *
     * @param string $requestId
     * @param string $messageBody
     * @param string $requestType
     * @return array|null
     */
    public function sendToErrorQueue($messageBody, $requestType, $requestId)
    {
        $this->errorQueueNullCheck();
        return $this->sendBase($messageBody, $requestType, $requestId, $this->errorQueueUrl);
    }


    /**
     * Send a request message to given Queue.
     *
     * @param string $messageBody
     * @param string $requestType
     * @param string $requestId
     * @param string $queueUrl
     * @return array|null
     */
    private function sendBase($messageBody, $requestType, $requestId, $queueUrl)
    {
        if($requestId == null && $messageBody == null){
            return null;
        }

        $message = $this->createMessage($queueUrl, $messageBody, $requestType);
        $response = $this->queueService->sendMessage($message);

        return $this->responseHandler($response);
    }


    /**
     * Send a request message to given Queue.
     *
     * @param string $requestId
     * @param string $messageBody
     * @param string $requestType
     * @return array|null
     */
    public function sendDeclareResponse($messageBody, $requestType, $requestId)
    {
        if($requestId == null && $messageBody == null){
            return null;
        }

        $message = $this->createMessage($this->queueUrl, $messageBody, $requestType, $requestId);
        $response = $this->queueService->sendMessage($message);

        return $this->responseHandler($response);
    }


    /**
     * @param string $queueUrl
     * @param string $messageBody
     * @param string $requestType
     * @param string $requestId
     * @return array
     */
    protected function createMessage($queueUrl, $messageBody, $requestType, $requestId = null)
    {
        $stringType = 'String';
        $messageAttributes = [
            'TaskType' =>
                [
                    'StringValue' => $requestType,
                    'DataType' => $stringType,
                ],
        ];

        if($requestId) {
            $messageAttributes['MessageId'] =
                [
                    'StringValue' => $requestId,
                    'DataType' => $stringType,
                ];
        }

        return [
            'QueueUrl' => $queueUrl,
            'MessageBody' => $messageBody,
            'MessageAttributes' => $messageAttributes
        ];
    }


    /**
     * @param $messageAttributeNames
     * @return \Aws\Result
     */
    public function getNextMessage(array $messageAttributeNames = ['All'])
    {
        return $this->getNextMessageBase($this->queueUrl, $messageAttributeNames);
    }


    /**
     * @param $messageAttributeNames
     * @return \Aws\Result
     */
    public function getNextErrorMessage(array $messageAttributeNames = ['All'])
    {
        $this->errorQueueNullCheck();
        return $this->getNextMessageBase($this->errorQueueUrl, $messageAttributeNames);
    }


    /**
     * @param string $queueUrl
     * @param $messageAttributeNames
     * @return \Aws\Result
     */
    private function getNextMessageBase($queueUrl, $messageAttributeNames)
    {
        return $this->queueService->receiveMessage([
            'QueueUrl' => $queueUrl,
            'MessageAttributeNames' => $messageAttributeNames,
        ]);
    }


    /**
     * @return string
     */
    public function getQueueService()
    {
        return $this->queueService;
    }


    /**
     * @param $response
     * @return array
     */
    protected function responseHandler($response) {
        $statusCode = $response['@metadata']['statusCode'];
        return array('statusCode' => $statusCode);
    }


    /**
     * @param string $receiptHandleOrAwsResult
     * @return \Aws\Result
     */
    public function deleteMessage($receiptHandleOrAwsResult)
    {
        return $this->deleteMessageBase($receiptHandleOrAwsResult, $this->queueUrl);
    }


    /**
     * @param string $receiptHandleOrAwsResult
     * @return \Aws\Result
     */
    public function deleteErrorMessage($receiptHandleOrAwsResult)
    {
        $this->errorQueueNullCheck();
        return $this->deleteMessageBase($receiptHandleOrAwsResult, $this->errorQueueUrl);
    }


    /**
     * @param $receiptHandleOrAwsResult
     * @param string $queueUrl
     * @return \Aws\Result
     */
    private function deleteMessageBase($receiptHandleOrAwsResult, $queueUrl)
    {
        if($receiptHandleOrAwsResult instanceof \Aws\Result) {
            $receiptHandleOrAwsResult = $receiptHandleOrAwsResult['Messages'][0]['ReceiptHandle'];
        }

        return $this->queueService->deleteMessage([
            'QueueUrl' => $queueUrl, // REQUIRED
            'ReceiptHandle' => $receiptHandleOrAwsResult, // REQUIRED
        ]);
    }


    /**
     * WARNING!
     * If this function is used in a while loop to continuously poll for messages in a queue
     * at least include a wait of 1 second within the loop.
     * Otherwise you will get a huge bill from Amazon.
     *
     * @return int
     */
    public function getSizeOfQueue()
    {
        return $this->getSizeOfQueueBase($this->queueUrl);
    }


    /**
     * WARNING!
     * If this function is used in a while loop to continuously poll for messages in a queue
     * at least include a wait of 1 second within the loop.
     * Otherwise you will get a huge bill from Amazon.
     *
     * @return int
     */
    public function getSizeOfErrorQueue()
    {
        $this->errorQueueNullCheck();
        return $this->getSizeOfQueueBase($this->errorQueueUrl);
    }


    /**
     * WARNING!
     * If this function is used in a while loop to continuously poll for messages in a queue
     * at least include a wait of 1 second within the loop.
     * Otherwise you will get a huge bill from Amazon.
     *
     * @param string $queueUrl
     * @return int
     */
    private function getSizeOfQueueBase($queueUrl)
    {
        /** @var AbstractModel $result */
        $result = $this->queueService->getQueueAttributes([
            'QueueUrl' => $queueUrl,
            'AttributeNames' => [
                AwsSqs::APPROXIMATE_MESSAGE_NAMESPACE
            ],
        ]);

        $attributes = ArrayUtil::get(AwsSqs::ATTRIBUTES, $result->toArray());
        if(is_array($attributes)) {
            $approxNumberOfMessages = ArrayUtil::get(AwsSqs::APPROXIMATE_MESSAGE_NAMESPACE, $attributes);
            return intval($approxNumberOfMessages);
        }

        return 0;
    }


    /**
     * @param \Aws\Result $response
     * @param bool $decodeJsonString
     * @return mixed
     */
    public static function getMessageBodyFromResponse($response, bool $decodeJsonString = true)
    {
        $jsonBody = self::getResponseValue($response, 'Body');
        if(is_string($jsonBody)) {
            return $decodeJsonString ? json_decode($jsonBody) : $jsonBody;
        }
        return null;
    }


    /**
     * @param $response
     * @return array
     */
    public static function getMessageAttributes($response)
    {
        $results = [];

        $messageAttributeResults = self::getResponseValue($response, 'MessageAttributes');

        if (is_array($messageAttributeResults)) {

            foreach ($messageAttributeResults as $key => $data)
            {
                $stringValue = $data['StringValue'];
                /*
                 * Get dataType: $data['DataType']
                 * Possible dataTypes: String, Number, Binary
                 */

                $results[$key] = strval($stringValue);
            }

        }

        return $results;
    }


    /**
     * @param \Aws\Result $messageResponse
     * @return null|string
     */
    public static function getTaskType($messageResponse): ?string
    {
        $messageAttributes = AwsQueueServiceBase::getMessageAttributes($messageResponse);
        return is_array($messageAttributes) ?
            ArrayUtil::get(self::TASK_TYPE, $messageAttributes,null) :
            null;
    }


    /**
     * @param \Aws\Result $response
     * @return mixed
     */
    public static function getResponseValue($response, $key)
    {
        return $response['Messages'][0][$key];
    }


    /**
     * @return string
     */
    public function getQueueId()
    {
        return $this->queueId;
    }

    /**
     * @return string
     */
    public function getErrorQueueId()
    {
        return $this->errorQueueId;
    }


    /**
     * @param \Aws\Result $receiptHandleOrAwsResult
     * @return bool
     */
    public static function hasMessage($receiptHandleOrAwsResult)
    {
        return $receiptHandleOrAwsResult['Messages'] !== null;
    }


    /**
     * @param array $messageAttributeNames
     * @return bool
     */
    public function moveErrorQueueMessagesToPrimaryQueue(array $messageAttributeNames = [self::TASK_TYPE, self::MESSAGE_ID])
    {
        $this->errorQueueNullCheck();
        $queueSize = $this->getSizeOfErrorQueue();

        if ($queueSize === 0) {
            //Queue is empty
            return true;
        }

        /**
         * To prevent an infinite loop, only process the amount of error messages equal to the error queue size.
         */
        for($i = 0; $i <= $queueSize; $i++)
        {
            $messageResponse = $this->getNextErrorMessage($messageAttributeNames);

            if (!self::hasMessage($messageResponse)) {
                //No message found
                return true;
            }

            $messageAttributes = AwsQueueServiceBase::getMessageAttributes($messageResponse);

            $taskType = null;
            $messageId = null;
            if (is_array($messageAttributes)) {
                $taskType =  ArrayUtil::get(self::TASK_TYPE, $messageAttributes,null);
                $messageId = ArrayUtil::get(self::MESSAGE_ID, $messageAttributes,null);
            }

            $response = $this->send(
                AwsQueueServiceBase::getResponseValue($messageResponse,'Body'),
                $taskType,
                $messageId
            );

            if (self::isMessageSuccessFullySent($response)) {
                $this->deleteErrorMessage($messageResponse);
            }
        }

        return $this->getSizeOfErrorQueue() === 0;
    }


    /**
     * @param $response
     * @return bool
     */
    public static function isMessageSuccessFullySent($response) {
        return ArrayUtil::get('statusCode', $response) === 200;
    }


    /**
     * @throws \Exception
     */
    private function errorQueueNullCheck()
    {
        if($this->errorQueueUrl === null) {
            throw new \Exception('Error Queue use not initialized', 428);
        }
    }


}
