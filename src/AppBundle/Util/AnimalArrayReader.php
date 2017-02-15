<?php

namespace AppBundle\Util;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\Enumerator\GenderType;

class AnimalArrayReader
{
    /**
     * @param array $animalArray
     * @return array
     */
    public static function readUlnOrPedigree($animalArray)
    {
        $ulnCountryCode = Utils::getNullCheckedArrayValue(JsonInputConstant::ULN_COUNTRY_CODE, $animalArray);
        $ulnNumber = Utils::getNullCheckedArrayValue(JsonInputConstant::ULN_NUMBER, $animalArray);
        if ($ulnCountryCode != null && $ulnNumber != null) {
            return array(   Constant::TYPE_NAMESPACE => Constant::ULN_NAMESPACE,
                 JsonInputConstant::ULN_COUNTRY_CODE => $animalArray[JsonInputConstant::ULN_COUNTRY_CODE],
                       JsonInputConstant::ULN_NUMBER => $animalArray[JsonInputConstant::ULN_NUMBER]);
        }


        $pedigreeCountryCode = Utils::getNullCheckedArrayValue(JsonInputConstant::PEDIGREE_COUNTRY_CODE, $animalArray);
        $pedigreeNumber = Utils::getNullCheckedArrayValue(JsonInputConstant::PEDIGREE_NUMBER, $animalArray);
        if ($pedigreeCountryCode != null && $pedigreeNumber != null) {

            return array(   Constant::TYPE_NAMESPACE => Constant::PEDIGREE_NAMESPACE,
            JsonInputConstant::PEDIGREE_COUNTRY_CODE => $animalArray[JsonInputConstant::PEDIGREE_COUNTRY_CODE],
                  JsonInputConstant::PEDIGREE_NUMBER => $animalArray[JsonInputConstant::PEDIGREE_NUMBER]);
        }

        return array(   Constant::TYPE_NAMESPACE => null,
             JsonInputConstant::ULN_COUNTRY_CODE => null,
                   JsonInputConstant::ULN_NUMBER => null);
    }


    /**
     * @param $animalArray
     * @param string $separator
     * @return null|string
     */
    public static function getIdString($animalArray, $separator = '')
    {
        $ulnCountryCode = Utils::getNullCheckedArrayValue(JsonInputConstant::ULN_COUNTRY_CODE, $animalArray);
        $ulnNumber = Utils::getNullCheckedArrayValue(JsonInputConstant::ULN_NUMBER, $animalArray);
        if ($ulnCountryCode != null && $ulnNumber != null) {
            return $ulnCountryCode.$separator.$ulnNumber;
        }
        
        $pedigreeCountryCode = Utils::getNullCheckedArrayValue(JsonInputConstant::PEDIGREE_COUNTRY_CODE, $animalArray);
        $pedigreeNumber = Utils::getNullCheckedArrayValue(JsonInputConstant::PEDIGREE_NUMBER, $animalArray);
        if ($pedigreeCountryCode != null && $pedigreeNumber != null) {
            return $pedigreeCountryCode.$separator.$pedigreeNumber;
        }

        return null;
    }


    /**
     * @param array $animalArray
     * @param string $separator
     * @return array
     */
    public static function getUlnAndPedigreeInArray($animalArray, $separator = '')
    {
        $animal = array();

        $ulnCountryCode = Utils::getNullCheckedArrayValue(JsonInputConstant::ULN_COUNTRY_CODE, $animalArray);
        $ulnNumber = Utils::getNullCheckedArrayValue(JsonInputConstant::ULN_NUMBER, $animalArray);
        if ($ulnCountryCode != null && $ulnNumber != null) {
            $animal[ReportLabel::ULN] = $ulnCountryCode.$separator.$ulnNumber;
        }

        $pedigreeCountryCode = Utils::getNullCheckedArrayValue(JsonInputConstant::PEDIGREE_COUNTRY_CODE, $animalArray);
        $pedigreeNumber = Utils::getNullCheckedArrayValue(JsonInputConstant::PEDIGREE_NUMBER, $animalArray);
        if ($pedigreeCountryCode != null && $pedigreeNumber != null) {
            $animal[ReportLabel::PEDIGREE] = $pedigreeCountryCode.$separator.$pedigreeNumber;
        }

        return $animal;
    }

    
    /**
     * @param array $animals
     * @return null|Animal|Ram|Ewe|Neuter
     */
    public static function prioritizeImportedAnimalFromArray($animals)
    {
        $count = count($animals);
        if($count == 1) {
            return $animals[0];

        } elseif($count > 1) {

            $lowestAnimalId = self::getLowestAnimalId($animals);

            /** @var Animal $animal */
            foreach ($animals as $animal) {
                //Prioritize imported animal, based on vsmId saved in name column
                if($animal->getName() != null) { return $animal;
                    //Then prioritize non-Neuter animal
                } elseif($animal->getGender() != GenderType::NEUTER && $animal->getGender() != GenderType::O) { return $animal;
                    //Then prioritize Animal with lowest id
                } elseif($animal->getId() == $lowestAnimalId) { return $animal; }
            }
            //By default return the first Animal
            return $animals[0];

        } else { //count == 0
            return null;
        }
    }


    /**
     * @param $animals
     * @return int|null
     */
    public static function getLowestAnimalId($animals)
    {
        if(count($animals) == 0) { return null; }
        /** @var Animal $firstAnimal */
        $firstAnimal = $animals[0];
        $lowestId = $firstAnimal->getId();

        /** @var Animal $animal */
        foreach ($animals as $animal) {
            if($animal->getId() < $lowestId) { $lowestId = $animal->getId(); }
        }
        return $lowestId;
    }


    /**
     * @param $array
     * @param null $nullReplacement
     * @return null|string
     */
    public static function getUlnFromArray($array, $nullReplacement = null)
    {
        $ulnCountryCode = ArrayUtil::get(JsonInputConstant::ULN_COUNTRY_CODE, $array);
        $ulnNumber = ArrayUtil::get(JsonInputConstant::ULN_NUMBER,$array);
        
        if($ulnCountryCode != null && $ulnNumber != null) {
            return $ulnCountryCode.$ulnNumber;
        }
        return $nullReplacement;
    }
}