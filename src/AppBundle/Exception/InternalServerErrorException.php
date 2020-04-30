<?php


namespace AppBundle\Exception;


use Symfony\Component\HttpFoundation\Response;

class InternalServerErrorException extends \Exception
{
    /**
     * @param string|null $message
     * @param \Exception|null $previous
     */
    public function __construct(?string $message = 'INTERNAL SERVER ERROR',
                                \Exception $previous = null)
    {
        parent::__construct(
            $message,
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $previous
        );
    }

}
