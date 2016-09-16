<?php

namespace AppBundle\Util;


use AppBundle\Enumerator\ErrorKindIndicator;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\SuccessIndicator;

class IRUtil
{
    /**
     * @param string $successIndicator
     * @param mixed $defaultResult
     * @return bool|null
     */
    public static function isSuccessResponse($successIndicator, $defaultResult = null)
    {
        if($successIndicator == SuccessIndicator::J) {
            return true;

        } elseif($successIndicator == SuccessIndicator::N) {
            return false;

        } else {
            return $defaultResult;
        }
    }


    /**
     * @param string $successIndicator
     * @param string $errorKindIndicator
     * @param string $defaultRequestState
     * @return null|string
     */
    public static function getRequestState($successIndicator, $errorKindIndicator, $defaultRequestState = null)
    {
        if(self::isSuccessResponse($successIndicator, false)) {

            if($errorKindIndicator == ErrorKindIndicator::W) {
                return RequestStateType::FINISHED_WITH_WARNING;

            } else {
                return RequestStateType::FINISHED;
            }

        } elseif($successIndicator == SuccessIndicator::N) {
            return RequestStateType::FAILED;

        } else {
            return $defaultRequestState;
        }
    }


    /**
     * @param string $requestState
     * @return bool
     */
    public static function isProcessedRequest($requestState)
    {
        if( $requestState != RequestStateType::OPEN && $requestState != RequestStateType::REVOKING
        ) {
            return true;
        } else {
            return false;
        }
    }
}