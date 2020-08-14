<?php


namespace AppBundle\Exception\Sqs;


use Throwable;

class SqsMessageSendFailedException extends SqsMessageException
{
    public function __construct(
        string $queueId,
        string $requestType,
        string $messageId, $code = 0, Throwable $previous = null
    )
    {
        $message = "Failed sending message to queue $queueId. RequestType: $requestType, messageId: $messageId";
        parent::__construct($message, $code, $previous);
    }
}