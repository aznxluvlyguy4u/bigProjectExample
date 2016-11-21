<?php


namespace AppBundle\Util;


use AppBundle\Component\Utils;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;

class BreedCodeUtil
{
    /** @var  ObjectManager */
    private $em;

    /** @var Connection */
    private $conn;

    /** @var array */
    private $breedCodesByVsmId;

    /** @var array */
    private $fathers;

    /** @var array */
    private $mothers;

    /** @var array */
    private $animalIdByVsmId;

    /** @var CommandUtil */
    private $cmdUtil;

    public function __construct(ObjectManager $em, CommandUtil $cmdUtil = null)
    {
        $this->em = $em;
        $this->breedCodesByVsmId = [];
        $this->fathers = [];
        $this->mothers = [];
        $this->animalIdByVsmId = [];
        $this->conn = $this->em->getConnection();
        $this->cmdUtil = $cmdUtil;
    }


    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function fixBreedCodes()
    {
        $sql = "SELECT id, vsm_id, breed_code, father_vsm_id, mother_vsm_id, is_breed_code_updated FROM animal_migration_table";
        $results = $this->conn->query($sql)->fetchAll();

        if($this->cmdUtil != null) { $this->cmdUtil->setStartTimeAndPrintIt(count($results), 1); }

        //Create searchArrays
        foreach ($results as $result) {
            $breedCodeString = $result['breed_code'];
            $vsmId = $result['vsm_id'];
            $this->breedCodesByVsmId[$vsmId] = $breedCodeString;
            $this->fathers[$vsmId] = $result['father_vsm_id'];
            $this->mothers[$vsmId] = $result['mother_vsm_id'];
            $this->animalIdByVsmId[$vsmId] = $result['id'];
        }

        $alreadyProcessedBreedCodes = 0;
        $correctBreedCodesSkipped = 0;
        $incorrectBreedCodesProcessed = 0;

        foreach ($results as $result) {
            if($result['is_breed_code_updated']) {
                if($this->cmdUtil != null) { $this->cmdUtil->advanceProgressBar(1, 'Already done: '.$alreadyProcessedBreedCodes.' | Correct Skipped: '.$correctBreedCodesSkipped
                    .' | incorrect processed (including parents): '.$incorrectBreedCodesProcessed); }
                $alreadyProcessedBreedCodes++;
                continue;
            }

            $breedCodeString = $result['breed_code'];
            $parts = Utils::separateLettersAndNumbersOfString($breedCodeString);
            $isValidBreedCode = BreedCodeUtil::verifySumOfBreedCodeParts($parts);
            if($isValidBreedCode) {
                $sql = "UPDATE animal_migration_table SET is_breed_code_updated = TRUE WHERE id = ". $result['id'];
                $this->conn->exec($sql);
                if($this->cmdUtil != null) { $this->cmdUtil->advanceProgressBar(1, 'Already done: '.$alreadyProcessedBreedCodes.' | Correct Skipped: '.$correctBreedCodesSkipped
                    .' | incorrect processed (including parents): '.$incorrectBreedCodesProcessed); }
                $correctBreedCodesSkipped++;
                continue;
            }

            $vsmId = $result['vsm_id'];
            $this->calculateBreedCodeFromParentsAndPersistNewValue($vsmId);
            $incorrectBreedCodesProcessed++;

            if($this->cmdUtil != null) { $this->cmdUtil->advanceProgressBar(1, 'Already done: '.$alreadyProcessedBreedCodes.' | Correct Skipped: '.$correctBreedCodesSkipped
                .' | incorrect processed (including parents): '.$incorrectBreedCodesProcessed); }
        }
        if($this->cmdUtil != null) {
            $this->cmdUtil->setProgressBarMessage('Already done: '.$alreadyProcessedBreedCodes.' | Correct Skipped: '.$correctBreedCodesSkipped
                .' | incorrect processed (including parents): '.$incorrectBreedCodesProcessed);
            $this->cmdUtil->setEndTimeAndPrintFinalOverview();
        }
    }


    /**
     * @param int $vsmId
     * @return array
     */
    private function calculateBreedCodeFromParentsAndPersistNewValue($vsmId)
    {
        $fatherVsmId = $this->fathers[$vsmId];
        $motherVsmId = $this->mothers[$vsmId];

        $breedCodeStringOfFather = Utils::getNullCheckedArrayValue($fatherVsmId, $this->breedCodesByVsmId);
        $breedCodePartsOfFather = Utils::separateLettersAndNumbersOfString($breedCodeStringOfFather);
        $breedCodeStringOfMother = Utils::getNullCheckedArrayValue($motherVsmId, $this->breedCodesByVsmId);
        $breedCodePartsOfMother = Utils::separateLettersAndNumbersOfString($breedCodeStringOfMother);

        //Two recursive loops to find the breedCodeValues of the parents
        if(!self::verifySumOfBreedCodeParts($breedCodePartsOfFather)) {
            $breedCodePartsOfFather = $this->calculateBreedCodeFromParentsAndPersistNewValue($fatherVsmId);
            $this->breedCodesByVsmId[$fatherVsmId] = self::implodeBreedCodeParts($breedCodePartsOfFather); //Update search array
        }

        if(!self::verifySumOfBreedCodeParts($breedCodePartsOfMother)) {
            $breedCodePartsOfMother = $this->calculateBreedCodeFromParentsAndPersistNewValue($motherVsmId);
            $this->breedCodesByVsmId[$motherVsmId] = self::implodeBreedCodeParts($breedCodePartsOfMother); //Update search array
        }

        if(self::verifySumOfBreedCodeParts($breedCodePartsOfFather) && self::verifySumOfBreedCodeParts($breedCodePartsOfMother)) {

            $newBreedCodeParts = self::divideBreedCodeValuesInHalf($breedCodePartsOfFather, $breedCodePartsOfMother);
            $newBreedCode = self::implodeBreedCodeParts($newBreedCodeParts);
            $newBreedCodeForSql = StringUtil::getNullAsStringOrWrapInQuotes($newBreedCode);

            $sql = "UPDATE animal_migration_table SET is_breed_code_updated = TRUE, old_breed_code = breed_code, breed_code = ".$newBreedCodeForSql." WHERE id = ". $this->animalIdByVsmId[$vsmId];
            $this->conn->exec($sql);

            return $newBreedCodeParts;
        }
        return null;
    }


    /**
     * Both parents must have a known breedCode
     *
     * @param array $breedCodePartsParent1
     * @param array $breedCodePartsParent2
     * @return array
     */
    public static function divideBreedCodeValuesInHalf($breedCodePartsParent1, $breedCodePartsParent2)
    {
        if(!is_array($breedCodePartsParent1) || !is_array($breedCodePartsParent2)) { return null; }
        if(count($breedCodePartsParent1) == 0 || count($breedCodePartsParent1) == 0) { return null; }

        $totalParts = [];

        //Merge values
        foreach ([$breedCodePartsParent1, $breedCodePartsParent2] as $breedCodeParts) {
            $count1 = count($breedCodeParts);
            for($i = 0; $i < $count1-1; $i += 2) {
                if(ctype_alpha($breedCodeParts[$i]) && ctype_alnum($breedCodeParts[$i+1])) {
                    $breedCode = $breedCodeParts[$i];
                    $number = intval($breedCodeParts[$i+1]);

                    if(array_key_exists($breedCode, $totalParts)) {
                        $totalParts[$breedCode] = $totalParts[$breedCode] + $number;
                    } else {
                        $totalParts[$breedCode] = $number;
                    }
                }
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
    

    /**
     * @param $breedCodeString
     * @return bool
     */
    public static function verifySumOfBreedCode($breedCodeString)
    {
        $parts = Utils::separateLettersAndNumbersOfString($breedCodeString);
        return self::verifySumOfBreedCodeParts($parts);
    }


    /**
     * @param array $breedCodeParts
     * @return bool
     */
    public static function verifySumOfBreedCodeParts($breedCodeParts)
    {
        if(!is_array($breedCodeParts)) { return false; }

        $count = count($breedCodeParts);
        if($count == 0) { return false; }

        $sumOfNumbers = 0;

        for($i = 0; $i < $count-1; $i += 2) {
            if(ctype_alpha($breedCodeParts[$i]) && ctype_alnum($breedCodeParts[$i+1])) {
                $breedCode = $breedCodeParts[$i];
                $number = $breedCodeParts[$i+1];
                $sumOfNumbers += $number;
            }
        }

        return $sumOfNumbers == 100;
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
}