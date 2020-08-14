<?php

namespace AppBundle\Command;

use AppBundle\Service\AwsInternalQueueService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoQueueInternalErrorCommand extends AwsSqsCommandBase
{
    const TITLE = 'NSFO PROCESS (JAVA) INTERNAL QUEUE ERRORS';
    const MAX_ERROR_QUEUE_BATCH_RETRIES = 3;

    protected function configure()
    {
        $this
            ->setName('nsfo:queue:internal:error')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->moveMessagesFromErrorQueueToPrimaryQueue(
            $this->getContainer()->get(AwsInternalQueueService::class),
            $output,
            true
        );
    }
}
