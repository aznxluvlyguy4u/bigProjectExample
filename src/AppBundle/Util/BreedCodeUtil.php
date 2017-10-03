<?php


namespace AppBundle\Util;


use AppBundle\Component\Utils;
use AppBundle\Entity\Animal;
use Doctrine\DBAL\Connection;
use Symfony\Bridge\Monolog\Logger;

class BreedCodeUtil
{

    /**
     * @param string $breedCodeString
     * @param bool $isBy8Parts
     * @return bool
     */
    public static function isValidBreedCodeString($breedCodeString, $isBy8Parts = false)
    {
        $breedCodeParts = self::getBreedCodePartsFromBreedCodeString($breedCodeString, $isBy8Parts);
        return $isBy8Parts ? self::verifySumOf8PartBreedCodeParts($breedCodeParts) : self::verifySumOf100PartBreedCodeParts($breedCodeParts);
    }


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
     * @param Animal $parent1
     * @param Animal $parent2
     * @param null $nullResponse
     * @param boolean $removeSingleQuotes
     * @return null|string
     */
    public static function calculateBreedCodeFromParents($parent1, $parent2, $nullResponse = null, $removeSingleQuotes = false)
    {
        $breedCode = $nullResponse;
        if($parent1 instanceof Animal && $parent2 instanceof Animal) {
             $breedCode = self::calculateBreedCodeFromParentBreedCodes($parent1->getBreedCode(), $parent2->getBreedCode(), $nullResponse);
        }

        if($removeSingleQuotes && !$nullResponse) {
            return trim($breedCode, "'");
        }
        return $breedCode;
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

        if(self::verifySumOf100PartBreedCodeParts($fatherBreedCodeParts) && self::verifySumOf100PartBreedCodeParts($motherBreedCodeParts)) {

            $newBreedCodeParts = self::divideBreedCodeValuesInHalf($fatherBreedCodeParts, $motherBreedCodeParts);
            $newBreedCode = self::implodeBreedCodeParts($newBreedCodeParts);
            $childBreedCodeString = StringUtil::getNullAsStringOrWrapInQuotes($newBreedCode);

            return $childBreedCodeString;
        }

        return $nullResponse;
    }


    /**
     * @param Connection $conn
     * @param int $animalId
     * @param Logger $logger
     * @return bool|null
     */
    public static function updateBreedCodeBySql(Connection $conn, $animalId, Logger $logger = null)
    {
        if (!is_int($animalId) && !ctype_digit($animalId)) {
            return null;
        }

        $sql = "SELECT
                  a.id,
                  CONCAT(a.uln_country_code, a.uln_number) as uln,
                  a.parent_mother_id,
                  a.parent_father_id,
                  a.breed_code,
                  mom.breed_code as breed_code_mom,
                  dad.breed_code as breed_code_dad
                FROM
                  animal a
                  LEFT JOIN animal mom ON mom.id = a.parent_mother_id
                  LEFT JOIN animal dad ON dad.id = a.parent_father_id
                WHERE a.id = $animalId
                ";

        $results = $conn->query($sql)->fetch();

        if ($results === null) {
            return null;
        }

        $fatherBreedCodeString = ArrayUtil::get('breed_code_mom', $results);
        $motherBreedCodeString = ArrayUtil::get('breed_code_dad', $results);

        $sqlNullValue = "NULL";
        $newBreedCode = BreedCodeUtil::calculateBreedCodeFromParentBreedCodes($fatherBreedCodeString, $motherBreedCodeString, $sqlNullValue);
        $oldBreedCode = ArrayUtil::get('breed_code', $results, $sqlNullValue);
        if (is_string($oldBreedCode) && $oldBreedCode !== $sqlNullValue) {
            $oldBreedCode = "'" . $oldBreedCode . "'";
        }

        $recalculate = $newBreedCode !== $oldBreedCode;

        if ($recalculate) {
            if($logger) {
                $uln = ArrayUtil::get('uln', $results, '-');
                $logger->notice('animal_id: ' . $animalId . ', uln: '.$uln . ' | old: '.$oldBreedCode . ', new: ' . $newBreedCode);
            }
            $sql = "UPDATE animal SET breed_code = $newBreedCode WHERE id = $animalId";
            SqlUtil::updateWithCount($conn, $sql);
        }

        return $recalculate;
    }


    /**
     * @param array $breedCodeParts
     * @return bool
     */
    public static function verifySumOf8PartBreedCodeParts($breedCodeParts)
    {
        return self::verifySumOfBreedCodeParts($breedCodeParts, 8);
    }

    
    /**
     * @param array $breedCodeParts
     * @return bool
     */
    public static function verifySumOf100PartBreedCodeParts($breedCodeParts)
    {
        return self::verifySumOfBreedCodeParts($breedCodeParts, 100);
    }


    /**
     * @param array $breedCodeParts
     * @param int $totalCorrectSum
     * @return bool
     */
    private static function verifySumOfBreedCodeParts($breedCodeParts, $totalCorrectSum)
    {
        if(!is_array($breedCodeParts)) { return false; }
        if(count($breedCodeParts) == 0) { return false; }

        return array_sum($breedCodeParts) == $totalCorrectSum;
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