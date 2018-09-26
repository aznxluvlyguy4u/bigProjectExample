<?php


namespace AppBundle\Util;


class Regex
{
    /**
     * @return string
     */
    public static function ulnNumber()
    {
        return '([0-9]{'.Validator::MIN_ULN_NUMBER_LENGTH.','.Validator::MAX_ULN_NUMBER_LENGTH.'})';
    }


    /**
     * @return string
     */
    public static function pedigreeNumber()
    {
        return '[A-Z0-9]{5}[-][a-zA-Z0-9]{5}';
    }


    /**
     * @return string
     */
    public static function countryCode()
    {
        return '[A-Z]{2}';
    }


    /**
     * @return string
     */
    public static function dutchPostalCode()
    {
        return '/([0-9]{4}[A-Z]{2})\b/';
    }
}