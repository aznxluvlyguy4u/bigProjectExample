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