<?php

namespace AppBundle\Service;
use AppBundle\Enumerator\MixBlupMessageType;


/**
 * Class MixBlupInputQueueService
 * @package AppBundle\Service
 */
class MixBlupInputQueueService extends AwsQueueServiceBase
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
     * @return array|null
     */
    public function send($jsonMessageBody)
    {
        return parent::send(1, $jsonMessageBody, MixBlupMessageType::MIXBLUP_INPUT_FILES);
    }
}