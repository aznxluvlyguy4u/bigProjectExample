<?php

namespace AppBundle\Service;
use AppBundle\Enumerator\MixBlupMessageType;


/**
 * Class MixBlupOutputQueueService
 * @package AppBundle\Service
 */
class MixBlupOutputQueueService extends AwsQueueServiceBase
{
    /**
     * AwsExternalQueueService constructor.
     * @param string $queueIdPrefix
     * @param array $credentials containing AWS accessKey and secretKey.
     * @param string $region of the Queue
     * @param string $version
     * @param string $currentEnvironment
     */
    public function __construct($queueIdPrefix, $credentials = array(), $region, $version, $currentEnvironment = null)
    {
        parent::__construct($queueIdPrefix, $credentials, $region, $version, $currentEnvironment);
    }


    /**
     * @param string $jsonMessageBody
     * @param string $messageType
     * @param int $requestId
     * @return array|null
     */
    public function send($jsonMessageBody, $messageType = MixBlupMessageType::MIXBLUP_OUTPUT_FILES, $requestId = 1)
    {
        return parent::send($jsonMessageBody, $messageType, $requestId);
    }
}