<?php


namespace AppBundle\Constant;


use AppBundle\Util\StringUtil;

class UnicodeSymbol
{
//    public static function FEMALE() { return mb_convert_encoding('&#x2640;', 'UTF-8', 'HTML-ENTITIES'); }
    public static function FEMALE() { return StringUtil::getUnicodeSymbol('2640'); }
    public static function MALE() { return StringUtil::getUnicodeSymbol('2642'); }
    public static function MALE_AND_FEMALE() { return StringUtil::getUnicodeSymbol('26A5'); }
    public static function NEUTER() { return StringUtil::getUnicodeSymbol('26B2'); }
}