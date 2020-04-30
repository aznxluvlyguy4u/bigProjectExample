<?php


namespace AppBundle\Service\InbreedingCoefficient;


use AppBundle\Entity\InbreedingCoefficientTaskSmall;
use AppBundle\Entity\InbreedingCoefficientTaskSmallRepository;
use AppBundle\Enumerator\InbreedingCoefficientProcessSlot;
use AppBundle\Exception\InternalServerErrorException;
use AppBundle\model\ParentIdsPair;
use AppBundle\Setting\InbreedingCoefficientSetting;
use AppBundle\Util\SleepUtil;
use Symfony\Component\HttpFoundation\Response;

class InbreedingCoefficientParentPairsUpdaterService extends InbreedingCoefficientUpdaterServiceBase
{
    private function taskRepository(): InbreedingCoefficientTaskSmallRepository
    {
        return $this->em->getRepository(InbreedingCoefficientTaskSmall::class);
    }

    /**
     * @param  array|ParentIdsPair[]  $parentIdsPairs
     * @param  bool  $recalculate
     */
    public function addPairs(array $parentIdsPairs, bool $recalculate = false)
    {
        foreach ($parentIdsPairs as $parentIdsPair) {
            $this->taskRepository()->add(
                $parentIdsPair->getRamId(), $parentIdsPair->getEweId(), $recalculate
            );
        }
        $this->em->flush();
    }

    public function addPair(ParentIdsPair $parentIdsPair, bool $recalculate = false)
    {
        $this->addPairBase($parentIdsPair, $recalculate);
        $this->em->flush();
    }

    private function addPairBase(ParentIdsPair $parentIdsPair, bool $recalculate = false)
    {
        $this->taskRepository()->add(
            $parentIdsPair->getRamId(), $parentIdsPair->getEweId(), $recalculate
        );
    }


    public function run(): bool
    {
        $process = $this->processRepository()->getSmallProcess();
        $isLoopRunSuccessful = true;

        if ($process->isLocked()) {
            $this->validateLockedDuration($process);
            SleepUtil::sleep(InbreedingCoefficientSetting::QUEUE_LOCKED_TIMEOUT_SECONDS_PARENT_PAIRS);
            return true;
        }


        try {
            $task = $this->taskRepository()->next();

            if (!$task) {
                SleepUtil::sleep(InbreedingCoefficientSetting::QUEUE_EMPTY_TIMEOUT_SECONDS_PARENT_PAIRS);
                return $isLoopRunSuccessful;
            }

            $taskId = $task->getId();
            $recalculate = $task->isRecalculate();
            $parentIdsPair = new ParentIdsPair($task->getRamId(), $task->getEweId());

            $process->setIsLocked(true);
            $this->em->persist($process);
            $this->em->flush();
            $this->em->clear();

            $isLoopRunSuccessful = $this->generateForParentIdsPairOuterLoop($parentIdsPair, $recalculate);

            $this->taskRepository()->deleteTask($taskId);

        } catch (\Exception $exception) {
            $process = $this->processRepository()->getSmallProcess();
            $process->setIsLocked(false);
            $process->setDebugErrorMessage($exception->getTraceAsString());
            $process->setErrorMessage($exception->getMessage());
            $process->setErrorCode($exception->getCode());
            $this->em->persist($process);
            $this->em->flush();

            throw new InternalServerErrorException($exception->getMessage(), $exception);
        }


        $process = $this->processRepository()->getSmallProcess();
        $process->setIsLocked(false);
        $this->em->persist($process);
        $this->em->flush();

        return $isLoopRunSuccessful;
    }

    public function cancel(): bool
    {
        $this->taskRepository()->purgeQueue();
        $process = $this->processRepository()->getSmallProcess();
        return $this->cancelBase($process);
    }


    private function generateForParentIdsPairOuterLoop(ParentIdsPair $parentIdsPair, bool $recalculate): bool
    {
        $this->setProcessSlot(InbreedingCoefficientProcessSlot::SMALL);

        $process = $this->processRepository()->getSmallProcess();
        $this->resetCounts($process);
        $this->em->clear();

        $ramId = $parentIdsPair->getRamId();
        $eweId = $parentIdsPair->getEweId();
        $this->logMessageGroup = "ramId: $ramId,eweId: $eweId";

        try {
            $groupedAnimalIdsSets = $this->getParentGroupedAnimalIdsByPairs([$parentIdsPair], $recalculate);

            $this->writeBatchCount();

            foreach ($groupedAnimalIdsSets as $groupedAnimalIdsSet)
            {
                if ($this->processRepository()->isReportProcessCancelled()) {
                    break;
                }
                $this->processGroupedAnimalIdsSets([$groupedAnimalIdsSet], $process->isRecalculate());
            }

        } catch (\Exception $exception) {

            $process = $this->processRepository()->getSmallProcess();
            $process->setErrorCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $process->setErrorMessage($exception->getMessage());
            $process->setDebugErrorMessage($exception->getTraceAsString());
            $process->setProcessDetails($this->getProcessDetails(), true);
            $this->em->persist($process);
            $this->em->flush();

            throw new InternalServerErrorException($exception->getMessage(), $exception);
        }

        $process = $this->processRepository()->getSmallProcess();
        $process->setProcessDetails($this->getProcessDetails());
        $this->em->persist($process);
        $this->em->flush();

        return true;
    }


    private function processGroupedAnimalIdsSets(array $groupedAnimalIdsSets, bool $recalculate)
    {
        $this->refillParentsCalculationTables($groupedAnimalIdsSets);

        foreach ($groupedAnimalIdsSets as $groupedAnimalIdSet)
        {
            $this->processGroupedAnimalIdsSet($groupedAnimalIdSet, $recalculate);
        }

        $this->clearParentsCalculationTables();
    }

}
