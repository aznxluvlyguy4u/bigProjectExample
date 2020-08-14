<?php

namespace AppBundle\Command;

use AppBundle\Processor\Rvo\RawExternalWorker;
use AppBundle\Service\AwsRawExternalSqsService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoQueueRawExternalCommand extends AwsSqsCommandBase
{
    const TITLE = 'NSFO PROCESS RAW (PHP) EXTERNAL QUEUE';
    const MAX_ERROR_QUEUE_BATCH_RETRIES = 3;

    protected function configure()
    {
        $this
            ->setName('nsfo:queue:external:raw')
            ->setDescription(self::TITLE)
            ->addArgument('processErrorQueue', InputArgument::OPTIONAL,
                'Move all queue messages from error queue to primary queue')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getArgument('processErrorQueue') === 'error') {

            $this->moveMessagesFromErrorQueueToPrimaryQueue(
                $this->getContainer()->get(AwsRawExternalSqsService::class),
                $output,
                false
            );

        } else {
            $this->getContainer()->get(RawExternalWorker::class)->run();
        }
    }
}
