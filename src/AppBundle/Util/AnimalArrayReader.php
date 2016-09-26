<?php

namespace AppBundle\Util;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\ReportLabel;

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
}