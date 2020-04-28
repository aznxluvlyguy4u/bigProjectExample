<?php


namespace AppBundle\Exception\Sqs;


class SqsMessageInvalidBodyException extends SqsMessageException
{
    /**
     * @param string|null $message
     * @param \Exception|null $previous
     * @param int $code
     */
    public function __construct(?string $message = '',
                                \Exception $previous = null, int $code = 0)
    {
        parent::__construct(
            $this->getAppendedMessage($message),
            $previous,
            $code
        );
    }


    /**
     * @param null|string $customMessage
     * @return string
     */
    private function getAppendedMessage(?string $customMessage = ''): string
    {
        return 'Invalid SQS message body.' . (!empty($customMessage) ? ' ' . $customMessage : '');
    }
}
