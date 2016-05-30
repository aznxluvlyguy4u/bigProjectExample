<?php

namespace AppBundle\Output;


use AppBundle\Component\Utils;
use AppBundle\Entity\DeclareBirth;

/**
 * Class DeclareBirthOutput
 */
class DeclareBirthOutput
{
    /**
     * @param DeclareBirth $birth
     * @return array
     */
    public static function createPostRequestArray(DeclareBirth $birth)
    {
        $animal = $birth->getAnimal();
        if($animal != null) {
            $animalId = $animal->getId();
        } else {
            $animalId = null;
        }

        $result = array("id" => $birth->getId(),
            "log_date" => $birth->getLogDate(),
            "message_id" => $birth->getMessageId(),
            "request_id" => $birth->getRequestId(), //Mandatory for IenR
            "request_state" => $birth->getRequestState(),
            "action" => $birth->getAction(),  //Mandatory for IenR
            "recovery_indicator" => $birth->getRecoveryIndicator(),  //Mandatory for IenR
            "relation_number_keeper" => $birth->getRelationNumberKeeper(),  //Mandatory for IenR
            "ubn" => $birth->getUbn(),  //Mandatory for IenR,

            "date_of_birth" => $birth->getDateOfBirth(),  //Mandatory for IenR
            "is_aborted" => $birth->getIsAborted(),
            "has_lambar" => $birth->getHasLambar(),
            "is_pseudo_pregnancy" => $birth->getIsPseudoPregnancy(),
            "birth_type" => $birth->getBirthType(),
            "litter_size" => $birth->getLitterSize(),
            "birth_weight" => $birth->getBirthWeight(),
            "birth_tail_length" => $birth->getBirthTailLength(),
            "type" => "DeclareBirth",
            
            "animal" =>
            array("id" => $animalId,
                  "uln_country_code" => $birth->getUlnCountryCode(),
                  "uln_number" => $birth->getAnimal()->getUlnNumber(),
                  "pedigree_country_code" => $birth->getAnimal()->getPedigreeCountryCode(),
                  "pedigree_number" => $birth->getAnimal()->getPedigreeNumber(),
                  "gender" => $birth->getAnimal()->getGender(),
                  "date_of_birth" => $birth->getDateOfBirth(),
                  "animal_order_number" => $birth->getAnimal()->getAnimalOrderNumber(),
                  "is_alive" => $birth->getAnimal()->getIsAlive(),
                  "animal_type" => $birth->getAnimal()->getAnimalType(),
                  "animal_category" => $birth->getAnimal()->getAnimalCategory(),
                  "object_type" => Utils::getClassName($birth->getAnimal()),
                  "type" => Utils::getClassName($birth->getAnimal()),
                  "parent_mother" =>
                  array(
                      "uln_country_code" => $birth->getUlnCountryCodeMother(),
                      "uln_number" => $birth->getUlnMother(),
                      "type" => "Ewe"
                  ),
                  "parent_father" =>
                  array(
                      "uln_country_code" => $birth->getUlnCountryCodeFather(),
                      "uln_number" => $birth->getUlnFather(),
                      "type" => "Ram"
                  ),
                  "surrogate" =>
                  array(
                      "uln_country_code" => $birth->getUlnCountryCodeSurrogate(),
                      "uln_number" => $birth->getUlnSurrogate(),
                      "type" => "Ewe"
                  )
            ),
            "location"=>
            array("id" => $birth->getLocation()->getId(),
                  "ubn" => $birth->getLocation()->getUbn())  //Mandatory for IenR or use the own above
        );

        return $result;
    }


    public static function createUpdateRequestArray(DeclareBirth $birth)
    {
        //At this moment the update array is identical to post array
        return self::createPostRequestArray($birth);
    }


}