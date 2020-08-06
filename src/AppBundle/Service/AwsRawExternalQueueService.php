<?php

namespace AppBundle\Service;


use AppBundle\Enumerator\AwsQueueType;

/**
 * Class AwsRawExternalQueueService
 * @package AppBundle\Service
 */
class AwsRawExternalQueueService extends AwsQueueServiceBaseV2
{
    const QUEUE_TYPE = AwsQueueType::EXTERNAL_RAW;
}