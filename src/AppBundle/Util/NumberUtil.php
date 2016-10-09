<?php

namespace AppBundle\Util;


class NumberUtil
{
    const DEFAULT_FLOAT_ACCURACY = 0.00001;

    /**
     * @param float $number
     * @return int
     */
    public static function getDecimalCount($number)
    {
        return ( (int) $number != $number ) ? (strlen($number) - strpos($number, '.')) - 1 : 0;
    }


    /**
     * @param float $number
     * @return bool
     */
    public static function hasDecimals($number)
    {
        return self::getDecimalCount($number) > 0;
    }


    /**
     * @param float $float1
     * @param float $float2
     * @param float $accuracy
     * @return boolean
     */
    public static function areFloatsEqual($float1, $float2, $accuracy = self::DEFAULT_FLOAT_ACCURACY)
    {
        if(abs($float1-$float2) < $accuracy) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * @param $float1
     * @param float $accuracy
     * @return bool
     */
    public static function isFloatZero($float1, $accuracy = self::DEFAULT_FLOAT_ACCURACY)
    {
        if($float1 == null) {
            return true;
        } elseif (NumberUtil::areFloatsEqual($float1, 0, $accuracy)) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * @param $string
     * @return mixed
     */
    public static function replaceCommaByDot($string)
    {
        return str_replace(',','.',$string);
    }


    /**
     * @param $number
     * @return string
     */
    public static function getPlusSignIfNumberIsPositive($number)
    {
        if($number > 0) {
            return '+';
        } else {
            return '';
        }
    }
}