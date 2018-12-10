<?php


namespace AppBundle\Exception;


class InvalidSwitchCaseException extends \Exception
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
        return 'Invalid switch case.' . (!empty($customMessage) ? ' ' . $customMessage : '');
    }
}