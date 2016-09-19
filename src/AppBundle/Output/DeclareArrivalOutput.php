<?php

namespace AppBundle\Output;


use AppBundle\Entity\DeclareArrival;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class DeclareArrivalOutput
 */
class DeclareArrivalOutput extends Output
{
    /**
     * @param DeclareArrival $arrival
     * @return array
     */
    public static function createPostRequestArray(ObjectManager $em, DeclareArrival $arrival)
    {
        $animal = $arrival->getAnimal();
        if($animal != null) {
            $animalId = $animal->getId();
        } else {
            $animalId = null;
        }

        self::setUbnAndLocationHealthValues($em, $arrival->getLocation());

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
                  "type" => $arrival->getAnimalObjectType()
            ),
            "location"=>
            array("id" => $arrival->getLocation()->getId(),
                  "ubn" => $arrival->getLocation()->getUbn(),  //Mandatory for IenR or use the own above
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
            "action_by" => $arrival->getActionBy()
        );

        return $result;
    }


    public static function createUpdateRequestArray(ObjectManager $em, DeclareArrival $arrival)
    {
        //At this moment the update array is identical to post array
        return self::createPostRequestArray($em, $arrival);
    }


}