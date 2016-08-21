<?php

namespace AppBundle\Util;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Location;

class NullChecker
{


    /**
     * @param array $animalArray
     * @param string $replacementText
     * @return string
     */
    public static function getUlnOrPedigreeStringFromAnimalArray($animalArray, $replacementText = "-")
    {
        if($animalArray == null) {return $replacementText; }
        
        $uln = self::getUlnStringFromAnimalArray($animalArray, $replacementText);
        if($uln != $replacementText) {
            return $uln;
        } else {
            return self::getPedigreeStringFromAnimalArray($animalArray, $replacementText);
        }
    }


    /**
     * @param array $animalArray
     * @param string $replacementText
     * @return string
     */
    public static function getUlnStringFromAnimalArray($animalArray, $replacementText = "-")
    {
        if($animalArray == null) {return $replacementText; }
        
        $ulnCountryCode = Utils::getNullCheckedArrayValue(JsonInputConstant::ULN_COUNTRY_CODE, $animalArray);
        $ulnNumber = Utils::getNullCheckedArrayValue(JsonInputConstant::ULN_NUMBER, $animalArray);

        if($ulnCountryCode != null && $ulnCountryCode != null) {
            return $ulnCountryCode.$ulnNumber;
        } else {
            return $replacementText;
        }
    }


    /**
     * @param array $animalArray
     * @param string $replacementText
     * @return string
     */
    public static function getPedigreeStringFromAnimalArray($animalArray, $replacementText = "-")
    {
        if($animalArray == null) {return $replacementText; }
        
        $pedigreeCountryCode = Utils::getNullCheckedArrayValue(JsonInputConstant::PEDIGREE_COUNTRY_CODE, $animalArray);
        $pedigreeNumber = Utils::getNullCheckedArrayValue(JsonInputConstant::PEDIGREE_NUMBER, $animalArray);

        if($pedigreeCountryCode != null && $pedigreeCountryCode != null) {
            return $pedigreeCountryCode.$pedigreeNumber;
        } else {
            return $replacementText;
        }
    }


    /**
     * @param Location $location
     * @param string $replacementText
     * @return string
     */
    public static function getUbnFromLocation($location, $replacementText = "-")
    {
        if($location instanceof Location) {
            return $location->getUbn();
        } else {
            return $replacementText;
        }
    }
}