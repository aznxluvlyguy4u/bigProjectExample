<?php


namespace AppBundle\Util;


class Regex
{
    /**
     * @return string
     */
    public static function getUlnNumberRegex()
    {
        return '([0-9]{'.Validator::MIN_ULN_NUMBER_LENGTH.','.Validator::MAX_ULN_NUMBER_LENGTH.'})';
    }
}