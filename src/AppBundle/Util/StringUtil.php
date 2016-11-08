<?php

namespace AppBundle\Util;


use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Enumerator\GenderType;

class StringUtil
{


    /**
     * Just remove the last 5 numbers of the uln
     *
     * @param string $ulnString
     * @param string $animalOrderNumberString
     * @return string
     */
    public static function getUlnWithoutOrderNumber($ulnString, $animalOrderNumberString)
    {
        $startChar = 0;
        $length = strlen($ulnString)-strlen($animalOrderNumberString);
        return mb_substr($ulnString, $startChar, $length);
    }


    /**
     * @param string $string
     * @return string
     */
    public static function getLast5CharactersFromString($string)
    {
        if(strlen($string) < 5) {
            return $string;
        } else {
            return substr($string, strlen($string)-5, strlen($string));
        }
    }


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
    
    
    public static function getUnicodeSymbol($unicodeCodePoint)
    {
        return mb_convert_encoding('&#x'.$unicodeCodePoint.';', 'UTF-8', 'HTML-ENTITIES');
    }


    /**
     * Only the first half of the pedigreeNumber is capitalized.
     * A lowercase first letter after the dash replaces the first letter of an animalOrderNumber,
     * in case an identical one already exists.
     * 
     * @param string $pedigreeNumber
     * @return string
     */
    public static function capitalizePedigreeNumber($pedigreeNumber)
    {
        if($pedigreeNumber != null) {

            $a = substr($pedigreeNumber, 0, 6); //First half including the dash
            $b = substr($pedigreeNumber, 6, 1); //First char after dash
            $c = substr($pedigreeNumber, 7, 4); //Last 4 chars

            $pedigreeNumber = strtoupper($a).$b.$c;
        }

        return $pedigreeNumber;
    }


    /**
     * @param string $classPath
     * @return string
     */
    public static function getEntityName($classPath)
    {
        $parts = explode('\\', $classPath);
        return end($parts);
    }
}