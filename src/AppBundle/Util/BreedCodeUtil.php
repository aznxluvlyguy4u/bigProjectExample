<?php


namespace AppBundle\Util;


use AppBundle\Component\Utils;

class BreedCodeUtil
{

    /**
     * @param string $breedCodeString
     * @return array
     */
    public static function getBreedCodePartsFromBreedCodeString($breedCodeString)
    {
        $parts = Utils::separateLettersAndNumbersOfString($breedCodeString);
        $result = [];
        $counter = 0;

        $totalCount = count($parts);

        if(NumberUtil::isOdd($totalCount)) {
            //Incorrect breedCode
            return $result;
        }

        for ($i = 0; $i < $totalCount-1; $i += 2) {
            $result[$parts[$i]] = $parts[$i+1];
        }

        return $result;
    }
}