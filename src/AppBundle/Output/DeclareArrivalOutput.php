<?php

namespace AppBundle\Output;


use AppBundle\Entity\DeclareArrival;

/**
 * Class DeclareArrivalOutput
 */
class DeclareArrivalOutput
{
    /**
     * @param DeclareArrival $arrival
     * @return array
     */
    public static function createPostRequestArray(DeclareArrival $arrival)
    {
        $animal = $arrival->getAnimal();
        if($animal != null) {
            $animalId = $animal->getId();
        } else {
            $animalId = null;
        }

        $result = array("id" => $arrival->getId(),
            "message_id" => $arrival->getMessageId(),
            "request_id" => $arrival->getRequestId(), //Mandatory for IenR
            "relation_number_keeper" => $arrival->getRelationNumberKeeper(),  //Mandatory for IenR
            "ubn" => $arrival->getUbn(),  //Mandatory for IenR,
            "action" => $arrival->getAction(),  //Mandatory for IenR
            "recovery_indicator" => $arrival->getRecoveryIndicator(),  //Mandatory for IenR
            "arrival_date" => $arrival->getArrivalDate(),  //Mandatory for IenR
            "log_date" => $arrival->getLogDate(),
            "is_import_animal" => $arrival->getIsImportAnimal(),
            "ubn_previous_owner" => $arrival->getUbnPreviousOwner(),
            "request_state" => $arrival->getRequestState(),
            "type" => "DeclareArrival",
            "animal" =>
            array("id" => $animalId,
                  "uln_country_code" => $arrival->getUlnCountryCode(),
                  "uln_number" => $arrival->getUlnNumber(),
                  "pedigree_country_code" => $arrival->getPedigreeCountryCode(),
                  "pedigree_number" => $arrival->getPedigreeNumber(),
                  "animal_type" => $arrival->getAnimalType(),
                  "type" => "Ram" //FIXME get from animal
                  //FIXME ADD animalType ALSO TO ENTITY  /Mandatory for IenR
            ),
            "location"=>
            array("id" => $arrival->getLocation()->getId(),
                  "ubn" => $arrival->getLocation()->getUbn())  //Mandatory for IenR or use the own above
        );

        return $result;
    }


    public static function createUpdateRequestArray(DeclareArrival $arrival)
    {
        //At this moment the update array is identical to post array
        return self::createPostRequestArray($arrival);
    }


}