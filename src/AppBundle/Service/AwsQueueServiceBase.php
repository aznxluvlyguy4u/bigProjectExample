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
    /** @var Credentials */
    protected $awsCredentials;
    /** @var string */
    protected $queueId;

    /**
     * AWSQueueServiceBase constructor, initialize SQS config
     * @param string $queueIdPrefix
     * @param string $accessKeyId
     * @param string $secretKey
     * @param string $region of the Queue
     * @param string $version
     * @param string $currentEnvironment
     */
    public function __construct($queueIdPrefix, $accessKeyId, $secretKey, $region, $version, $currentEnvironment)
    {
        $this->accessKeyId = $accessKeyId;
        $this->secretKey = $secretKey;

        $this->awsCredentials =  new Credentials($this->accessKeyId, $this->secretKey);
        $this->region = $region;
        $this->version = $version;
        $this->queueId = $this->selectQueueIdByEnvironment($queueIdPrefix, $currentEnvironment);

        $sqsConfig = array(
            'region'  => $this->region,
            'version' => $this->version,
            'credentials' => $this->awsCredentials
        );

        $sqsClient = new SqsClient($sqsConfig);
        $this->queueService = $sqsClient;

        //Setup queue URL
        $result = $this->queueService->createQueue(array('QueueName' => $this->queueId));
        $this->queueUrl = $result->get('QueueUrl');
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
     * Send a request message to given Queue.
     *
     * @param string $requestId
     * @param string $messageBody
     * @param string $requestType
     * @return array|null
     */
    public function send($messageBody, $requestType, $requestId)
    {
        if($requestId == null && $messageBody == null){
            return null;
        }

        $message = $this->createMessage($this->queueUrl, $messageBody, $requestType);
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
     * @return \Aws\Result
     */
    public function getNextMessage()
    {
        $result = $this->queueService->receiveMessage(array(
            'QueueUrl' => $this->queueUrl
        ));

        return $result;
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
    public function deleteMessage($receiptHandleOrAwsResult){
        if($receiptHandleOrAwsResult instanceof \Aws\Result) {
            $receiptHandleOrAwsResult = $receiptHandleOrAwsResult['Messages'][0]['ReceiptHandle'];
        }

        return $this->queueService->deleteMessage([
            'QueueUrl' => $this->queueUrl, // REQUIRED
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
        /** @var AbstractModel $result */
        $result = $this->queueService->getQueueAttributes([
            'QueueUrl' => $this->queueUrl,
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
        $jsonBody = $response['Messages'][0]['Body'];
        if($jsonBody) {
            return json_decode($jsonBody);
        }
        return null;
    }

    /**
     * @return string
     */
    public function getQueueId()
    {
        return $this->queueId;
    }

}