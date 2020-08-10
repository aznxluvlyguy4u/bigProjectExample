<?php


namespace AppBundle\Exception\Rvo;


use AppBundle\Entity\DeclareBaseResponseInterface;
use Throwable;

class RvoUnmappedResponseDetailsException extends RvoInternalWorkerException
{
    public function __construct(DeclareBaseResponseInterface $response, $code = 0, Throwable $previous = null)
    {
        $successIndicator = $response->getSuccessIndicator();
        $errorIndicator = $response->getErrorKindIndicator();
        $message = "The successIndicator=$successIndicator & errorIndicator=$errorIndicator combination ".
            "has not been mapped to a RequestStateType yet";

        parent::__construct($message, $code, $previous);
    }
}