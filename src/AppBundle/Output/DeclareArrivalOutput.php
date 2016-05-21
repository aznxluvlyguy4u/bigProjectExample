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
            "request_id" => $arrival->getRequestId(),
            "message_id" => $arrival->getMessageId(),
            "request_state" => $arrival->getRequestState(),
            "action" => $arrival->getAction(),
            "recovery_indicator" => $arrival->getRecoveryIndicator(),
            "relation_number_keeper" => $arrival->getRelationNumberKeeper(),
            "log_date" => $arrival->getLogDate(),
            "arrival_date" => $arrival->getArrivalDate(),
            "is_import_animal" => $arrival->getIsImportAnimal(),
            "ubn_previous_owner" => $arrival->getUbnPreviousOwner(),
            "ubn" => $arrival->getUbn(),
            "animal" =>
            array("id" => $animalId,
                  "uln_country_code" => $arrival->getUlnCountryCode(),
                  "uln_number" => $arrival->getUlnNumber(),
                  "pedigree_country_code" => $arrival->getPedigreeCountryCode(),
                  "pedigree_number" => $arrival->getPedigreeNumber()),
            "location"=>
            array("id" => $arrival->getLocation()->getId(),
                  "ubn" => $arrival->getLocation()->getUbn())
        );

        return $result;
    }


    public static function createUpdateRequestArray(DeclareArrival $arrival)
    {
        //At this moment the update array is identical to post array
        return self::createPostRequestArray($arrival);
    }


}