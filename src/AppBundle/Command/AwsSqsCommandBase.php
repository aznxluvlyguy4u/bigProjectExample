<?php


namespace AppBundle\Command;


use AppBundle\Service\QueueServiceInterface;
use AppBundle\Util\DatabaseDataFixer;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AwsSqsCommandBase extends ContainerAwareCommand
{
    const MAX_ERROR_QUEUE_BATCH_RETRIES = 3;

    protected function moveMessagesFromErrorQueueToPrimaryQueue(
        QueueServiceInterface $queueService,
        OutputInterface $output,
        bool $updateDatabaseSequencesIfErrorMessagesExist
    )
    {
        if (empty($this->getSizeOfErrorQueue($queueService, $output))) {
            return;
        }

        $queueId = $queueService->getQueueId();
        $errorQueueId = $queueService->getErrorQueueId();

        if (!empty($queueService->getSizeOfQueue())) {
            $output->writeln("$queueId (primary) queue is not empty yet, so skip processing $errorQueueId queue");
            return;
        }

        if ($updateDatabaseSequencesIfErrorMessagesExist) {
            $this->updateDatabaseSequences($output);
        }

        for($i = 0; $i <= static::MAX_ERROR_QUEUE_BATCH_RETRIES; $i++) {
            $isErrorQueueEmptyAfterProcess = $queueService->moveErrorQueueMessagesToPrimaryQueue();
            if ($isErrorQueueEmptyAfterProcess) {
                $output->writeln("All $errorQueueId queue messages are moved to the primary queue $queueId");
                return;
            }
        }

        $output->writeln($errorQueueId.' is not empty yet');
    }


    private function getSizeOfErrorQueue(QueueServiceInterface $queueService, OutputInterface $output) {
        $errorQueueCount = $queueService->getSizeOfErrorQueue();
        $output->writeln($queueService->getErrorQueueId().' ' .
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