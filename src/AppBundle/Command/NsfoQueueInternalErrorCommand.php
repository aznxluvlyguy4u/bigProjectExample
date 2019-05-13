<?php

namespace AppBundle\Command;

use AppBundle\Service\AwsInternalQueueService;
use AppBundle\Util\DatabaseDataFixer;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoQueueInternalErrorCommand extends ContainerAwareCommand
{
    const TITLE = 'NSFO PROCESS (JAVA) INTERNAL QUEUE ERRORS';
    const MAX_BATCH_RETRIES = 3;

    /** @var AwsInternalQueueService */
    private $queueService;

    protected function configure()
    {
        $this
            ->setName('nsfo:queue:internal:error')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->queueService = $this->getContainer()->get(AwsInternalQueueService::class);

        if (empty($this->getSizeOfErrorQueue($output))) {
            return;
        }

        if (!empty($this->queueService->getSizeOfQueue())) {
            $output->writeln('INTERNAL primary queue is not empty yet, so skip processing error queue');
            return;
        }

        $this->updateDatabaseSequences($output);

        for($i = 0; $i <= self::MAX_BATCH_RETRIES; $i++) {
            $isErrorQueueEmptyAfterProcess = $this->queueService->moveErrorQueueMessagesToPrimaryQueue();
            if ($isErrorQueueEmptyAfterProcess) {
                $output->writeln('All INTERNAL error queue messages are moved to the primary queue');
                return;
            }
        }

        $output->writeln('INTERNAL error queue is not empty yet');
    }

    private function getSizeOfErrorQueue(OutputInterface $output) {
        $errorQueueCount = $this->queueService->getSizeOfErrorQueue();
        $output->writeln('INTERNAL error queue '.$this->queueService->getErrorQueueId().' ' .
            (empty($errorQueueCount) ? 'is empty' : 'count: ' . $errorQueueCount)
        );
        return $errorQueueCount;
    }

    private function updateDatabaseSequences(OutputInterface $output) {
        $em = $this->getContainer()->get('doctrine')->getManager();
        DatabaseDataFixer::updateMaxIdOfAllSequences($em->getConnection(), null);
        $output->writeln("All database sequences are updated");
    }
}
