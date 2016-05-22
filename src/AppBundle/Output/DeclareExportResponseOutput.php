<?php

namespace AppBundle\Output;


use AppBundle\Entity\DeclareExport;

class DeclareExportResponseOutput
{
    /**
     * @param DeclareExport $export
     * @return array
     */
    public static function createHistoryResponse($export)
    {
        return array(
            "request_id" => $export->getRequestId(),
            "log_datum" => $export->getLogDate(),
            "uln_country_code" => $export->getUlnCountryCode(),
            "uln_number" => $export->getUlnNumber(),
            "pedigree_country_code" => $export->getPedigreeCountryCode(),
            "pedigree_number" => $export->getPedigreeNumber(),
            "is_export_animal" => $export->getIsExportAnimal(),
            "depart_date" => $export->getExportDate(),
            "request_state" => $export->getRequestState()
        );
    }

    /**
     * @param DeclareExport $export
     * @return array
     */
    public static function createErrorResponse($export)
    {
        $lastResponse = $export->getResponses()->last();

        $res = array("request_id" => $export->getRequestId(),
            "log_datum" => $export->getLogDate(),
            "uln_country_code" => $export->getUlnCountryCode(),
            "uln_number" => $export->getUlnNumber(),
            "pedigree_country_code" => $export->getPedigreeCountryCode(),
            "pedigree_number" => $export->getPedigreeNumber(),
            "is_export_animal" => $export->getIsExportAnimal(),
            "depart_date" => $export->getExportDate(),
            "request_state" => $export->getRequestState(),
            "error_code" => $lastResponse->getErrorCode(),
            "error_message" => $lastResponse->getErrorMessage()
        );

        return $res;
    }


}