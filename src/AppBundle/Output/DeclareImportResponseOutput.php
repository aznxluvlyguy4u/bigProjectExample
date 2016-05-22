<?php

namespace AppBundle\Output;


use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareImport;

class DeclareImportResponseOutput
{
    /**
     * @param DeclareImport $arrival
     * @return array
     */
    public static function createHistoryResponse($arrival)
    {
        return array(
            "request_id" => $arrival->getRequestId(),
            "log_datum" => $arrival->getLogDate(),
            "uln_country_code" => $arrival->getUlnCountryCode(),
            "uln_number" => $arrival->getUlnNumber(),
            "pedigree_country_code" => $arrival->getPedigreeCountryCode(),
            "pedigree_number" => $arrival->getPedigreeNumber(),
            "is_import_animal" => $arrival->getIsImportAnimal(),
            "arrival_date" => $arrival->getImportDate(),
            "animal_country_origin" => $arrival->getAnimalCountryOrigin(),
            "request_state" => $arrival->getRequestState()
        );
    }

    /**
     * @param DeclareImport $arrival
     * @return array
     */
    public static function createErrorResponse($arrival)
    {
        $lastResponse = $arrival->getResponses()->last();

        $res = array("request_id" => $arrival->getRequestId(),
            "log_datum" => $arrival->getLogDate(),
            "uln_country_code" => $arrival->getUlnCountryCode(),
            "uln_number" => $arrival->getUlnNumber(),
            "pedigree_country_code" => $arrival->getPedigreeCountryCode(),
            "pedigree_number" => $arrival->getPedigreeNumber(),
            "is_import_animal" => $arrival->getIsImportAnimal(),
            "arrival_date" => $arrival->getImportDate(),
            "animal_country_origin" => $arrival->getAnimalCountryOrigin(),
            "request_state" => $arrival->getRequestState(),
            "error_code" => $lastResponse->getErrorCode(),
            "error_message" => $lastResponse->getErrorMessage()
        );

        return $res;
    }


}