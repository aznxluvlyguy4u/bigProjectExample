<?php

namespace AppBundle\Output;


use AppBundle\Component\Utils;
use AppBundle\Entity\DeclareImport;

class DeclareImportResponseOutput
{
    /**
     * @param DeclareImport $import
     * @return array
     */
    public static function createHistoryResponse($import)
    {
        $lastResponse = Utils::returnLastResponse($import->getResponses());
        if($lastResponse) {
            $messageNumber = $lastResponse->getMessageNumber();
        } else {
            $messageNumber = null;
        }

        return array(
            "request_id" => $import->getRequestId(),
            "log_date" => $import->getLogDate(),
            "uln_country_code" => $import->getUlnCountryCode(),
            "uln_number" => $import->getUlnNumber(),
            "pedigree_country_code" => $import->getPedigreeCountryCode(),
            "pedigree_number" => $import->getPedigreeNumber(),
            "is_import_animal" => $import->getIsImportAnimal(),
            "arrival_date" => $import->getImportDate(),
            "country_origin" => $import->getAnimalCountryOrigin(),
            "animal_uln_number_origin" => $import->getAnimalUlnNumberOrigin(),
            "request_state" => $import->getRequestState(),
            "message_number" => $messageNumber
        );
    }

    /**
     * @param DeclareImport $import
     * @return array
     */
    public static function createErrorResponse($import)
    {
        $lastResponse = Utils::returnLastResponse($import->getResponses());
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

        $res = array("request_id" => $import->getRequestId(),
            "log_date" => $import->getLogDate(),
            "uln_country_code" => $import->getUlnCountryCode(),
            "uln_number" => $import->getUlnNumber(),
            "pedigree_country_code" => $import->getPedigreeCountryCode(),
            "pedigree_number" => $import->getPedigreeNumber(),
            "is_import_animal" => $import->getIsImportAnimal(),
            "arrival_date" => $import->getImportDate(),
            "country_origin" => $import->getAnimalCountryOrigin(),
            "animal_uln_number_origin" => $import->getAnimalUlnNumberOrigin(),
            "request_state" => $import->getRequestState(),
            "error_code" => $errorCode,
            "error_message" => $errorMessage,
            "message_number" => $messageNumber
        );

        return $res;
    }


}