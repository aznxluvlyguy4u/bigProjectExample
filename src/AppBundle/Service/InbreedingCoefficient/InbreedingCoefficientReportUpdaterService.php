<?php


namespace AppBundle\Service\InbreedingCoefficient;


use AppBundle\Entity\InbreedingCoefficientTaskReport;
use AppBundle\Entity\InbreedingCoefficientTaskReportRepository;
use AppBundle\Entity\Person;
use AppBundle\Entity\ReportWorker;
use AppBundle\Entity\ReportWorkerRepository;
use AppBundle\Enumerator\InbreedingCoefficientProcessSlot;
use AppBundle\Exception\InternalServerErrorException;
use AppBundle\Processor\ReportProcessor;
use AppBundle\Service\Report\InbreedingCoefficientReportService;
use AppBundle\Setting\InbreedingCoefficientSetting;
use AppBundle\Util\ParentIdsPairUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\SleepUtil;
use Enqueue\Util\JSON;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class InbreedingCoefficientReportUpdaterService extends InbreedingCoefficientUpdaterServiceBase implements InbreedingCoefficientReportUpdaterServiceInterface
{
    /** @var InbreedingCoefficientReportService */
    private $coefficientReportService;

    /**
     * @required
     *
     * @param  InbreedingCoefficientReportService  $service
     */
    public function setInbreedingCoefficientReportService(InbreedingCoefficientReportService $service)
    {
        $this->coefficientReportService = $service;
    }

    private function reportTaskRepository(): InbreedingCoefficientTaskReportRepository
    {
        return $this->em->getRepository(InbreedingCoefficientTaskReport::class);
    }

    private function reportWorkerRepository(): ReportWorkerRepository
    {
        return $this->em->getRepository(ReportWorker::class);
    }

    public function add(int $workerId, array $ramIds, array $eweIds)
    {
        $task = new InbreedingCoefficientTaskReport($workerId, $ramIds, $eweIds);
        $this->em->persist($task);
        $this->em->flush();
    }

    public function run(): bool
    {
        $process = $this->processRepository()->getReportProcess();
        $isLoopRunSuccessful = true;

        if ($process->isLocked()) {
            $this->validateLockedDuration($process);
            SleepUtil::sleep(InbreedingCoefficientSetting::QUEUE_LOCKED_TIMEOUT_SECONDS_REPORT);
            return true;
        }

        $workerId = null;

        try {
            $task = $this->reportTaskRepository()->next();

            if (!$task) {
                SleepUtil::sleep(InbreedingCoefficientSetting::QUEUE_EMPTY_TIMEOUT_SECONDS_REPORT);
                return $isLoopRunSuccessful;
            }

            $taskId = $task->getId();
            $process->setIsLocked(true);
            $this->em->persist($process);
            $this->em->flush();
            $this->em->clear();

            $ramIds = $task->getRamIds();
            $eweIds = $task->getEweIds();
            $workerId = $task->getWorkerId();

            /** @var ReportWorker $worker */
            $worker = $this->reportWorkerRepository()->find($workerId);
            if (!$worker) {
                throw new BadRequestHttpException('No reportWorker record was found for workerId: '.$workerId);
            }

            $data = $this->generateReport(
                $ramIds,
                $eweIds,
                $workerId,
                $worker->getActionBy(),
                $worker->getFileType(),
                $worker->getLocale()
            );

            $worker = $this->reportWorkerRepository()->find($workerId);
            $arrayData = JSON::decode($data->getContent());
            if($data->getStatusCode() === Response::HTTP_OK) {
                $worker->setDownloadUrl($arrayData['result']);
            }
            else {
                $worker->setErrorCode($data->getStatusCode());
                $worker->setErrorMessage(ResultUtil::getMessageStringFromErrorResult($arrayData));
            }

            $worker->setFinishedAt(new \DateTime());
            $this->em->persist($worker);
            $this->em->flush();

            $this->reportTaskRepository()->deleteTask($taskId);

        } catch (\Exception $exception) {

            $worker = empty($workerId) ? null : $this->reportWorkerRepository()->find($workerId);

            if ($worker) {
                $worker->setDebugErrorCode($exception->getCode());
                $worker->setDebugErrorMessage($exception->getMessage());
                if (ReportProcessor::publiclyDisplayErrorMessage($exception->getCode())) {
                    $worker->setErrorCode($exception->getCode());
                    $worker->setErrorMessage($exception->getMessage());
                } else {
                    $worker->setErrorCode(Response::HTTP_INTERNAL_SERVER_ERROR);
                    $worker->setErrorMessage('SOMETHING WENT WRONG');
                }

                $worker->setFinishedAt(new \DateTime());
                $this->em->persist($worker);
                $this->em->flush();
            }

            $process = $this->processRepository()->getReportProcess();
            $process->setIsLocked(false);
            $process->setDebugErrorMessage($exception->getTraceAsString());
            $process->setErrorMessage($exception->getMessage());
            $process->setErrorCode($exception->getCode());
            $this->em->persist($process);
            $this->em->flush();

            throw new InternalServerErrorException($exception->getMessage(), $exception);
        }


        $process = $this->processRepository()->getReportProcess();
        $process->setIsLocked(false);
        $this->em->persist($process);
        $this->em->flush();

        return $isLoopRunSuccessful;
    }

    /**
     * @param  array  $ramIds
     * @param  array  $eweIds
     * @param  int|null  $workerId
     * @param  Person  $actionBy
     * @param  string  $fileType
     * @param  string  $locale
     * @return \AppBundle\Component\HttpFoundation\JsonResponse
     */
    public function generateReport(
        array $ramIds, array $eweIds, ?int $workerId,
        Person $actionBy, string $fileType, string $locale
    )
    {
        $ramsData = InbreedingCoefficientReportService::getRamsDataByIds($this->em, $ramIds);
        $ewesData = InbreedingCoefficientReportService::getEwesDataByIds($this->em, $eweIds);

        $isLoopRunSuccessful = $this->generateForReportOuterLoop($ramIds, $eweIds, $workerId);
        if (!$isLoopRunSuccessful) {
            throw new InternalServerErrorException('Failed generating inbreeding coefficients. '.
                'See inbreedingCoefficientProcess record slot: '.InbreedingCoefficientProcessSlot::REPORT);
        }

        return $this->coefficientReportService->getReport(
            $actionBy, $ramsData, $ewesData, $fileType, $locale
        );
    }


    public function cancel(): bool
    {
        $this->reportTaskRepository()->purgeQueue();
        $process = $this->processRepository()->getReportProcess();
        return $this->cancelBase($process);
    }


    private function generateForReportOuterLoop(array $ramIds, array $eweIds, ?int $workerId): bool
    {
        $this->setProcessSlot(InbreedingCoefficientProcessSlot::REPORT);

        $process = $this->processRepository()->getReportProcess();
        $this->resetCounts($process);
        $this->em->clear();

        $this->logMessageGroup = empty($workerId) ? 'running without worker' : "workerId: $workerId";

        try {
            $parentIdsPairs = ParentIdsPairUtil::getParentIdsPairsFromIdArrays($ramIds, $eweIds);
            $groupedAnimalIdsSets = $this->getParentGroupedAnimalIdsByPairs($parentIdsPairs);

            $this->writeBatchCount();

            foreach ($groupedAnimalIdsSets as $groupedAnimalIdsSet)
            {
                if ($this->processRepository()->isReportProcessCancelled()) {
                    break;
                }
                $this->processGroupedAnimalIdsSets([$groupedAnimalIdsSet], $process->isRecalculate());
            }

        } catch (\Exception $exception) {
            $process = $this->processRepository()->getReportProcess();
            $process->setErrorCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $process->setErrorMessage($exception->getMessage());
            $process->setDebugErrorMessage($exception->getTraceAsString());
            $process->setProcessDetails($this->getProcessDetails(), true);
            $this->em->persist($process);
            $this->em->flush();

            throw new InternalServerErrorException($exception->getMessage(), $exception);
        }

        $process = $this->processRepository()->getReportProcess();
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
