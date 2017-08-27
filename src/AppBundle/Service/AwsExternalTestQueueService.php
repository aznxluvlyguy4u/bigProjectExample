<?php

namespace AppBundle\Service;

use AppBundle\Constant\Environment;


/**
 * Class AwsExternalTestQueueService
 * @package AppBundle\Service
 */
class AwsExternalTestQueueService extends AwsQueueServiceBase
{
    /**
     * @return int
     * @throws \Exception
     */
    public function purgeQueue()
    {
        if($this->selectedEnvironment !== Environment::TEST) {
            throw new \Exception('Purging the Queue is only allowed for the test queue', 401);
        }

        $queueSize = $this->getSizeOfQueue();
        if($queueSize > 0) {
            $this->queueService->purgeQueue([
                'QueueUrl' => $this->queueUrl, // REQUIRED
            ]);
        }
        return $queueSize;
    }
}