<?php

namespace AppBundle\Output;


use AppBundle\Entity\DeclareImport;
use Doctrine\ORM\EntityManager;

/**
 * Class DeclareImportOutput
 */
class DeclareImportOutput extends Output
{
    /**
     * @param DeclareImport $import
     * @return array
     */
    public static function createPostRequestArray(EntityManager $em, DeclareImport $import)
    {
        $animal = $import->getAnimal();
        if($animal != null) {
            $animalId = $animal->getId();
        } else {
            $animalId = null;
        }

        self::setUbnAndLocationHealthValues($em, $import->getLocation());

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
            "animal_uln_number_origin" => $import->getAnimalUlnNumberOrigin(),
            "ubn" => $import->getUbn(),
            "type" => "DeclareImport",
            "animal" =>
            array("id" => $animalId,
                  "uln_country_code" => $import->getUlnCountryCode(),
                  "uln_number" => $import->getUlnNumber(),
                  "pedigree_country_code" => $import->getPedigreeCountryCode(),
                  "pedigree_number" => $import->getPedigreeNumber(),
                  "animal_type" => $import->getAnimalType(),
                  "type" => $import->getAnimalObjectType()
            ),
            "location"=>
            array("id" => $import->getLocation()->getId(),
                  "ubn" => $import->getLocation()->getUbn(),  //Mandatory for IenR or use the own above
                "health" =>
                    array(
                        "location_health_status" => self::$locationHealthStatus,
                        //maedi_visna is zwoegerziekte
                        "maedi_visna_status" => self::$maediVisnaStatus,
                        "maedi_visna_end_date" => self::$maediVisnaEndDate,
                        "scrapie_status" => self::$scrapieStatus,
                        "scrapie_end_date" => self::$scrapieEndDate,
                        "check_date" => self::$checkDate
                    )),
            "action_by" => $import->getActionBy()
        );

        return $result;
    }


    public static function createUpdateRequestArray(EntityManager $em, DeclareImport $import)
    {
        //At this moment the update array is identical to post array
        return self::createPostRequestArray($em, $import);
    }


}