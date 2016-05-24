<?php

namespace AppBundle\Output;


use AppBundle\Entity\Animal;
use AppBundle\Enumerator\TagStateType;

/**
 * Class DeclareAnimalDetailsOutput
 */
class AnimalDetailsOutput
{
    /**
     * @param Animal $animal
     * @return array
     */
    public static function create(Animal $animal)
    {

        $result = array(
            "animalDetails" =>
            array(
                  "uln_country_code" => $animal->getUlnCountryCode(),
                  "uln_number" => $animal->getUlnNumber(),
                  "pedigree_country_code" => $animal->getPedigreeCountryCode(),
                  "pedigree_number" => $animal->getPedigreeNumber(),
                  "work_number" => $animal->getAnimalOrderNumber(),
                  "collar_number" => "",
                  "name" => "",
                  "date_of_birth" => $animal->getDateOfBirth(),
                  "inbred_coefficient" => "",
                  "gender" => $animal->getGender(),
                  "litter_size" => "",
                  "mother" => "",
                  "father" => "",
                  "rearing" => "",
                  "suction_size" => "",
                  "blind_factor" => "",
                  "scrapie_genotype" => ""
            ),
            "exterior" =>
            array(
                "head" => "",
                "progress" => "",
                "muscularity" => "",
                "proportion" => "",
                "type" => "",
                "leg_work" => "",
                "pelt" => "",
                "general_appearance" => "",
                "height" => "",
                "breast_depth" => "",
                "torso_length" => "",
                "markings" => ""
            ),
            "measurements" =>
            array(
                "fat_cover" => "",
                "muscular_thickness" => "",
                "scan_weight" => "",
                "tail_length" => "",
                "birth_weight" => "",
                "birth_progress" => "",
            ),
            "predicate_and_statuses" =>
            array(
                "breed" => "",
                "predicate" => "",
                "status" => ""
            ),
            "breeder_information" =>
            array(
                "breeder" => "",
                "ubn_breeder" => "",
                "email_address" => "",
                "telephone" => "",
                "co-owner" => ""
            )
        );

        return $result;
    }



}