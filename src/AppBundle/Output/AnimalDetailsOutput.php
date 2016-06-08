<?php

namespace AppBundle\Output;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;

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
                  Constant::ULN_COUNTRY_CODE_NAMESPACE => $animal->getUlnCountryCode(),
                  Constant::ULN_NUMBER_NAMESPACE => $animal->getUlnNumber(),
                  Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE => $animal->getPedigreeCountryCode(),
                  Constant::PEDIGREE_NUMBER_NAMESPACE => $animal->getPedigreeNumber(),
                  JsonInputConstant::WORK_NUMBER => $animal->getAnimalOrderNumber(),
                  "collar_number" => "",
                  "name" => "",
                  Constant::DATE_OF_BIRTH_NAMESPACE => $animal->getDateOfBirth(),
                  "inbred_coefficient" => "",
                  Constant::GENDER_NAMESPACE => $animal->getGender(),
                  "litter_size" => "",
                  Constant::MOTHER_NAMESPACE => Utils::getUlnStringFromAnimal($animal->getParentMother()),
                  Constant::FATHER_NAMESPACE => Utils::getUlnStringFromAnimal($animal->getParentFather()),
                  "rearing" => "",
                  "suction_size" => "",
                  "blind_factor" => "",
                  "scrapie_genotype" => "",
                  "breed" => "",
                  "predicate" => "",
                  "breed_status" => "",
                  JsonInputConstant::IS_ALIVE => $animal->getIsAlive(),
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
                "measurement" =>
                    array(
                        "fat_cover" => "",
                        "muscular_thickness" => "",
                        "scan_weight" => "",
                        "tail_length" => "",
                        "birth_weight" => "",
                        "birth_progress" => "",
                        "withers" => "",
                        "breast_depth" => "",
                        "torso_length" => ""
                    ),
                "breeder" =>
                    array(
                        "breeder" => "",
                        "ubn_breeder" => "",
                        "email_address" => "",
                        "telephone" => "",
                        "co-owner" => ""
                    ),
                "note" => ""
        );

        return $result;
    }



}