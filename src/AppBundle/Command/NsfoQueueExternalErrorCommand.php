<?php

namespace AppBundle\Command;

use AppBundle\Service\AwsExternalQueueService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoQueueExternalErrorCommand extends AwsSqsCommandBase
{
    const TITLE = 'NSFO PROCESS (JAVA) EXTERNAL QUEUE ERRORS';
    const MAX_ERROR_QUEUE_BATCH_RETRIES = 3;

    protected function configure()
    {
        $this
            ->setName('nsfo:queue:external:error')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->moveMessagesFromErrorQueueToPrimaryQueue(
            $this->getContainer()->get(AwsExternalQueueService::class),
            $output,
            false
        );
    }
}
