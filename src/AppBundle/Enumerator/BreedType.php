<?php

namespace AppBundle\Enumerator;

use AppBundle\Traits\EnumInfo;
use AppBundle\Util\Translation;

class BreedType
{
    use EnumInfo;

    const BLIND_FACTOR = "BLIND_FACTOR";             // Blindfactor
    const MEAT_LAMB_FATHER = "MEAT_LAMB_FATHER";     // Vleeslamvaderdier
    const MEAT_LAMB_MOTHER = "MEAT_LAMB_MOTHER";     // Vleeslammoederdier
    const PARENT_ANIMAL = "PARENT_ANIMAL";           // Ouderdier
    const PURE_BRED = "PURE_BRED";                   // Volbloed
    const REGISTER = "REGISTER";                     // Register
    const SECONDARY_REGISTER = "SECONDARY_REGISTER"; // Hulpboek
    const UNDETERMINED = "UNDETERMINED";             // Onbepaald
    const EN_MANAGEMENT = "EN_MANAGEMENT";
    const EN_BASIS = "EN_BASIS";


    /**
     * @return array
     */
    public static function getAllInDutch()
    {
        $results = [];
        foreach (self::getConstants() as $key => $item) {
            $results[$key] = Translation::getDutch($item);
        }
        return $results;
    }


    /**
     * @return array
     */
    static function getConstants()
    {
        $oClass = new \ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }

}