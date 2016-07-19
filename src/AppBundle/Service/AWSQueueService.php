<?php

namespace AppBundle\Service;

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
  private $queueService;

  /**
   * @var string
   */
  protected $queueURL;

  /**
   * @var Credentials
   */
  private $awsCredentials;

  /**
   * @var array
   */
  private $queueIds;

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
    $currentEnvironment = 'stage';
    switch($currentEnvironment) {
      case 'prod':
        $queueId = $queueIds[1]; // set 0 for deployment to production, set 1 for deployement to staging!
        break;
      case 'stage':
        $queueId = $queueIds[1];
        break;
      case 'dev':
        $queueId = $queueIds[2];
        break;
      case 'test':
        $queueId = $queueIds[3];
        break;
      case 'local':
        $queueId = $queueIds[4];
        break;
      default; //dev
        $queueId = $queueIds[1];
        break;
    }

    $sqsConfig = array(
      'region'  => $this->region,
      'version' => $this->version,
      'credentials' => $this->awsCredentials
    );

    $this->queueService = new SqsClient($sqsConfig);
    
    $result = $this->queueService->createQueue(array('QueueName' => $queueId));
    $this->queueURL = $result->get('QueueUrl');
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

    $response = $this->queueService->sendMessage(array (
      'QueueUrl' => $this->queueURL,
      'MessageBody' => $messageBody,
      'MessageAttributes' => [
        'TaskType' => [
          'StringValue' => $requestType,
          'DataType' => 'String',
        ],
      ],
    ));

    return $this->responseHandler($response);
  }

  /**
   * @return \Aws\Result
   */
  public function getNextMessage()
  {
    $result = $this->queueService->receiveMessage(array(
      'QueueUrl' => $this->queueURL
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
  private function responseHandler($response){
    $statusCode = $response['@metadata']['statusCode'];
    $result = array('statusCode' => $statusCode);
    return $result;
  }
}