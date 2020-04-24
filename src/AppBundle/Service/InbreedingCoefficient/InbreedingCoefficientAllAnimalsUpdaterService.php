<?php


namespace AppBundle\Service\InbreedingCoefficient;


use AppBundle\Entity\InbreedingCoefficientTaskAdmin;
use AppBundle\Entity\InbreedingCoefficientTaskAdminRepository;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\InbreedingCoefficientProcessSlot;
use AppBundle\model\metadata\YearMonthData;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class InbreedingCoefficientAllAnimalsUpdaterService extends InbreedingCoefficientUpdaterServiceBase
{
    private function taskRepository(): InbreedingCoefficientTaskAdminRepository
    {
        return $this->em->getRepository(InbreedingCoefficientTaskAdmin::class);
    }

    public function start(Person $actionBy, bool $recalculate)
    {
        AdminValidator::isAdmin($actionBy,AccessLevelType::SUPER_ADMIN,true);

        $process = $this->processRepository()->getAdminProcess();

        if (!$process->isFinished()) {
            if ($this->taskRepository()->next()) {
                $this->taskRepository()->purgeQueue();
            }
            throw new BadRequestHttpException($this->translator->trans('process.duplicate'));
        }

        $this->updateAnimalsWithoutParents();

        $startedAt = new \DateTime();
        $process->reset($startedAt, $recalculate);

        $this->resetCounts();

        $yearsAndMonthsAnimalIdsSets = $this->calcInbreedingCoefficientParentRepository->getAllYearsAndMonths();

        if ($recalculate) {
            $this->totalInbreedingCoefficientPairs = array_sum(
                array_map(function (YearMonthData $yearMonthData) {
                    return $yearMonthData->getCount();
                }, $yearsAndMonthsAnimalIdsSets)
            );
        } else {
            $this->totalInbreedingCoefficientPairs = array_sum(
                array_map(function (YearMonthData $yearMonthData) {
                    return $yearMonthData->getMissingInbreedingCoefficientCount();
                }, $yearsAndMonthsAnimalIdsSets)
            );

            $alreadyExistsCount = array_sum(
                array_map(function (YearMonthData $yearMonthData) {
                    return $yearMonthData->getNonMissingCount();
                }, $yearsAndMonthsAnimalIdsSets)
            );
            $this->logger->notice("$alreadyExistsCount inbreeding coefficient pairs skipped (already exist). Includes animals without both parents.");

            $yearsAndMonthsAnimalIdsSets = array_filter(
                $yearsAndMonthsAnimalIdsSets, function (YearMonthData $yearMonthData) {
                return $yearMonthData->hasMissingInbreedingCoefficients();
            }
            );
        }

        $process->setTotal($this->totalInbreedingCoefficientPairs);
        $this->em->persist($process);

        $this->taskRepository()->add($yearsAndMonthsAnimalIdsSets, $startedAt);

        $this->em->flush();

        return ResultUtil::successResult('ok');
    }

    public function run(): bool
    {
        $process = $this->processRepository()->getAdminProcess();
        $isLoopRunSuccessful = true;

        if ($process->isLocked()) {
            $this->validateLockedDuration($process);
            return true;
        }


        try {
            $task = $this->taskRepository()->next();

            if (!$task) {

                if (
                    $process->getFinishedAt() == null ||
                    $process->getProcessed() !== 100
                ) {
                    $now = new \DateTime();
                    $process->setFinishedAt($now);
                    $process->setBumpedAt($now);
                    $process->setProgress(100);
                    $process->setIsCancelled(false);
                    $process->setIsLocked(false);
                    $this->em->persist($process);
                    $this->em->flush();
                    $this->writeBatchCount('Completed!');
                }
                return $isLoopRunSuccessful;
            }

            $taskId = $task->getId();
            $process->setIsLocked(true);
            $this->em->persist($process);
            $this->em->flush();
            $this->em->clear();

            $isLoopRunSuccessful = $this->generateForAllAnimalsAndLitterBasePeriodLoop(
                $task->getYear(), $task->getMonth()
            );

            $this->taskRepository()->deleteTask($taskId);

        } catch (\Exception $exception) {
            $isLoopRunSuccessful = false;
        }


        $process = $this->processRepository()->getAdminProcess();
        $process->setIsLocked(false);
        $this->em->persist($process);
        $this->em->flush();

        return $isLoopRunSuccessful;
    }


    public function cancel(Person $actionBy)
    {
        AdminValidator::isAdmin($actionBy,AccessLevelType::SUPER_ADMIN,true);

        $this->taskRepository()->purgeQueue();

        $process = $this->processRepository()->getAdminProcess();
        $process->setFinishedAt(new \DateTime());
        $process->setIsCancelled(true);

        $this->em->persist($process);
        $this->em->flush();
    }


    public function generateForAllAnimalsAndLitters()
    {
        // TODO remove after function calls are removed
    }

    public function regenerateForAllAnimalsAndLitters()
    {
        // TODO remove after function calls are removed
    }


    private function generateForAllAnimalsAndLitterBasePeriodLoop(int $year, int $month): bool
    {
        $this->setProcessSlot(InbreedingCoefficientProcessSlot::ADMIN);

        $process = $this->processRepository()->getAdminProcess();
        $this->resetCounts($process);
        $this->em->clear();

        $this->logMessageGroup = "$year-$month (year-month)";

        try {
            $groupedAnimalIdsSets = $this->getParentGroupedAnimalIdsByYearAndMonth($year, $month);
            $this->writeBatchCount();

            foreach ($groupedAnimalIdsSets as $groupedAnimalIdsSet)
            {
                if ($this->processRepository()->isAdminProcessCancelled()) {
                    break;
                }
                $this->processGroupedAnimalIdsSets([$groupedAnimalIdsSet], $process->isRecalculate());
            }

        } catch (\Exception $exception) {
            $process = $this->processRepository()->getAdminProcess();
            $process->setErrorCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $process->setErrorMessage($exception->getMessage());
            $process->setDebugErrorMessage($exception->getTraceAsString());
            $process->setProcessDetails($this->getProcessDetails(), true);
            $this->em->persist($process);
            $this->em->flush();
            return false;
        }

        $process = $this->processRepository()->getAdminProcess();
        $process->setProcessDetails($this->getProcessDetails());
        $this->em->persist($process);
        $this->em->flush();

        return true;
    }


    /**
     * @param  array  $groupedAnimalIdsSets
     * @param  bool  $recalculate
     */
    private function processGroupedAnimalIdsSets(array $groupedAnimalIdsSets, bool $recalculate)
    {
        $this->refillParentsCalculationTables($groupedAnimalIdsSets);

        foreach ($groupedAnimalIdsSets as $groupedAnimalIdSet)
        {
            $this->processGroupedAnimalIdsSet($groupedAnimalIdSet, $recalculate);
        }

        $this->clearParentsCalculationTables();
    }



    private function updateAnimalsWithoutParents()
    {
        $this->logger->notice("Remove update mark if animal now has both parents...");
        $sql1 = "UPDATE animal SET inbreeding_coefficient_match_updated_at = NULL
WHERE inbreeding_coefficient_id ISNULL AND inbreeding_coefficient_match_updated_at NOTNULL
    AND parent_father_id NOTNULL AND parent_mother_id NOTNULL";
        $this->em->getConnection()->executeQuery($sql1);

        $this->logger->notice("Add update mark to animals without parents...");
        $sql2 = "UPDATE animal SET inbreeding_coefficient_match_updated_at = NOW()
WHERE EXISTS(
              SELECT
                  a.id
              FROM animal a
              WHERE (parent_father_id ISNULL OR parent_mother_id ISNULL OR date_of_birth ISNULL)
                AND inbreeding_coefficient_match_updated_at ISNULL
                AND a.id = animal.id
          )";
        $this->em->getConnection()->executeQuery($sql2);
        $this->logger->notice("Finished updating update marks for animals without parents");
    }


    private function getParentGroupedAnimalIdsByYearAndMonth(int $year, int $month, bool $recalculate = false): array
    {
        return $this->getParentGroupedAnimalAndLitterIds(
            "date_part('YEAR', date_of_birth) = $year AND date_part('MONTH', date_of_birth) = $month AND",
            $recalculate
        );
    }
}
