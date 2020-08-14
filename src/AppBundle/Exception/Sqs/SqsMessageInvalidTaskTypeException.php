<?php


namespace AppBundle\Exception\Sqs;


class SqsMessageInvalidTaskTypeException extends SqsMessageException
{
    /**
     * @param string|null $taskType
     * @param \Exception|null $previous
     * @param int $code
     */
    public function __construct(string $taskType, \Exception $previous = null, int $code = 0)
    {
        $message = 'Sqs is has an invalid TaskType: '.$taskType;

        parent::__construct(
            $message,
            $previous,
            $code
        );
    }

}
