<?php


namespace AppBundle\Util;


use AppBundle\Component\Utils;

class BreedCodeUtil
{

    /**
     * @param $breedCodeString
     * @return array
     */
    public static function getBreedCodeAs8PartsFromBreedCodeString($breedCodeString)
    {
        return self::getBreedCodePartsFromBreedCodeString($breedCodeString, true);
    }


    /**
     * @param string $breedCodeString
     * @param boolean $isBy8Parts
     * @return array
     */
    public static function getBreedCodePartsFromBreedCodeString($breedCodeString, $isBy8Parts = false)
    {
        $parts = Utils::separateLettersAndNumbersOfString($breedCodeString);
        $result = [];
        $counter = 0;

        $totalCount = count($parts);

        if(NumberUtil::isOdd($totalCount)) {
            //Incorrect breedCode
            return $result;
        }

        if($isBy8Parts) {
            for ($i = 0; $i < $totalCount-1; $i += 2) {
                $result[$parts[$i]] = intval(round(intval($parts[$i+1])*8.0/100.0));
            }
        } else {
            for ($i = 0; $i < $totalCount-1; $i += 2) {
                $result[$parts[$i]] = intval($parts[$i+1]);
            }
        }

        return $result;
    }


    /**
     * @param string $fatherBreedCodeString
     * @param string $motherBreedCodeString
     * @param mixed $nullResponse
     * @return string|null
     */
    public static function calculateBreedCodeFromParentBreedCodes($fatherBreedCodeString, $motherBreedCodeString, $nullResponse = null)
    {
        $fatherBreedCodeParts = self::getBreedCodePartsFromBreedCodeString($fatherBreedCodeString);
        $motherBreedCodeParts = self::getBreedCodePartsFromBreedCodeString($motherBreedCodeString);

        if(self::verifySumOfBreedCodeParts($fatherBreedCodeParts) && self::verifySumOfBreedCodeParts($motherBreedCodeParts)) {

            $newBreedCodeParts = self::divideBreedCodeValuesInHalf($fatherBreedCodeParts, $motherBreedCodeParts);
            $newBreedCode = self::implodeBreedCodeParts($newBreedCodeParts);
            $childBreedCodeString = StringUtil::getNullAsStringOrWrapInQuotes($newBreedCode);

            return $childBreedCodeString;
        }

        return $nullResponse;
    }


    /**
     * @param array $breedCodeParts
     * @return bool
     */
    public static function verifySumOfBreedCodeParts($breedCodeParts)
    {
        if(!is_array($breedCodeParts)) { return false; }
        if(count($breedCodeParts) == 0) { return false; }

        return array_sum($breedCodeParts) == 100;
    }

    /**
     * Both parents must have a known breedCode
     *
     * @param array $breedCodePartsParent1
     * @param array $breedCodePartsParent2
     * @param mixed $nullResponse
     * @return array
     */
    public static function divideBreedCodeValuesInHalf($breedCodePartsParent1, $breedCodePartsParent2, $nullResponse = null)
    {
        if(!is_array($breedCodePartsParent1) || !is_array($breedCodePartsParent2)) { return $nullResponse; }
        if(count($breedCodePartsParent1) == 0 || count($breedCodePartsParent1) == 0) { return $nullResponse; }

        $totalParts = [];

        //Merge values
        foreach ([$breedCodePartsParent1, $breedCodePartsParent2] as $breedCodeParts) {
            foreach ($breedCodeParts as $breedCode => $value) {
                $breedCodeSum = ArrayUtil::get($breedCode, $totalParts, 0) + intval($value);
                $totalParts[$breedCode] = $breedCodeSum;
            }
        }

        //Divide values
        ksort($totalParts); //Sort the codes alphabetically, secondary
        arsort($totalParts);//Sort by number value, primary
        $breedCodes = array_keys($totalParts);

        $roundingError = 100;
        foreach ($breedCodes as $breedCode) {
            $number = $totalParts[$breedCode];
            $newHalvedNumber = self::divideBreedCodeNumberInHalf($number);
            $totalParts[$breedCode] = $newHalvedNumber;
            $roundingError = $roundingError - $newHalvedNumber;
        }

        //Fix rounding errors
        if($roundingError != 0) {
            //Add the roundingError to the highest one in the set
            $totalParts[$breedCodes[0]] = $totalParts[$breedCodes[0]] + $roundingError;
            ksort($totalParts); //Sort the codes alphabetically, secondary
            arsort($totalParts);//Sort by number value, primary
        }

        return $totalParts;
    }


    /**
     * @param $integer
     * @param int $partsBy
     * @return int
     */
    public static function divideBreedCodeNumberInHalf($integer, $partsBy = 100)
    {
        $halfFloat = floatval(intval($integer))/2;
        if(!NumberUtil::hasDecimals($halfFloat)) {
            return intval($halfFloat);
        } else {
            return self::roundBreedCodeNumber($halfFloat, $partsBy);
        }
    }


    /**
     * @param float|int $number
     * @param int $partsBy
     * @return int
     */
    public static function roundBreedCodeNumber($number, $partsBy = 100)
    {
        if($number > $partsBy/2) {
            return intval(round($number, 0, PHP_ROUND_HALF_UP));
        } else {
            return intval(round($number, 0, PHP_ROUND_HALF_DOWN));
        }
    }


    /**
     * @param array $totalParts
     * @return string
     */
    public static function implodeBreedCodeParts($totalParts)
    {
        $breedCodes = array_keys($totalParts);
        //Generate string
        $breedCodeString = '';
        foreach ($breedCodes as $breedCode) {
            $breedCodeString = $breedCodeString.$breedCode.$totalParts[$breedCode];
        }
        return $breedCodeString;
    }
}