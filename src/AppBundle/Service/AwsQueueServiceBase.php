<?php

namespace AppBundle\Service;

use AppBundle\Constant\AwsSqs;
use AppBundle\Constant\Environment;
use AppBundle\Enumerator\QueueSuffix;
use AppBundle\Util\ArrayUtil;
use Aws\Api\AbstractModel;
use Aws\Sqs\SqsClient;
use Aws\Credentials\Credentials;

/**
 * Class AWSQueueServiceBase
 * @package AppBundle\Service
 */
abstract class AwsQueueServiceBase
{
    const TaskType = 'TaskType';
    const MessageId = 'MessageId';

    /** @var string */
    protected $region;
    /** @var string */
    protected $version;
    /** @var string */
    protected $accessKeyId;
    /** @var string */
    protected $secretKey;
    /** @var SqsClient */
    protected $queueService;
    /** @var string */
    protected $queueUrl;
    /** @var string */
    protected $errorQueueUrl;
    /** @var Credentials */
    protected $awsCredentials;
    /** @var string */
    protected $queueId;
    /** @var string */
    protected $errorQueueId;
    /** @var string */
    protected $selectedEnvironment;

    /**
     * AWSQueueServiceBase constructor, initialize SQS config
     * @param string $queueIdPrefix
     * @param string $accessKeyId
     * @param string $secretKey
     * @param string $region of the Queue
     * @param string $version
     * @param string $selectedEnvironment
     * @param string $currentEnvironment
     * @param boolean $useErrorQueue
     */
    public function __construct($queueIdPrefix, $accessKeyId, $secretKey, $region, $version,
                                $selectedEnvironment, $currentEnvironment, $useErrorQueue = true)
    {
        $this->accessKeyId = $accessKeyId;
        $this->secretKey = $secretKey;

        $this->awsCredentials =  new Credentials($this->accessKeyId, $this->secretKey);
        $this->region = $region;
        $this->version = $version;

        if ($currentEnvironment === Environment::TEST) { $selectedEnvironment = $currentEnvironment; }
        $this->selectedEnvironment = $selectedEnvironment;
        $this->queueId = $this->selectQueueIdByEnvironment($queueIdPrefix, $this->selectedEnvironment);

        $sqsConfig = array(
            'region'  => $this->region,
            'version' => $this->version,
            'credentials' => $this->awsCredentials
        );

        $sqsClient = new SqsClient($sqsConfig);
        $this->queueService = $sqsClient;

        $this->queueUrl = $this->createQueueUrl($this->queueId);

        if ($useErrorQueue) {
            $this->errorQueueId = $this->createErrorQueueIdByQueueId($this->queueId);
            $this->errorQueueUrl = $this->createQueueUrl($this->errorQueueId);
        }
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
     * Set queueId based on environment.
     *
     * @param string $queueIdPrefix
     * @param $currentEnvironment
     * @return mixed
     */
    protected function selectQueueIdByEnvironment($queueIdPrefix, $currentEnvironment)
    {
        switch($currentEnvironment) {
            case Environment::PROD:     return $queueIdPrefix.QueueSuffix::PROD;
            case Environment::STAGE:    return $queueIdPrefix.QueueSuffix::STAGE;
            case Environment::DEV:      return $queueIdPrefix.QueueSuffix::DEV;
            case Environment::TEST:     return $queueIdPrefix.QueueSuffix::TEST;
            case Environment::LOCAL:    return $queueIdPrefix.QueueSuffix::LOCAL;
            default;                    return $queueIdPrefix.QueueSuffix::DEV;
        }
    }


    /**
     * @param string $queueId
     * @return string
     */
    protected function createErrorQueueIdByQueueId($queueId)
    {
        $parts = explode('_', $queueId);
        return $parts[0].'_error_'.implode('_',array_splice($parts,1));
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
    public function getNextMessage(array $messageAttributeNames = [])
    {
        return $this->getNextMessageBase($this->queueUrl, $messageAttributeNames);
    }


    /**
     * @param $messageAttributeNames
     * @return \Aws\Result
     */
    public function getNextErrorMessage(array $messageAttributeNames = [])
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
    protected function responseHandler($response){
        $statusCode = $response['@metadata']['statusCode'];
        $result = array('statusCode' => $statusCode);
        return $result;
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
     * @return mixed
     */
    public static function getMessageBodyFromResponse($response)
    {
        $jsonBody = self::getResponseValue($response, 'Body');
        if(is_string($jsonBody)) {
            return json_decode($jsonBody);
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
                $dataType = $data['DataType'];

                switch ($dataType)
                {
                    case 'String': $results[$key] = strval($stringValue); break;
                    case 'Number': $results[$key] = strval($stringValue); break;
                    case 'Binary': $results[$key] = strval($stringValue); break;
                    default: $results[$key] = strval($stringValue); break;
                }
            }

        }

        return $results;
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
    public function moveErrorQueueMessagesToPrimaryQueue(array $messageAttributeNames = [self::TaskType, self::MessageId])
    {
        $this->errorQueueNullCheck();
        $queueSize = $this->getSizeOfErrorQueue();

        if ($queueSize === 0) {
            //Queue is empty
            return true;
        }

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
                $taskType =  ArrayUtil::get(self::TaskType, $messageAttributes,null);
                $messageId = ArrayUtil::get(self::MessageId, $messageAttributes,null);
            }

            $response = $this->send(
                AwsQueueServiceBase::getResponseValue($messageResponse,'Body'),
                $taskType,
                $messageId
            );

            if (ArrayUtil::get('statusCode', $response) === 200) {
                $this->deleteErrorMessage($messageResponse);
            }
        }

        return $this->getSizeOfErrorQueue() === 0;
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