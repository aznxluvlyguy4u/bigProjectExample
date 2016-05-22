<?php

namespace AppBundle\Output;


use AppBundle\Entity\DeclareImport;

/**
 * Class DeclareImportOutput
 */
class DeclareImportOutput
{
    /**
     * @param DeclareImport $import
     * @return array
     */
    public static function createPostRequestArray(DeclareImport $import)
    {
        $animal = $import->getAnimal();
        if($animal != null) {
            $animalId = $animal->getId();
        } else {
            $animalId = null;
        }

        $result = array("id" => $import->getId(),
            "request_id" => $import->getRequestId(),
            "message_id" => $import->getMessageId(),
            "request_state" => $import->getRequestState(),
            "action" => $import->getAction(),
            "recovery_indicator" => $import->getRecoveryIndicator(),
            "relation_number_keeper" => $import->getRelationNumberKeeper(),
            "log_date" => $import->getLogDate(),
            "import_date" => $import->getImportDate(),
            "is_import_animal" => $import->getIsImportAnimal(),
            "animal_country_origin" => $import->getAnimalCountryOrigin(),
            "ubn" => $import->getUbn(),
            "animal" =>
            array("id" => $animalId,
                  "uln_country_code" => $import->getUlnCountryCode(),
                  "uln_number" => $import->getUlnNumber(),
                  "pedigree_country_code" => $import->getPedigreeCountryCode(),
                  "pedigree_number" => $import->getPedigreeNumber()),
            "location"=>
            array("id" => $import->getLocation()->getId(),
                  "ubn" => $import->getLocation()->getUbn())
        );

        return $result;
    }


    public static function createUpdateRequestArray(DeclareImport $import)
    {
        //At this moment the update array is identical to post array
        return self::createPostRequestArray($import);
    }


}