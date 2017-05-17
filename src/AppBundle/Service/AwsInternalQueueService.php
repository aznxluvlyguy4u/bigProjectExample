<?php

namespace AppBundle\Service;


/**
 * Class AwsInternalQueueService
 * @package AppBundle\Service
 */
class AwsInternalQueueService extends AwsQueueServiceBase
{
    /**
     * AwsInternalQueueService constructor.
     * @param string $queueIdPrefix
     * @param array $credentials containing AWS accessKey and secretKey.
     * @param string $region of the Queue
     * @param string $version
     * @param string $currentEnvironment
     */
    public function __construct($queueIdPrefix,$credentials = array(), $region, $version, $currentEnvironment = null)
    {
        parent::__construct($queueIdPrefix,$credentials, $region, $version, $currentEnvironment);
    }
    

}