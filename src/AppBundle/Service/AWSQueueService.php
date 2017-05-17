<?php

namespace AppBundle\Service;

use AppBundle\Constant\Environment;
use Aws\Sqs\SqsClient;
use  Aws\Credentials\Credentials;

/**
 * Class AWSQueueService
 * @package AppBundle\Service
 */
class AWSQueueService
{
  /**
   * @var string
   */
  protected $region;

  /**
   * @var string
   */
  private $version;

  /**
   * @var string
   */
  private $accessKeyId;

  /**
   * @var string
   */
  private $secretKey;

  /**
   * @var SqsClient
   */
  private $externalQueueService;

  /**
   * @var SqsClient
   */
  private $internalQueueService;

  /**
   * @var string
   */
  protected $externalQueueURL;

  /**
   * @var string
   */
  protected $internalQueueURL;

  /**
   * @var Credentials
   */
  private $awsCredentials;

  /**
   * @var array
   */
  private $queueIds;

  /** @var string */
  private $externalQueueId;

  /** @var string */
  private $internalQueueId;

  /**
   * ArrivalAPIService constructor, initialize SQS config
   *
   * @param $credentials array containing AWS accessKey and secretKey.
   * @param $region of the Queue.
   * @param $version API version to use.
   */
  public function __construct($credentials = array(), $region, $version, $queueIds = array(), $currentEnvironment = null)
  {
    $this->accessKeyId = $credentials[0];
    $this->secretKey = $credentials[1];

    $this->awsCredentials =  new Credentials($this->accessKeyId, $this->secretKey);
    $this->region = $region;
    $this->version = $version;
    $this->queueIds = $queueIds;

    /**
     * Get current environment, set queueId based on environment.
     *
     * 0 = (prod)uction
     * 1 = (stage)ing
     * 2 = (dev)elopment
     * 3 = test
     * 4 = local
     */
    switch($currentEnvironment) {
      case Environment::PROD:
        $this->externalQueueId = $queueIds[0];
        $this->internalQueueId = str_replace("ext","int", $queueIds[0]);
        break;
      case Environment::STAGE:
        $this->externalQueueId  = $queueIds[1];
        $this->internalQueueId = str_replace("ext","int", $queueIds[1]);
        break;
      case Environment::DEV:
        $this->externalQueueId  = $queueIds[2];
        $this->internalQueueId = str_replace("ext","int", $queueIds[2]);
        break;
      case Environment::TEST:
        $this->externalQueueId = $queueIds[3];
        $this->internalQueueId = str_replace("ext","int", $queueIds[3]);
        break;
      case Environment::LOCAL:
        $this->externalQueueId  = $queueIds[4];
        $this->internalQueueId = str_replace("ext","int", $queueIds[4]);
        break;
      default; //dev
        $this->externalQueueId = $queueIds[2];
        $this->internalQueueId = str_replace("ext","int", $queueIds[2]);
        break;
    }

    $sqsConfig = array(
      'region'  => $this->region,
      'version' => $this->version,
      'credentials' => $this->awsCredentials
    );

    $sqsClient = new SqsClient($sqsConfig);
    $this->externalQueueService = $sqsClient;
    $this->internalQueueService = $sqsClient;

    //Setup external Queue URL
    $result = $this->externalQueueService->createQueue(array('QueueName' => $this->externalQueueId));
    $this->externalQueueURL = $result->get('QueueUrl');

    //Setup internal Queue URL
    $result = $this->internalQueueService->createQueue(array('QueueName' => $this->internalQueueId));
    $this->internalQueueURL = $result->get('QueueUrl');
  }


  /**
   * Send a request message to given Queue.
   *
   * @param string $requestId
   * @param string $messageBody
   * @param string $requestType
   * @return array|null
   */
  public function sendToExternalQueue($requestId, $messageBody, $requestType)
  {
    if($requestId == null && $messageBody == null){
      return null;
    }

    $message = $this->createMessage($this->externalQueueURL, $messageBody, $requestType);
    $response = $this->externalQueueService->sendMessage($message);

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
  public function sendToInternalQueue($requestId, $messageBody, $requestType)
  {
    if($requestId == null && $messageBody == null){
      return null;
    }

    $message = $this->createMessage($this->internalQueueURL, $messageBody, $requestType);
    $response = $this->internalQueueService->sendMessage($message);

    return $this->responseHandler($response);
  }


  /**
   * @param string $queueUrl
   * @param string $messageBody
   * @param string $requestType
   * @param string $dataType
   * @return array
   */
  private function createMessage($queueUrl, $messageBody, $requestType, $dataType = 'String')
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
    $result = $this->externalQueueService->receiveMessage(array(
      'QueueUrl' => $this->externalQueueURL
    ));

    return $result;
  }

  /**
   * @return string
   */
  public function getExternalQueueService()
  {
    return $this->externalQueueService;
  }

  /**
   * @param $response
   * @return array
   */
  private function responseHandler($response){
    $statusCode = $response['@metadata']['statusCode'];
    $result = array('statusCode' => $statusCode);
    return $result;
  }


}