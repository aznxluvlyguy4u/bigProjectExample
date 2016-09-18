<?php

namespace AppBundle\Util;


class NumberUtil
{
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
    public static function areFloatsEqual($float1, $float2, $accuracy = 0.00001)
    {
        if(abs($float1-$float2) < $accuracy) {
            return true;
        } else {
            return false;
        }
    }
}