<?php

namespace AppBundle\Command;

use AppBundle\Service\AwsExternalQueueService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoQueueExternalErrorCommand extends ContainerAwareCommand
{
    const TITLE = 'NSFO PROCESS (JAVA) EXTERNAL QUEUE ERRORS';
    const MAX_BATCH_RETRIES = 3;

    /** @var AwsExternalQueueService */
    private $queueService;

    protected function configure()
    {
        $this
            ->setName('nsfo:queue:external:error')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->queueService = $this->getContainer()->get(AwsExternalQueueService::class);

        if (empty($this->getSizeOfErrorQueue($output))) {
            return;
        }

        for($i = 0; $i <= self::MAX_BATCH_RETRIES; $i++) {
            $isErrorQueueEmptyAfterProcess = $this->queueService->moveErrorQueueMessagesToPrimaryQueue();
            if ($isErrorQueueEmptyAfterProcess) {
                $output->writeln('All EXTERNAL error queue messages are moved to the primary queue');
                return;
            }
        }

        $output->writeln('EXTERNAL error queue is not empty yet');
    }

    private function getSizeOfErrorQueue(OutputInterface $output) {
        $errorQueueCount = $this->queueService->getSizeOfErrorQueue();
        $output->writeln('EXTERNAL error queue '.$this->queueService->getErrorQueueId().' ' .
            (empty($errorQueueCount) ? 'is empty' : 'count: ' . $errorQueueCount)
        );
        return $errorQueueCount;
    }
}
