<?php

namespace AppBundle\Service;

use AppBundle\Constant\Environment;
use AppBundle\Enumerator\QueueSuffix;
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
     * @param array $credentials containing AWS accessKey and secretKey.
     * @param string $region of the Queue
     * @param string $version
     * @param string $currentEnvironment
     */
    public function __construct($queueIdPrefix, $credentials = array(), $region, $version, $currentEnvironment)
    {
        $this->accessKeyId = $credentials[0];
        $this->secretKey = $credentials[1];

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
    public function send($requestId, $messageBody, $requestType)
    {
        if($requestId == null && $messageBody == null){
            return null;
        }

        $message = $this->createMessage($this->queueUrl, $messageBody, $requestType);
        $response = $this->queueService->sendMessage($message);

        return $this->responseHandler($response);
    }


    /**
     * @param string $queueUrl
     * @param string $messageBody
     * @param string $requestType
     * @param string $dataType
     * @return array
     */
    protected function createMessage($queueUrl, $messageBody, $requestType, $dataType = 'String')
    {
        return [
            'QueueUrl' => $queueUrl,
            'MessageBody' => $messageBody,
            'MessageAttributes' =>
                [
                    'TaskType' =>
                        [
                            'StringValue' => $requestType,
                            'DataType' => $dataType,
                        ],
                ],
        ];
    }


    /**
     * @return \Aws\Result
     */
    public function getNextExternalQueueMessage()
    {
        $result = $this->queueService->receiveMessage(array(
            'QueueUrl' => $this->queueUrl
        ));

        return $result;
    }


    /**
     * @return string
     */
    public function getExternalQueueService()
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
     * @param string $ReceiptHandle
     * @return \Aws\Result
     */
    public function deleteMessage($ReceiptHandle){
        return $this->queueService->deleteMessage([
            'QueueUrl' => $this->queueUrl, // REQUIRED
            'ReceiptHandle' => $ReceiptHandle, // REQUIRED
        ]);
    }
}