<?php

namespace AppBundle\Command;

use AppBundle\Processor\Rvo\RawInternalWorker;
use AppBundle\Service\AwsRawInternalSqsService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoQueueRawInternalCommand extends AwsSqsCommandBase
{
    const TITLE = 'NSFO PROCESS RAW (PHP) INTERNAL QUEUE';
    const MAX_ERROR_QUEUE_BATCH_RETRIES = 3;

    protected function configure()
    {
        $this
            ->setName('nsfo:queue:internal:raw')
            ->setDescription(self::TITLE)
            ->addArgument('processErrorQueue', InputArgument::OPTIONAL,
                'Move all queue messages from error queue to primary queue')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getArgument('processErrorQueue') === 'error') {

            $this->moveMessagesFromErrorQueueToPrimaryQueue(
                $this->getContainer()->get(AwsRawInternalSqsService::class),
                $output,
                true
            );

        } else {
            $this->getContainer()->get(RawInternalWorker::class)->run();
        }
    }
}
