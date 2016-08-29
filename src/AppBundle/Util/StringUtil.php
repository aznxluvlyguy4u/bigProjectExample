<?php

namespace AppBundle\Util;


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
}