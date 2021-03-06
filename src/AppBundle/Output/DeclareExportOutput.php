<?php

namespace AppBundle\Output;


use AppBundle\Entity\DeclareExport;

/**
 * Class DeclareExportOutput
 */
class DeclareExportOutput extends Output
{
    /**
     * @param DeclareExport $export
     * @return array
     */
    public static function createPostRequestArray(DeclareExport $export)
    {
        $animal = $export->getAnimal();
        if($animal != null) {
            $animalId = $animal->getId();
        } else {
            $animalId = null;
        }

        $result = array("id" => $export->getId(),
            "request_id" => $export->getRequestId(),
            "message_id" => $export->getMessageId(),
            "request_state" => $export->getRequestState(),
            "action" => $export->getAction(),
            "recovery_indicator" => $export->getRecoveryIndicator(),
            "relation_number_keeper" => $export->getRelationNumberKeeper(),
            "log_date" => $export->getLogDate(),
            "ubn" => $export->getUbn(),
            "export_date" => $export->getExportDate(),
            "reason_of_export" => $export->getReasonOfExport(),
            "type" => "DeclareExport",
            "animal" =>
            array("id" => $animalId,
                  "uln_country_code" => $export->getUlnCountryCode(),
                  "uln_number" => $export->getUlnNumber(),
                  "pedigree_country_code" => $export->getPedigreeCountryCode(),
                  "pedigree_number" => $export->getPedigreeNumber(),
                  "is_export_animal" => $export->getIsExportAnimal(),
                  "animal_type" => $export->getAnimalType(),
                  "type" => $export->getAnimalObjectType()
            ),
            "location"=>
            array("id" => $export->getLocation()->getId(),
                  "ubn" => $export->getLocation()->getUbn()),
            "action_by" => self::actionByOutput($export->getActionBy())
        );

        return $result;
    }


    public static function createUpdateRequestArray(DeclareExport $export)
    {
        //At this moment the update array is identical to post array
        return self::createPostRequestArray($export);
    }


}