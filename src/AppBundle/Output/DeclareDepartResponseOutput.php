<?php

namespace AppBundle\Output;


use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareImport;

class DeclareDepartResponseOutput
{
    /**
     * @param DeclareDepart $depart
     * @return array
     */
    public static function createHistoryResponse($depart)
    {
        $lastResponse = $depart->getResponses()->last();
        if($lastResponse) {
            $messageNumber = $lastResponse->getMessageNumber();
        } else {
            $messageNumber = null;
        }

        return array(
            "request_id" => $depart->getRequestId(),
            "log_date" => $depart->getLogDate(),
            "uln_country_code" => $depart->getUlnCountryCode(),
            "uln_number" => $depart->getUlnNumber(),
            "pedigree_country_code" => $depart->getPedigreeCountryCode(),
            "pedigree_number" => $depart->getPedigreeNumber(),
            "depart_date" => $depart->getDepartDate(),
            "is_export_animal" => $depart->getIsExportAnimal(),
            "ubn_new_owner" => $depart->getUbnNewOwner(),
            "reason_of_depart" => $depart->getReasonOfDepart(),
            "request_state" => $depart->getRequestState(),
            "message_number" => $messageNumber
        );
    }

    /**
     * @param DeclareDepart $depart
     * @return array
     */
    public static function createErrorResponse($depart)
    {
        $lastResponse = $depart->getResponses()->last();
        if($lastResponse) {
            $messageNumber = $lastResponse->getMessageNumber();
        } else {
            $messageNumber = null;
        }

        $res = array("request_id" => $depart->getRequestId(),
            "log_date" => $depart->getLogDate(),
            "uln_country_code" => $depart->getUlnCountryCode(),
            "uln_number" => $depart->getUlnNumber(),
            "pedigree_country_code" => $depart->getPedigreeCountryCode(),
            "pedigree_number" => $depart->getPedigreeNumber(),
            "depart_date" => $depart->getDepartDate(),
            "is_export_animal" => $depart->getIsExportAnimal(),
            "ubn_new_owner" => $depart->getUbnNewOwner(),
            "reason_of_depart" => $depart->getReasonOfDepart(),
            "request_state" => $depart->getRequestState(),
            "error_code" => $lastResponse->getErrorCode(),
            "error_message" => $lastResponse->getErrorMessage(),
            "message_number" => $messageNumber
        );

        return $res;
    }


}