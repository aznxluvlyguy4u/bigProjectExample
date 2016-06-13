<?php

namespace AppBundle\Util;

use AppBundle\Entity\Animal;

/**
 * This class contains methods to find objects or values in given
 * arrays, Collections or ArrayCollections.
 *
 * Class Finder
 * @package AppBundle\Util
 */
class Finder
{
    /**
     * @param array $animals
     * @param string $ulnCountryCode
     * @param string $ulnNumber
     * @return Animal
     */
    public static function findAnimalByUlnValues($animals, $ulnCountryCode, $ulnNumber)
    {
        foreach($animals as $animal)
        {
            //Nested ifs are used to minimize the necessary operations
            //for the vast majority of negative matches
            if($animal->getUlnCountryCode() == $ulnCountryCode){
                if($animal->getUlnNumber() == $ulnNumber){
                    return $animal;
                }
            }
        }

        return null;
    }
}