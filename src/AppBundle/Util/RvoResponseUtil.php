<?php


namespace AppBundle\Util;


use AppBundle\Entity\DeclareBaseResponseInterface;
use AppBundle\Enumerator\ErrorKindIndicator;
use AppBundle\Enumerator\SuccessIndicator;

class RvoResponseUtil
{
    public static function hasSuccessResponse(DeclareBaseResponseInterface $response): bool
    {
        return $response->getSuccessIndicator() === SuccessIndicator::J;
    }

    public static function hasSuccessWithWarningResponse(DeclareBaseResponseInterface $response): bool
    {
        return $response->getSuccessIndicator() === SuccessIndicator::J
            && $response->getErrorKindIndicator() === ErrorKindIndicator::W
            ;
    }

    public static function hasFailedResponse(DeclareBaseResponseInterface $response): bool
    {
        return $response->getSuccessIndicator() === SuccessIndicator::N;
    }
}