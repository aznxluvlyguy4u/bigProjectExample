<?php

namespace AppBundle\Service;


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


}