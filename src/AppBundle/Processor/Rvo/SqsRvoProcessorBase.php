<?php


namespace AppBundle\Processor\Rvo;


use AppBundle\Enumerator\RequestType;
use AppBundle\Processor\SqsProcessorBase;
use AppBundle\Service\AwsQueueServiceBase;
use AppBundle\Service\QueueServiceInterface;
use Aws\Result;

abstract class SqsRvoProcessorBase extends SqsProcessorBase
{
    // Set in child class
    const PROCESS_TYPE = null;

    const SUPPORTED_REQUEST_TYPES = [
        RequestType::DECLARE_ANIMAL_FLAG,
    ];

    protected function getRequestType(Result $queueMessage): string
    {
        return AwsQueueServiceBase::getTaskType($queueMessage, self::SUPPORTED_REQUEST_TYPES);
    }

    protected function processEmptyMessage(QueueServiceInterface $queueService, Result $queueMessage)
    {
        $queueId = $queueService->getQueueId();
        $requestType = $this->getRequestType($queueMessage);
        $requestId = AwsQueueServiceBase::getMessageId($queueMessage);

        $errorMessage = "There is an empty message in queue $queueId. RequestType:$requestType, requestId:$requestId";

        $this->exceptionLogger->emergency($this->logPrefix().$errorMessage);
        $queueService->deleteMessage($queueMessage);
        $this->messageCount++;
    }

    protected function logPrefix(): string
    {
        return static::PROCESS_TYPE .': ';
    }

    protected function logException(\Throwable $exception, string $prefix = '')
    {
        parent::logException($exception, $prefix . $this->logPrefix());
    }
}