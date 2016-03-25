<?php

namespace AppBundle\Service;

require 'vendor/autoload.php';

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
  private $region;

  /**
   * @var string
   */
  private $version;

  /**
   * @var Array
   */
  private $credentials;

  /**
   * @var SqsClient
   */
  private $queueService;

  /**
   * @var \SqsClient
   */
  private $queueService;

  private $queueURL;

  public function __construct(array $credentials = Array(), $region, $version)
  {
    $this.credentials = $credentials;
    $this.version = $version;
    $this.region = $region;


    $this.queueService = new SqsClient([
      'region'  => $this.region,
      'version' => $this.version
      'credentials' => [
        'key'    => $credentials[0],
        'secret' => $credentials[1]
      ]
    ]);

    $this.queueURL = '';
  }


  public function send($messageBody)
  {

    $sqs->sendMessage(array(
      'QueueUrl'    => $this.queueUrl,
      'MessageBody' => 'an awesome message.',
    ));

  }

}