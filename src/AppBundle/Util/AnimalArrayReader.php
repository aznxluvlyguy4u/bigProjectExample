<?php

namespace AppBundle\Util;


use AppBundle\Constant\Constant;

class AnimalArrayReader
{
    /**
     * @param array $animalArray
     * @return array
     */
    public static function readUlnOrPedigree($animalArray)
    {
        if(array_key_exists(Constant::ULN_COUNTRY_CODE_NAMESPACE, $animalArray) && array_key_exists(Constant::ULN_NUMBER_NAMESPACE, $animalArray)) {
            if( ($animalArray[Constant::ULN_COUNTRY_CODE_NAMESPACE] != null && $animalArray[Constant::ULN_COUNTRY_CODE_NAMESPACE] != "" )
                && ($animalArray[Constant::ULN_NUMBER_NAMESPACE] != null && $animalArray[Constant::ULN_NUMBER_NAMESPACE] != "" ) ) {

                return array(            Constant::TYPE_NAMESPACE => Constant::ULN_NAMESPACE,
                             Constant::ULN_COUNTRY_CODE_NAMESPACE => $animalArray[Constant::ULN_COUNTRY_CODE_NAMESPACE],
                                   Constant::ULN_NUMBER_NAMESPACE => $animalArray[Constant::ULN_NUMBER_NAMESPACE]);
            }

        }

        //Don't use a single 'else if' here, because this is preceded by a double/nested if block
        if (array_key_exists(Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE, $animalArray) && array_key_exists(Constant::PEDIGREE_NUMBER_NAMESPACE, $animalArray)) {
            if (($animalArray[Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE] != null && $animalArray[Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE] != "")
                && ($animalArray[Constant::PEDIGREE_NUMBER_NAMESPACE] != null && $animalArray[Constant::PEDIGREE_NUMBER_NAMESPACE] != "") ) {

                return array(        Constant::TYPE_NAMESPACE => Constant::PEDIGREE_NAMESPACE,
                    Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE => $animalArray[Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE],
                          Constant::PEDIGREE_NUMBER_NAMESPACE => $animalArray[Constant::PEDIGREE_NUMBER_NAMESPACE]);
            }
        }

        return array(   Constant::TYPE_NAMESPACE => null,
            Constant::ULN_COUNTRY_CODE_NAMESPACE => null,
                  Constant::ULN_NUMBER_NAMESPACE => null);
    }
}