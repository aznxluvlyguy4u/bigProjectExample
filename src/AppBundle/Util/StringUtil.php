<?php

namespace AppBundle\Util;


use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Enumerator\GenderType;

class StringUtil
{

    /**
     * @param string $firstName
     * @param string $lastName
     * @return string
     */
    public static function getFullName($firstName, $lastName)
    {
        $isFirstNameBlank = $firstName == null || $firstName == '';
        $isLastNameBlank = $lastName == null || $lastName == '';

        if(!$isFirstNameBlank && !$isLastNameBlank) {
            return $firstName.' '.$lastName;

        } elseif ($isFirstNameBlank && !$isLastNameBlank) {
            return $lastName;

        } elseif (!$isFirstNameBlank && $isLastNameBlank) {
            return $firstName;

        } else {
            //both first and last name are blank
            return '';
        }
    }

    /**
     * @param string $firstName
     * @param string $lastName
     * @param int $maxLength
     * @return string
     */
    public static function getTrimmedFullNameWithAddedEllipsis($firstName, $lastName, $maxLength)
    {
        return self::trimStringWithAddedEllipsis(
               self::getFullName($firstName, $lastName), $maxLength
        );
    }

    /**
     * @param string $string
     * @param int $maxLength
     * @return string
     */
    public static function trimStringWithAddedEllipsis($string, $maxLength)
    {
        if(strlen($string) > $maxLength) {
            return substr($string, 0, $maxLength).'...';
        } else {
            return $string;
        }
    }


    /**
     * @param string $gender
     * @return string
     */
    public static function getGenderFullyWritten($gender)
    {
        if($gender == GenderType::M || $gender == GenderType::MALE) {
            return GenderType::MALE;
        } elseif($gender == GenderType::V || $gender == GenderType::FEMALE) {
            return GenderType::FEMALE;
        } elseif($gender == GenderType::O || $gender == GenderType::NEUTER) {
            return GenderType::NEUTER;
        } else {
            return $gender;
        }
    }


    /**
     * The PedigreeCode/STN format in csv files is: "XX 12AB3-67890" or "XX 123456789012".
     * There is always a space between the country code (XX) and the rest of the code (numbers and possible some letters).
     *
     * @param string $csvPedigreeCode
     * @return array|null
     */
    public static function getStnFromCsvFileString($csvPedigreeCode)
    {
        if($csvPedigreeCode == '' || $csvPedigreeCode == null) { return null; }
        elseif(strlen($csvPedigreeCode) < 4) { return null; }
        
        if(strpos($csvPedigreeCode, ' ') !== false) {
            $stnParts = explode(' ', $csvPedigreeCode);
            $countryCode = $stnParts[0];
            $number = str_replace('-', '', $stnParts[1]);

        } else {
            //if the countryCode and number are not separated by a space
            $countryCode = mb_substr($csvPedigreeCode, 0, 2, 'utf-8');
            $number = str_replace('-', '', mb_substr($csvPedigreeCode, 2, strlen($csvPedigreeCode)));
        }

        return array(JsonInputConstant::PEDIGREE_COUNTRY_CODE => $countryCode, JsonInputConstant::PEDIGREE_NUMBER => $number);
    }


    /**
     * Change string with dateFormat of MM/DD/YYYY to YYYY-MM-DD
     *
     * @param string $americanDate
     * @return string
     */
    public static function changeDateFormatStringFromAmericanToISO($americanDate)
    {
        $dateParts = explode('/', $americanDate);
        return $dateParts[2].'-'.$dateParts[0].'-'.$dateParts[1];
    }


    /**
     * @param $haystack
     * @param $needle
     * @return bool
     */
    public static function isStringContains($haystack, $needle)
    {
        if (strpos($haystack, $needle) !== FALSE) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * @param boolean $boolean
     * @return string
     */
    public static function getBooleanAsString($boolean)
    {
        return ($boolean) ? 'true' : 'false';
    }


    /**
     * @param $stringOrArray
     * @return string|array|null
     */
    public static function replaceMultipleSpacesByOne($stringOrArray)
    {
        return preg_replace('!\s+!', ' ', $stringOrArray);
    }
}