<?php

namespace AppBundle\Output;


use AppBundle\Component\Utils;
use AppBundle\Entity\DeclareExport;

class DeclareExportResponseOutput
{
    /**
     * @param DeclareExport $export
     * @return array
     */
    public static function createHistoryResponse($export)
    {
        $lastResponse = Utils::returnLastResponse($export->getResponses());
        if($lastResponse) {
            $messageNumber = $lastResponse->getMessageNumber();
        } else {
            $messageNumber = null;
        }


        return array(
            "request_id" => $export->getRequestId(),
            "log_date" => $export->getLogDate(),
            "uln_country_code" => $export->getUlnCountryCode(),
            "uln_number" => $export->getUlnNumber(),
            "pedigree_country_code" => $export->getPedigreeCountryCode(),
            "pedigree_number" => $export->getPedigreeNumber(),
            "is_export_animal" => $export->getIsExportAnimal(),
            "depart_date" => $export->getExportDate(),
            "reason_of_depart" => $export->getReasonOfExport(),
            "request_state" => $export->getRequestState(),
            "message_number" => $messageNumber
        );
    }

    /**
     * @param DeclareExport $export
     * @return array
     */
    public static function createErrorResponse($export)
    {
        $lastResponse = Utils::returnLastResponse($export->getResponses());
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

        $res = array("request_id" => $export->getRequestId(),
            "log_date" => $export->getLogDate(),
            "uln_country_code" => $export->getUlnCountryCode(),
            "uln_number" => $export->getUlnNumber(),
            "pedigree_country_code" => $export->getPedigreeCountryCode(),
            "pedigree_number" => $export->getPedigreeNumber(),
            "is_export_animal" => $export->getIsExportAnimal(),
            "depart_date" => $export->getExportDate(),
            "reason_of_depart" => $export->getReasonOfExport(),
            "request_state" => $export->getRequestState(),
            "error_code" => $errorCode,
            "error_message" => $errorMessage,
            "is_removed_by_user" => $isRemovedByUser,
            "message_number" => $messageNumber
        );

        return $res;
    }


}