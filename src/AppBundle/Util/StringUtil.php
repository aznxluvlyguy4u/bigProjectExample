<?php

namespace AppBundle\Util;


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
}