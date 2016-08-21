<?php

namespace AppBundle\Util;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Location;

class NullChecker
{

    /**
     * @param $array
     * @return int
     */
    public static function getArrayCount($array)
    {
        if($array != null) {
            return sizeof($array);
        } else {
            return 0;
        }
    }
    
    /**
     * @param array $array
     * @param string $replacementText
     * @return string
     */
    public static function getUlnOrPedigreeStringFromArray($array, $replacementText = "-")
    {
        if($array == null) {return $replacementText; }
        
        $uln = self::getUlnStringFromArray($array, $replacementText);
        if($uln != $replacementText) {
            return $uln;
        } else {
            return self::getPedigreeStringFromArray($array, $replacementText);
        }
    }


    /**
     * @param array $array
     * @param string $replacementText
     * @return string
     */
    public static function getUlnStringFromArray($array, $replacementText = "-")
    {
        if($array == null) {return $replacementText; }
        
        $ulnCountryCode = Utils::getNullCheckedArrayValue(JsonInputConstant::ULN_COUNTRY_CODE, $array);
        $ulnNumber = Utils::getNullCheckedArrayValue(JsonInputConstant::ULN_NUMBER, $array);

        if($ulnCountryCode != null && $ulnCountryCode != null) {
            return $ulnCountryCode.$ulnNumber;
        } else {
            return $replacementText;
        }
    }


    /**
     * @param array $array
     * @param string $replacementText
     * @return string
     */
    public static function getPedigreeStringFromArray($array, $replacementText = "-")
    {
        if($array == null) {return $replacementText; }
        
        $pedigreeCountryCode = Utils::getNullCheckedArrayValue(JsonInputConstant::PEDIGREE_COUNTRY_CODE, $array);
        $pedigreeNumber = Utils::getNullCheckedArrayValue(JsonInputConstant::PEDIGREE_NUMBER, $array);

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