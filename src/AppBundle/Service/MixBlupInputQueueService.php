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
     * @param string $jsonMessageBody
     * @param string $messageType
     * @param int $requestId
     * @return array|null
     */
    public function send($jsonMessageBody, $messageType = MixBlupMessageType::MIXBLUP_INPUT_FILES, $requestId = 1)
    {
        return parent::send($jsonMessageBody, $messageType, $requestId);
    }
}