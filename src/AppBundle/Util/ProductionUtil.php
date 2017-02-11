<?php


namespace AppBundle\Util;


class ProductionUtil
{
    /**
     * @param $productionString
     * @return mixed|null
     */
    public static function getProductionAgeFromProductionString($productionString)
    {
        $productionAge = ArrayUtil::get(0, self::getProductionPartsFromString($productionString));
        if(ctype_digit($productionAge) || is_int($productionAge)) {
            return intval($productionAge);
        } else {
            return null;
        }
    }


    /**
     * @param $productionString
     * @return mixed|null
     */
    public static function getLitterCountFromProductionString($productionString)
    {
        $litterCount = ArrayUtil::get(1, self::getProductionPartsFromString($productionString));
        if(ctype_digit($litterCount) || is_int($litterCount)) {
            return intval($litterCount);
        } else {
            return null;
        }
    }


    /**
     * @param $productionString
     * @return int|null
     */
    public static function getTotalOffspringCountFromProductionString($productionString)
    {
        $totalOffspringCount = ArrayUtil::get(2, self::getProductionPartsFromString($productionString));
        if(ctype_digit($totalOffspringCount) || is_int($totalOffspringCount)) {
            return intval($totalOffspringCount);
        } else {
            return null;
        }
    }


    /**
     * @param $productionString
     * @return mixed|null
     */
    public static function getBornAliveCountFromProductionString($productionString)
    {
        $bornAliveCount = ArrayUtil::get(3, self::getProductionPartsFromString($productionString));
        if(ctype_digit($bornAliveCount) || is_int($bornAliveCount)) {
            return intval($bornAliveCount);
        } else {
            return null;
        }
    }
    

    /**
     * @param $productionString
     * @return array
     */
    public static function getProductionPartsFromString($productionString)
    {
        return explode('/',  rtrim($productionString, '*'));
    }


    /**
     * @param $productionString
     * @return bool
     */
    public static function hasOneYearMark($productionString)
    {
        return is_int(strpos($productionString, '*'));
    }
}