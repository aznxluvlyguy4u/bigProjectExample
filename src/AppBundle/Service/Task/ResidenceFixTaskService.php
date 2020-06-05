<?php


namespace AppBundle\Service\Task;


use AppBundle\Entity\ProcessStatus;
use AppBundle\Entity\TaskResidenceFix;
use AppBundle\Entity\TaskResidenceFixRepository;
use AppBundle\Exception\InternalServerErrorException;
use AppBundle\model\process\ProcessDetails;
use AppBundle\Service\DataFix\UbnHistoryFixer;
use AppBundle\Util\SleepUtil;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ResidenceFixTaskService extends TaskServiceBase implements TaskServiceInterface
{
    const LOOP_MAX_DURATION_IN_HOURS = 3;
    const QUEUE_EMPTY_TIMEOUT_SECONDS = 5;

    /** @var UbnHistoryFixer */
    private $ubnHistoryFixer;

    /**
     * @required
     *
     * @param UbnHistoryFixer $ubnHistoryFixer
     */
    public function setUbnHistoryFixer(UbnHistoryFixer $ubnHistoryFixer)
    {
        $this->ubnHistoryFixer = $ubnHistoryFixer;
    }

    private function taskRepository(): TaskResidenceFixRepository
    {
        return $this->em->getRepository(TaskResidenceFix::class);
    }

    private function getProcess(): ProcessStatus
    {
        return $this->processStatusRepository->getAnimalResidenceDailyMatchWithCurrentLivestockProcess();
    }

    public function start(bool $recalculate = false)
    {
        $process = $this->getProcess();

        if (!$process->isFinished()) {
            throw new BadRequestHttpException($this->translator->trans('process.duplicate'));
        }

        $locationIds = $this->ubnHistoryFixer->getLocationIdsWithUbnHistoryDiscrepancies();
        $startedAt = new \DateTime();

        $tasksAdded = $this->taskRepository()->add($locationIds, $startedAt);

        $process->reset($startedAt, $recalculate);
        $process->setTotal($tasksAdded);
        $this->em->persist($process);

        $this->logger->notice($tasksAdded.' location residence fix tasks added');

        $this->em->flush();
    }

    public function run(): bool
    {
        $process = $this->getProcess();

        if ($process->isLocked()) {
            $this->validateLockedDuration($process, self::LOOP_MAX_DURATION_IN_HOURS);
            SleepUtil::sleep(self::QUEUE_EMPTY_TIMEOUT_SECONDS);
            return true;
        }

        $fixedAnimalsCount = 0;

        try {
            $task = $this->taskRepository()->next();

            if (!$task) {

                if (
                    $process->getFinishedAt() == null ||
                    $process->getProgress() !== 100
                ) {
                    $now = new \DateTime();
                    $process->setFinishedAt($now);
                    $process->setBumpedAt($now);
                    $process->setProgress(100);
                    $process->setIsCancelled(false);
                    $process->setIsLocked(false);
                    $this->em->persist($process);
                    $this->em->flush();
                    $this->logger->notice("Process was finished");
                }
                SleepUtil::sleep(self::QUEUE_EMPTY_TIMEOUT_SECONDS);
                return true;
            }

            $taskId = $task->getId();
            $process->setIsLocked(true);
            $this->em->persist($process);
            $this->em->flush();
            $this->em->clear();

            $locationId = $task->getLocationId();

            $fixedAnimalsCount = $this->ubnHistoryFixer->updateCurrentAnimalResidenceRecordsByCurrentLivestock($locationId);
            $this->taskRepository()->deleteTask($taskId);

            $isLoopRunSuccessful = true;

        } catch (\Exception $exception) {
            $process = $this->getProcess();
            $process->setIsLocked(false);
            $process->setDebugErrorMessage($exception->getTraceAsString());
            $process->setErrorMessage($exception->getMessage());
            $process->setErrorCode($exception->getCode());
            $this->em->persist($process);
            $this->em->flush();

            throw new InternalServerErrorException($exception->getMessage(), $exception);
        }

        $addedUpdatedCount = $fixedAnimalsCount > 0 ? 1 : 0;
        $addedSkippedCount = 1 - $addedUpdatedCount;

        $processDetails = (new ProcessDetails())
            ->setTotal($process->getTotal())
            ->setProcessed($process->getProcessed() + 1)
            ->setNew(0)
            ->setUpdated($process->getProcessed() + $addedUpdatedCount)
            ->setSkipped($process->getSkippedCount() + $addedSkippedCount)
        ;

        $process = $this->getProcess();
        $process->setProcessDetails($processDetails);
        $process->setIsLocked(false);
        $this->em->persist($process);
        $this->em->flush();

        return $isLoopRunSuccessful;
    }

    public function cancel(): bool
    {
        $this->taskRepository()->purgeQueue();
        $process = $this->getProcess();
        return $this->cancelBase($process, true);
    }
}
