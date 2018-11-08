<?php


namespace AppBundle\Exception;


use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

class MissingClientHttpException extends PreconditionFailedHttpException
{
    /**
     * @param \Exception|null $previous
     * @param int $code
     */
    public function __construct(\Exception $previous = null, int $code = 0)
    {
        parent::__construct(
            'CLIENT CANNOT BE EMPTY',
            $previous,
            $code
        );
    }

}