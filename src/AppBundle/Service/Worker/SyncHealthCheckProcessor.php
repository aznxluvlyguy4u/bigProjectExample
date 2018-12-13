<?php


namespace AppBundle\Service\Worker;


use AppBundle\Entity\Employee;
use AppBundle\Entity\HealthCheckTask;
use AppBundle\Entity\SqsCommandWorker;
use AppBundle\Enumerator\SqsCommandType;
use AppBundle\Exception\Sqs\SqsMessageInvalidBodyException;
use AppBundle\Service\AwsQueueServiceBase;
use AppBundle\Util\LocationHealthUpdater;
use Aws\Result;
use Symfony\Component\HttpFoundation\Response;

class SyncHealthCheckProcessor extends SqsWorkerTaskProcessorBase implements SqsWorkerTaskProcessorInterface
{
    /** @var LocationHealthUpdater $locationHealthUpdater */
    private $locationHealthUpdater;

    /**
     * @required
     *
     * @param LocationHealthUpdater $locationHealthUpdater
     */
    public function setLocationHealthUpdater(LocationHealthUpdater $locationHealthUpdater)
    {
        $this->locationHealthUpdater = $locationHealthUpdater;
    }

    /**
     * @param Result $queueMessage
     * @throws \Throwable
     */
    function process(Result $queueMessage)
    {
        $worker = null;

        try {
            $this->logStartMessage(SqsCommandType::SYNC_HEALTH_CHECK);

            $healthCheckTask = $this->getNextHealthCheckTask($queueMessage);

            $worker = $this->getNewSqsCommandWorker($healthCheckTask);

            $this->processHealthCheckTask($healthCheckTask);

            $this->closeSqsCommandWorker($worker);
            $this->getManager()->flush();

            $worker = null;

        } catch (\Throwable $e) {
            $this->closeSqsCommandWorker($worker, $e);
            $this->getManager()->flush();
            throw $e;
        }
    }


    /**
     * @param Result $queueMessage
     * @return HealthCheckTask
     * @throws SqsMessageInvalidBodyException
     */
    private function getNextHealthCheckTask(Result $queueMessage): HealthCheckTask
    {
        $messageBody = AwsQueueServiceBase::getMessageBodyFromResponse($queueMessage, false);

        $healthCheckTask = $this->getJsonMessageReader()->readHealthCheckTask($messageBody);

        if (!($healthCheckTask instanceof HealthCheckTask)) {
            throw new SqsMessageInvalidBodyException('Could not serialize message body to HealthCheckTask');
        }

        return $healthCheckTask;
    }


    /**
     * @param HealthCheckTask $healthCheckTask
     * @return SqsCommandWorker
     */
    private function getNewSqsCommandWorker(HealthCheckTask $healthCheckTask): SqsCommandWorker
    {
        $automatedProcess = $this->getManager()->getRepository(Employee::class)->getAutomatedProcess();

        $location = $healthCheckTask->getDestinationLocation();
        $owner = $location ? $location->getOwner() : null;

        $worker = new SqsCommandWorker();
        $worker
            ->setCommandType(SqsCommandType::SYNC_HEALTH_CHECK)
            ->setLocation($location)
            ->setActionBy($automatedProcess)
            ->setOwner($owner)
            ->setStartedAt(new \DateTime())
        ;
        return $worker;
    }


    /**
     * @param HealthCheckTask $healthCheckTask
     * @throws \AppBundle\Exception\InvalidSwitchCaseException
     */
    private function processHealthCheckTask(HealthCheckTask $healthCheckTask)
    {
        $this->locationHealthUpdater->updateByHealthCheckTaskFromRvoSync($healthCheckTask);
    }


    /**
     * @param SqsCommandWorker $worker
     * @param null|\Throwable $e
     */
    private function closeSqsCommandWorker(?SqsCommandWorker $worker = null, ?\Throwable $e = null)
    {
        if (!$worker) {
            return;
        }

        $worker->setFinishedAt(new \DateTime());

        if($e) {
            $worker->setDebugErrorCode($e->getCode());
            $worker->setDebugErrorMessage($e->getMessage());
            $worker->setErrorCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $worker->setErrorMessage('SOMETHING WENT WRONG');
        }

        $this->getManager()->persist($worker);
    }
}