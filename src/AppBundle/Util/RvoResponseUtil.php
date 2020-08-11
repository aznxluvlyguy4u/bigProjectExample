<?php


namespace AppBundle\Util;


use AppBundle\Entity\DeclareBaseResponseInterface;
use AppBundle\Enumerator\ErrorKindIndicator;
use AppBundle\Enumerator\RvoErrorCode;
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


    public static function extractMessageNumberFromErrorMessage(string $errorCode, string $errorMessage): ?string
    {
        return $errorCode === RvoErrorCode::REPEATED_DECLARE_00015 ?
            self::extractMessageNumberFromErrorIRD00015($errorMessage) : null;
    }


    private static function extractMessageNumberFromErrorIRD00015($errorMessage): ?string
    {
        /*
         * Example message
         * Deze melding is al gedaan of er is niets gewijzigd. Aanvullende info: meldingnummer = 290111894, ander kanaal = N, andere melder = N.
         */

        $messageNumber = StringUtil::extractSandwichedSubString($errorMessage,
            'Deze melding is al gedaan of er is niets gewijzigd. Aanvullende info: meldingnummer = ',
            ', ander kanaal = N, andere melder = N.'
        );

        return (ctype_digit($messageNumber) || is_int($messageNumber)) && !empty($messageNumber) ? $messageNumber : null;
    }
}