<?php


namespace AppBundle\Exception\Sqs;


use AppBundle\Sqs\Exception\SqsMessageException;

class SqsMessageInvalidBodyException extends SqsMessageException
{
    /**
     * @param string|null $customMessage
     * @param \Exception|null $previous
     * @param int $code
     */
    public function __construct(?string $customMessage = '',
                                \Exception $previous = null, int $code = 0)
    {
        parent::__construct(
            $this->getAppendedMessage($customMessage),
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