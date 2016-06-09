<?php

namespace AppBundle\Output;


use AppBundle\Entity\Animal;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;

/**
 * Class AnimalOutput
 */
class AnimalOutput
{
    /**
     * @param Ram[]|Ewe[]|Neuter[] $animals
     * @return array
     */
    public static function createAnimalsArray($animals)
    {
        $animalsArray = array();

        foreach($animals as $animal) {
            $animalsArray[] = self::createAnimalArray($animal);
        }

        return $animalsArray;
    }


    /**
     * @param Ram|Ewe|Neuter $animal
     * @return array
     */
    public static function createAnimalArray($animal)
    {

        $result = array("id" => $animal->getId(),
            "uln_country_code" => $animal->getUlnCountryCode(),
            "uln_number" => $animal->getUlnNumber(),
            "pedigree_country_code" => $animal->getPedigreeCountryCode(),
            "pedigree_number" => $animal->getPedigreeNumber(),
            "work_number" => $animal->getAnimalOrderNumber(),
//            "collar_number" => "unknown", //TODO not available in phase 1
            "gender" => $animal->getGender(),
            "date_of_birth" => $animal->getDateOfBirth(),
//            "breed_status" => "unknown", //TODO not available in phase 1
//            "inflow_date" => "unknown", //TODO not available in phase 1
            "is_alive" => $animal->getIsAlive(),
            "is_departed_animal" => $animal->getIsDepartedAnimal(),
        );

        return $result;
    }


    /**
     * @param Ram|Ewe|Neuter $animal
     * @return array
     */
    public static function createChildrenArray($animal)
    {
        $children = array();
        foreach($animal->getChildren() as $child){
            $data = array(
                "id" => $child->getId(),
                "uln_country_code" => $child->getUlnCountryCode(),
                "uln_number" => $child->getUlnNumber(),
                "pedigree_country_code" => $child->getPedigreeCountryCode(),
                "pedigree_number" => $child->getPedigreeNumber(),
                "gender" => $child->getGender()
            );
            $children[] = $data;
        }

        return $children;
    }

}