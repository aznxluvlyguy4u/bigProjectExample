<?php


namespace AppBundle\Service\Aws;


use Aws\Credentials\Credentials;
use Aws\Sqs\SqsClient;

class AwsSqsService
{
    /** @var string */
    private $region;
    /** @var string */
    private $version;
    /** @var string */
    private $accessKeyId;
    /** @var string */
    private $secretKey;
    /** @var Credentials */
    private $awsCredentials;

    /** @var SqsClient */
    private $sqlClient;

    /**
     * AwsSqsService
     * @param string $accessKeyId
     * @param string $secretKey
     * @param string $region of the Queue
     * @param string $version
     * @param boolean|null $deactivateSsl
     */
    public function __construct($accessKeyId, $secretKey, $region, $version,
                                $deactivateSsl = false
    )
    {
        $this->accessKeyId = $accessKeyId;
        $this->secretKey = $secretKey;

        $this->awsCredentials =  new Credentials($this->accessKeyId, $this->secretKey);
        $this->region = $region;
        $this->version = $version;

        $sqsConfig = [
            'region'  => $this->region,
            'version' => $this->version,
            'credentials' => $this->awsCredentials
        ];

        if ($deactivateSsl) {
            $sqsConfig['http'] = [ 'verify' => false ];
        }

        $this->sqlClient = new SqsClient($sqsConfig);
    }

    /**
     * @return SqsClient
     */
    public function getSqlClient(): SqsClient
    {
        return $this->sqlClient;
    }

}