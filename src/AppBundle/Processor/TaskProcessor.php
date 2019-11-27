<?php

namespace AppBundle\Processor;

use AppBundle\Entity\UpdateAnimalDataWorker;
use AppBundle\Enumerator\UpdateType;
use AppBundle\Enumerator\WorkerAction;
use AppBundle\Service\BaseSerializer;
use AppBundle\Service\Task\InbreedingCoefficientCalculationTaskService;
use AppBundle\Service\Task\StarEwesCalculationTaskService;
use AppBundle\Util\ResultUtil;
use Doctrine\ORM\EntityManager;
use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Util\JSON;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;

class TaskProcessor implements PsrProcessor, CommandSubscriberInterface
{
    const ERROR_LOG_HEADER = '===== SYMFONY WORKER =====';

    /**
     * @var EntityManager
     */
    private $em;

    /** @var BaseSerializer */
    private $serializer;

    /** @var StarEwesCalculationTaskService */
    private $starEwesCalculationTaskService;

    /** @var InbreedingCoefficientCalculationTaskService */
    private $inbreedingCoefficientCalculationTaskService;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * ReportProcessor constructor.
     * @param InbreedingCoefficientCalculationTaskService $inbreedingCoefficientCalculationTaskService
     * @param StarEwesCalculationTaskService $starEwesCalculationTaskService
     * @param EntityManager $em
     * @param Logger $logger
     * @param BaseSerializer $serializer
     */
    public function __construct(
        InbreedingCoefficientCalculationTaskService $inbreedingCoefficientCalculationTaskService,
        StarEwesCalculationTaskService $starEwesCalculationTaskService,
        EntityManager $em,
        Logger $logger,
        BaseSerializer $serializer
    )
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->starEwesCalculationTaskService = $starEwesCalculationTaskService;
        $this->inbreedingCoefficientCalculationTaskService = $inbreedingCoefficientCalculationTaskService;
    }

    public function process(PsrMessage $message, PsrContext $context)
    {
        $worker = null;
        try {
            $data = JSON::decode($message->getBody());

            $workerId = $data['worker_id'];

            /**
             * @var UpdateAnimalDataWorker $worker
             */
            $worker = $this->em->getRepository(UpdateAnimalDataWorker::class)->find($workerId);
            if (!$worker)
                return self::REJECT;

            $updateType = $worker->getUpdateType();
            $workerType = $worker->getWorkerType();
            $location = $worker->getLocation();
            $actionBy = $worker->getActionBy();

            switch ($updateType) {
                case UpdateType::STAR_EWES:
                    {
                        $data = $this->starEwesCalculationTaskService->calculate($actionBy, $location);
                        break;
                    }
                case UpdateType::INBREEDING_COEFFICIENT_CALCULATION:
                    {
                        $data = $this->inbreedingCoefficientCalculationTaskService->calculate();
                        break;
                    }
                case UpdateType::INBREEDING_COEFFICIENT_RECALCULATION:
                    {
                        $data = $this->inbreedingCoefficientCalculationTaskService->recalculate();
                        break;
                    }
            }
            $arrayData = JSON::decode($data->getContent());

            if($data->getStatusCode() !== Response::HTTP_OK) {
                $worker->setErrorCode($data->getStatusCode());
                $worker->setErrorMessage(ResultUtil::getMessageStringFromErrorResult($arrayData));
            }
            $this->em->persist($worker);
        }
        catch(\Throwable $e){
            if($worker) {
                $worker->setDebugErrorCode($e->getCode());
                $worker->setDebugErrorMessage($e->getMessage());
                if ($this->publiclyDisplayErrorMessage($e->getCode())) {
                    $worker->setErrorCode($e->getCode());
                    $worker->setErrorMessage($e->getMessage());
                } else {
                    $worker->setErrorCode(Response::HTTP_INTERNAL_SERVER_ERROR);
                    $worker->setErrorMessage('SOMETHING WENT WRONG');
                }
            }
            $this->logException($e);
        }

        try {
            if ($worker) {
                $worker->setFinishedAt(new \DateTime());
                $this->em->persist($worker);
            }
            $this->em->flush();
        }
        catch (\Exception $e) {
            //Database Exception
            $this->logException($e);
        }
        return self::ACK;
    }

    public static function getSubscribedCommand()
    {
        return WorkerAction::UPDATE_ANIMAL_DATA;
    }

    private function logException(\Throwable $exception)
    {
        $this->logger->error(self::ERROR_LOG_HEADER);
        $this->logger->error($exception->getMessage());
        $this->logger->error($exception->getTraceAsString());
    }


    /**
     * @param int|null $errorCode
     * @return bool
     */
    private function publiclyDisplayErrorMessage($errorCode): bool
    {
        return is_int($errorCode) && (
                $errorCode === Response::HTTP_NOT_FOUND
            );
    }
}
