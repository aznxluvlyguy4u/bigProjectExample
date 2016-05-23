<?php

namespace AppBundle\Output;


use AppBundle\Entity\DeclareDepart;

/**
 * Class DeclareDepartOutput
 */
class DeclareDepartOutput
{
    /**
     * @param DeclareDepart $depart
     * @return array
     */
    public static function createPostRequestArray(DeclareDepart $depart)
    {
        $animal = $depart->getAnimal();
        if($animal != null) {
            $animalId = $animal->getId();
        } else {
            $animalId = null;
        }

        $result = array("id" => $depart->getId(),
            "request_id" => $depart->getRequestId(),
            "message_id" => $depart->getMessageId(),
            "request_state" => $depart->getRequestState(),
            "action" => $depart->getAction(),
            "recovery_indicator" => $depart->getRecoveryIndicator(),
            "relation_number_keeper" => $depart->getRelationNumberKeeper(),
            "log_date" => $depart->getLogDate(),
            "ubn" => $depart->getUbn(),
            "depart_date" => $depart->getDepartDate(),
            "ubn_new_owner" => $depart->getUbnNewOwner(),
            "type" => "DeclareDepart",
            "animal" =>
            array("id" => $animalId,
                  "uln_country_code" => $depart->getUlnCountryCode(),
                  "uln_number" => $depart->getUlnNumber(),
                  "pedigree_country_code" => $depart->getPedigreeCountryCode(),
                  "pedigree_number" => $depart->getPedigreeNumber(),
                  "is_export_animal" => $depart->getIsExportAnimal(),
                  "is_departed_animal" => $depart->getIsDepartedAnimal(),
                  "animal_type" => $depart->getAnimalType(),
                  "type" => $depart->getAnimalObjectType()
            ),
            "location"=>
            array("id" => $depart->getLocation()->getId(),
                  "ubn" => $depart->getLocation()->getUbn())
        );

        return $result;
    }


    public static function createUpdateRequestArray(DeclareDepart $depart)
    {
        //At this moment the update array is identical to post array
        return self::createPostRequestArray($depart);
    }


}