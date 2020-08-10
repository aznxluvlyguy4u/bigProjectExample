<?php


namespace AppBundle\Util;


use AppBundle\Entity\DeclareBaseResponseInterface;
use AppBundle\Enumerator\ErrorKindIndicator;
use AppBundle\Enumerator\SuccessIndicator;

class RvoResponseUtil
{
    public static function hasSuccessRvoResponseDetails(DeclareBaseResponseInterface $response): bool
    {
        return $response->getSuccessIndicator() === SuccessIndicator::J;
    }

    public static function hasSuccessWithWarningRvoResponseDetails(DeclareBaseResponseInterface $response): bool
    {
        return $response->getSuccessIndicator() === SuccessIndicator::J
            && $response->getErrorKindIndicator() === ErrorKindIndicator::W
            ;
    }

    public static function hasFailedRvoResponseDetails(DeclareBaseResponseInterface $response): bool
    {
        return $response->getSuccessIndicator() === SuccessIndicator::N;
    }
}