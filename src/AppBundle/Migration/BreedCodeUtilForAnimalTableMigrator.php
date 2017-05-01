<?php


namespace AppBundle\Util;


use AppBundle\Component\Utils;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;

class BreedCodeUtilForAnimalTableMigrator
{
    const NESTING_LEVEL_LIMIT = 255;

    /** @var  ObjectManager */
    private $em;

    /** @var Connection */
    private $conn;

    /** @var array */
    private $breedCodesByVsmId;

    /** @var array */
    private $updatedBreedCodes;

    /** @var array */
    private $fathers;

    /** @var array */
    private $mothers;

    /** @var array */
    private $migrationTableIdByVsmId;

    /** @var CommandUtil */
    private $cmdUtil;

    public function __construct(ObjectManager $em, CommandUtil $cmdUtil = null)
    {
        $this->em = $em;
        $this->breedCodesByVsmId = [];
        $this->updatedBreedCodes = [];
        $this->fathers = [];
        $this->mothers = [];
        $this->migrationTableIdByVsmId = [];
        $this->conn = $this->em->getConnection();
        $this->cmdUtil = $cmdUtil;
    }


    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function fixBreedCodes()
    {
        $sql = "SELECT id, vsm_id, breed_code, father_vsm_id, mother_vsm_id, is_breed_code_updated FROM animal_migration_table ORDER BY date_of_birth";
        $results = $this->conn->query($sql)->fetchAll();

        if($this->cmdUtil != null) { $this->cmdUtil->setStartTimeAndPrintIt(count($results), 1); }

        //Create searchArrays
        foreach ($results as $result) {
            $vsmId = $result['vsm_id'];

            $breedCodeString = $result['breed_code'];
            if($breedCodeString != null && $breedCodeString != ''){
                $this->breedCodesByVsmId[$vsmId] = $breedCodeString;
            }

            $isBreedCodeUpdated = boolval($result['is_breed_code_updated']);
            if($isBreedCodeUpdated){
                $this->updatedBreedCodes[$vsmId] = $vsmId;
            }

            $fatherVsmId = $result['father_vsm_id'];
            if($fatherVsmId != null && $fatherVsmId != 0 && $fatherVsmId != ''){
                $this->fathers[$vsmId] = $fatherVsmId;
            }

            $motherVsmId = $result['mother_vsm_id'];
            if($motherVsmId != null && $motherVsmId != 0 && $motherVsmId != ''){
                $this->mothers[$vsmId] = $motherVsmId;
            }

            $migrationTableId = $result['id'];
            if($migrationTableId != null && $migrationTableId != 0 && $migrationTableId != ''){
                $this->migrationTableIdByVsmId[$vsmId] = $migrationTableId;
            }
        }

        $alreadyProcessedBreedCodes = 0;
        $correctBreedCodesSkipped = 0;
        $incorrectBreedCodesProcessed = 0;
        $emptyBreedCodesProcessed = 0;

        foreach ($results as $result) {
            if($result['is_breed_code_updated']) {
                $alreadyProcessedBreedCodes++;
                if($this->cmdUtil != null) { $this->cmdUtil->advanceProgressBar(1, 'BreedCodesFix - AlreadyDone|CorrectSkipped|Empty|IncorrectProcessed(including parents): '.$alreadyProcessedBreedCodes.'|'.$correctBreedCodesSkipped.'|'.$emptyBreedCodesProcessed
                    .'|'.$incorrectBreedCodesProcessed); }
                continue;
            }

            $breedCodeString = $result['breed_code'];
            $parts = Utils::separateLettersAndNumbersOfString($breedCodeString);
            $isValidBreedCode = BreedCodeUtilForAnimalTableMigrator::verifySumOfBreedCodeParts($parts);
            if($isValidBreedCode) {
                $sql = "UPDATE animal_migration_table SET is_breed_code_updated = TRUE WHERE id = ". $result['id'];
                $this->conn->exec($sql);
                $correctBreedCodesSkipped++;
                if($this->cmdUtil != null) { $this->cmdUtil->advanceProgressBar('BreedCodesFix - AlreadyDone|CorrectSkipped|Empty|IncorrectProcessed(including parents): '.$alreadyProcessedBreedCodes.'|'.$correctBreedCodesSkipped.'|'.$emptyBreedCodesProcessed
                    .'|'.$incorrectBreedCodesProcessed); }
                continue;
            }

            $vsmId = $result['vsm_id'];
            $nestingLevel = 1;
            $newBreedCodeParts = $this->calculateBreedCodeFromParentsAndPersistNewValue($vsmId, $nestingLevel);

            if($newBreedCodeParts == null) {
                $emptyBreedCodesProcessed++;
            } else {
                $incorrectBreedCodesProcessed++;
            }


            if($this->cmdUtil != null) { $this->cmdUtil->advanceProgressBar(1, 'BreedCodesFix - AlreadyDone|CorrectSkipped|Empty|IncorrectProcessed(including parents): '.$alreadyProcessedBreedCodes.'|'.$correctBreedCodesSkipped.'|'.$emptyBreedCodesProcessed
                .'|'.$incorrectBreedCodesProcessed); }
        }
        if($this->cmdUtil != null) {
            $this->cmdUtil->setProgressBarMessage('BreedCodesFix - AlreadyDone|CorrectSkipped|Empty|IncorrectProcessed(including parents): '.$alreadyProcessedBreedCodes.'|'.$correctBreedCodesSkipped.'|'.$emptyBreedCodesProcessed
                .'|'.$incorrectBreedCodesProcessed);
            $this->cmdUtil->setEndTimeAndPrintFinalOverview();
        }
    }


    /**
     * @param int $vsmId
     * @param int $nestingLevel
     * @return array
     */
    private function calculateBreedCodeFromParentsAndPersistNewValue($vsmId, $nestingLevel)
    {
        $fatherVsmId = Utils::getNullCheckedArrayValue($vsmId, $this->fathers);
        $motherVsmId = Utils::getNullCheckedArrayValue($vsmId, $this->mothers);

        //Two recursive loops to find the breedCodeValues of the parents

        $breedCodePartsOfFather = null;
        if($fatherVsmId != null && $fatherVsmId != 0 && $fatherVsmId != ''
            && $nestingLevel < self::NESTING_LEVEL_LIMIT
            && !array_key_exists($fatherVsmId, $this->updatedBreedCodes)
        ) {
            $breedCodeStringOfFather = Utils::getNullCheckedArrayValue($fatherVsmId, $this->breedCodesByVsmId);
            $breedCodePartsOfFather = Utils::separateLettersAndNumbersOfString($breedCodeStringOfFather);

            if(!self::verifySumOfBreedCodeParts($breedCodePartsOfFather)) {
                $breedCodePartsOfFather = $this->calculateBreedCodeFromParentsAndPersistNewValue($fatherVsmId, $nestingLevel+1);
                if($breedCodePartsOfFather != null) {
                    $this->breedCodesByVsmId[$fatherVsmId] = BreedCodeUtil::implodeBreedCodeParts($breedCodePartsOfFather); //Update search array
                }
            }
        }


        $breedCodePartsOfMother = null;
        if($motherVsmId != null && $motherVsmId != 0 && $motherVsmId != ''
            && $nestingLevel < self::NESTING_LEVEL_LIMIT
            && !array_key_exists($motherVsmId, $this->updatedBreedCodes)
        ) {
            $breedCodeStringOfMother = Utils::getNullCheckedArrayValue($motherVsmId, $this->breedCodesByVsmId);
            $breedCodePartsOfMother = Utils::separateLettersAndNumbersOfString($breedCodeStringOfMother);

            if(!self::verifySumOfBreedCodeParts($breedCodePartsOfMother)) {
                $breedCodePartsOfMother = $this->calculateBreedCodeFromParentsAndPersistNewValue($motherVsmId, $nestingLevel+1);
                if($breedCodePartsOfMother != null) {
                    $this->breedCodesByVsmId[$motherVsmId] = BreedCodeUtil::implodeBreedCodeParts($breedCodePartsOfMother); //Update search array
                }
            }
        }


        if(self::verifySumOfBreedCodeParts($breedCodePartsOfFather) && self::verifySumOfBreedCodeParts($breedCodePartsOfMother)) {

            $newBreedCodeParts = self::divideBreedCodeValuesInHalf($breedCodePartsOfFather, $breedCodePartsOfMother);
            $newBreedCode = BreedCodeUtil::implodeBreedCodeParts($newBreedCodeParts);
            $newBreedCodeForSql = StringUtil::getNullAsStringOrWrapInQuotes($newBreedCode);

            $sql = "UPDATE animal_migration_table SET is_breed_code_updated = TRUE, old_breed_code = breed_code, breed_code = ".$newBreedCodeForSql." WHERE id = ". $this->migrationTableIdByVsmId[$vsmId];
            $this->conn->exec($sql);

            $this->updatedBreedCodes[$vsmId] = $vsmId;

            return $newBreedCodeParts;
        }
        //TODO decide what to do with incorrect BreedCodes that cannot be fixed
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
            $newHalvedNumber = BreedCodeUtil::divideBreedCodeNumberInHalf($number);
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
        if($count < 2 || $count%2 != 0) { return false; }

        $sumOfNumbers = 0;

        for($i = 0; $i < $count-1; $i += 2) {
            if(array_key_exists($i, $breedCodeParts) && array_key_exists($i+1, $breedCodeParts)) {
                $breedCode = $breedCodeParts[$i];
                $number = $breedCodeParts[$i+1];
                if(ctype_alpha($breedCode) && ctype_alnum($number)) {
                    $sumOfNumbers += $number;
                }
            }
        }

        return $sumOfNumbers == 100;
    }


}