<?php

namespace AppBundle\Service;


use AppBundle\Enumerator\AwsQueueType;

/**
 * Class AwsRawInternalQueueService
 * @package AppBundle\Service
 */
class AwsRawInternalSqsService extends AwsSqsServiceBase
{
    const QUEUE_TYPE = AwsQueueType::INTERNAL_RAW;
}