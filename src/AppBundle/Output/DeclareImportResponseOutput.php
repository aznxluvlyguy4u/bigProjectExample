<?php

namespace AppBundle\Output;


use AppBundle\Entity\DeclareImport;

class DeclareImportResponseOutput
{
    /**
     * @param DeclareImport $import
     * @return array
     */
    public static function createHistoryResponse($import)
    {
        $lastResponse = $import->getResponses()->last();
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
            "import_date" => $import->getImportDate(),
            "animal_country_origin" => $import->getAnimalCountryOrigin(),
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
        $lastResponse = $import->getResponses()->last();
        if($lastResponse) {
            $messageNumber = $lastResponse->getMessageNumber();
        } else {
            $messageNumber = null;
        }

        $res = array("request_id" => $import->getRequestId(),
            "log_date" => $import->getLogDate(),
            "uln_country_code" => $import->getUlnCountryCode(),
            "uln_number" => $import->getUlnNumber(),
            "pedigree_country_code" => $import->getPedigreeCountryCode(),
            "pedigree_number" => $import->getPedigreeNumber(),
            "is_import_animal" => $import->getIsImportAnimal(),
            "import_date" => $import->getImportDate(),
            "animal_country_origin" => $import->getAnimalCountryOrigin(),
            "request_state" => $import->getRequestState(),
            "error_code" => $lastResponse->getErrorCode(),
            "error_message" => $lastResponse->getErrorMessage(),
            "message_number" => $messageNumber
        );

        return $res;
    }


}