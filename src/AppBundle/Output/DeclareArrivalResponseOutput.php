<?php

namespace AppBundle\Output;


use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareArrivalResponse;
use AppBundle\Entity\DeclareImport;

class DeclareArrivalResponseOutput
{
    /**
     * @param DeclareArrival $arrival
     * @return array
     */
    public static function createHistoryResponse($arrival)
    {
        $lastResponse = $arrival->getResponses()->last();
        if($lastResponse) {
            $messageNumber = $lastResponse->getMessageNumber();
        } else {
            $messageNumber = null;
        }

        return array(
            "request_id" => $arrival->getRequestId(),
            "log_date" => $arrival->getLogDate(),
            "uln_country_code" => $arrival->getUlnCountryCode(),
            "uln_number" => $arrival->getUlnNumber(),
            "pedigree_country_code" => $arrival->getPedigreeCountryCode(),
            "pedigree_number" => $arrival->getPedigreeNumber(),
            "arrival_date" => $arrival->getArrivalDate(),
            "is_import_animal" => $arrival->getIsImportAnimal(),
            "ubn_previous_owner" => $arrival->getUbnPreviousOwner(),
            "request_state" => $arrival->getRequestState(),
            "message_number" => $messageNumber
        );
    }

    /**
     * @param DeclareArrival $arrival
     * @return array
     */
    public static function createErrorResponse($arrival)
    {
        $lastResponse = $arrival->getResponses()->last();
        if($lastResponse != false) {
            $errorCode = $lastResponse->getErrorCode();
            $errorMessage = $lastResponse->getErrorMessage();
            $messageNumber = $lastResponse->getMessageNumber();
            $isRemovedByUser = $lastResponse->getIsRemovedByUser();
        } else {
            $errorCode = null;
            $errorMessage = null;
            $messageNumber = null;
            $isRemovedByUser = true;
        }

        if($isRemovedByUser) {
            return null;
        }

        $res = array("request_id" => $arrival->getRequestId(),
            "log_date" => $arrival->getLogDate(),
            "uln_country_code" => $arrival->getUlnCountryCode(),
            "uln_number" => $arrival->getUlnNumber(),
            "pedigree_country_code" => $arrival->getPedigreeCountryCode(),
            "pedigree_number" => $arrival->getPedigreeNumber(),
            "is_import_animal" => $arrival->getIsImportAnimal(),
            "arrival_date" => $arrival->getArrivalDate(),
            "ubn_previous_owner" => $arrival->getUbnPreviousOwner(),
            "request_state" => $arrival->getRequestState(),
            "error_code" => $errorCode,
            "error_message" => $errorMessage,
            "message_number" => $messageNumber
        );

        return $res;
    }


}