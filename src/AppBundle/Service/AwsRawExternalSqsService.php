<?php

namespace AppBundle\Service;


use AppBundle\Enumerator\AwsQueueType;

/**
 * Class AwsRawExternalQueueService
 * @package AppBundle\Service
 */
class AwsRawExternalSqsService extends AwsSqsServiceBase
{
    const QUEUE_TYPE = AwsQueueType::EXTERNAL_RAW;
}