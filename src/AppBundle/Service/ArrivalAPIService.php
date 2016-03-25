<?php

namespace AppBundle\Service;

use Aws\Sqs\SqsClient;

/**
 * Class ArrivalAPIService
 * @package AppBundle\Service
 */
class ArrivalAPIService
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
  private $secrectKey;

  /**
   * @var SqsClient
   */
  private $queueService;

  /**
   * @var string
   */
  protected $queueURL;

  /**
   * ArrivalAPIService constructor.
   *
   * @param array $credentials
   * @param $region
   * @param $version
   */
  public function __construct($credentials = array(), $region, $version)
  {
    $this->accessKeyId = $credentials[0];
    $this->secretKey = $credentials[1];
    $this->region = $region;
    $this->version = $version;

    $this->queueService = new SqsClient([
      'region'  => $this->region,
      'version' => $this->version,
      'credentials' => [
        'key'    => $this->accessKeyId,
        'secret' => $this->secrectKey
      ]
    ]);

    //TODO
    $this->queueURL = '';
  }

  /**
   *
   * @param $messageBody
   */
  public function send($messageBody)
  {
    //TODO
    $messageBod = 'an awesome message.';

    $this->queueService->sendMessage(array(
      'QueueUrl'    => $this->queueUrl,
      'MessageBody' => $messageBody,
    ));

  }

  /**
   * @return string
   */
  public function getQueueService()
  {
    return $this->queueService;
  }

}