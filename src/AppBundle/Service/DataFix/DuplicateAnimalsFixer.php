<?php


namespace AppBundle\Service\DataFix;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Measurement;
use AppBundle\Entity\MeasurementRepository;
use AppBundle\Entity\VsmIdGroupRepository;
use AppBundle\Enumerator\BreedType;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\BreedCodeUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DatabaseDataFixer;
use AppBundle\Util\GenderChanger;
use AppBundle\Util\NullChecker;
use AppBundle\Util\SqlUtil;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * The old legacy version used entities and did not into account some nuances.
 *
 *
 * Class DuplicateAnimalsFixer
 *
 * @ORM\Entity(repositoryClass="AppBundle\Migration")
 * @package AppBundle\Migration
 */
class DuplicateAnimalsFixer extends DuplicateFixerBase
{
    const ULN_DATE_OF_BIRTH_MOTHER = 'uln_date_of_birth_mother';
    const ULN_DATE_OF_BIRTH_FATHER = 'uln_date_of_birth_father';
    const ULN = 'uln';
    const BATCH_SIZE = 100;

    /** @var GenderChanger */
    private $genderChanger;

    /**
     * DuplicateAnimalsFixer constructor.
     * @param ObjectManager $em
     */
    public function __construct(ObjectManager $em)
    {
        parent::__construct($em);
        $this->genderChanger = new GenderChanger($this->em);
    }


    /**
     * @param CommandUtil $cmdUtil
     * @return bool
     */
    public function mergeAnimalPairs(CommandUtil $cmdUtil)
    {
        $this->setCmdUtil($cmdUtil);

        do {
            $primaryAnimalId = intval($this->cmdUtil->generateQuestion('Insert animalId of (primary) animal to keep', 0));
        } while ($primaryAnimalId == 0);

        do {
            $secondaryAnimalId = intval($this->cmdUtil->generateQuestion('Insert animalId of (secondary) animal to delete', 0));
        } while ($secondaryAnimalId == 0 || $primaryAnimalId == $secondaryAnimalId);

        $this->displayAnimalValues($cmdUtil, [$primaryAnimalId, $secondaryAnimalId]);

        $continue = $this->cmdUtil->generateConfirmationQuestion(['Your choice, '.
            'primaryAnimalId: '.$primaryAnimalId, '  secondaryAnimalId: '.$secondaryAnimalId, '. Is this correct? (y/n)']);

        if($continue) {
            $isMergeSuccessFul = $this->mergeAnimalPairByIds($primaryAnimalId, $secondaryAnimalId);
            if($isMergeSuccessFul) { $printOutText = 'MERGE SUCCESSFUL'; } else { $printOutText = 'MERGE FAILED'; }
            $this->cmdUtil->writeln($printOutText);
            return true;
        }
        $this->cmdUtil->writeln('MERGE ABORTED');
        return false;
    }


    /**
     * @param CommandUtil $cmdUtil
     * @return bool
     */
    public function mergeImportedAnimalsMissingLeadingZeroes(CommandUtil $cmdUtil)
    {
        $this->setCmdUtil($cmdUtil);

        $this->writeLn('====== Merge imported Animals missing leading zeroes ======');

        $ulnCountryCode = null;
        $ulnNumber = null;

        do {
            $ulnString = $this->cmdUtil->generateQuestion('Insert uln', null);
            $ulnString = str_replace(' ', '', $ulnString);

            $uln = $ulnString != null ? Utils::getUlnFromString($ulnString) : null;

            if($uln != null) {
                $ulnCountryCode = ArrayUtil::get(Constant::ULN_COUNTRY_CODE_NAMESPACE, $uln);
                $ulnNumber = ArrayUtil::get(Constant::ULN_NUMBER_NAMESPACE, $uln);
            }

        } while ($ulnCountryCode == null || $ulnNumber == null);

        $ulnNumberWithLeadingZeroes = str_pad($ulnNumber, 12, '0', STR_PAD_LEFT);

        $sql = "SELECT id FROM animal WHERE uln_country_code = '".$ulnCountryCode."' 
        AND (uln_number = '".$ulnNumber."' OR uln_number = '".$ulnNumberWithLeadingZeroes."')
        ORDER BY name";
        $results = $this->conn->query($sql)->fetchAll();

        $count = count($results);

        if($count == 0) {
            $this->cmdUtil->writeln('This animal was not found! '.$ulnCountryCode.$ulnNumber);
            return false;
        } elseif($count == 1) {
            $this->cmdUtil->writeln('There are no duplicate animals for this animal! '.$ulnCountryCode.$ulnNumber);
            return false;
        } elseif($count > 2) {
            $this->cmdUtil->writeln('There where more than 2 animals found ('.$count
                .' in total) fix this manually or using another command');
        }

        $animalIds = SqlUtil::getSingleValueGroupedSqlResults('id', $results);

        $primaryAnimalId = array_shift($animalIds);
        $secondaryAnimalId = array_shift($animalIds);

        $this->displayAnimalValues($cmdUtil, [$primaryAnimalId, $secondaryAnimalId]);

        $continue = $this->cmdUtil->generateConfirmationQuestion(['Your choice, '.
            'primaryAnimalId: '.$primaryAnimalId, '  secondaryAnimalId: '.$secondaryAnimalId, ' but the ULN used will be of the secondaryAnimalId. Is this correct? (y/n)']);

        if($continue) {

            //Update uln of primary animal BEFORE merging other values!
            $sql = "UPDATE animal SET uln_number = (
                SELECT uln_number FROM animal WHERE id = ".$secondaryAnimalId."
            ), animal_order_number = (
                SELECT animal_order_number FROM animal WHERE id = ".$secondaryAnimalId."
            ) WHERE id = ".$primaryAnimalId;
            $this->conn->exec($sql);

            $isMergeSuccessFul = $this->mergeAnimalPairByIds($primaryAnimalId, $secondaryAnimalId);
            if($isMergeSuccessFul) { $printOutText = 'MERGE SUCCESSFUL'; } else { $printOutText = 'MERGE FAILED'; }
            $this->cmdUtil->writeln($printOutText);
            return true;
        }
        $this->cmdUtil->writeln('MERGE ABORTED');
        return false;
    }



    /**
     * @param int $primaryAnimalId
     * @param int $secondaryAnimalId
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function mergeAnimalPairByIds($primaryAnimalId, $secondaryAnimalId)
    {
        if(!is_int($primaryAnimalId) || !is_int($secondaryAnimalId) || intval($primaryAnimalId) == intval($secondaryAnimalId)) {
            return false;
        }

        /* 1. Retrieve animalData */
        $sql = "SELECT a.*,
                    CONCAT(mother.uln_country_code, mother.uln_number, mother.date_of_birth) as ".self::ULN_DATE_OF_BIRTH_MOTHER.",
                    CONCAT(father.uln_country_code, father.uln_number, father.date_of_birth) as ".self::ULN_DATE_OF_BIRTH_FATHER.
                " FROM animal a
                LEFT JOIN animal mother ON mother.id = a.parent_mother_id
                LEFT JOIN animal father ON father.id = a.parent_father_id
                    WHERE a.id = ".$primaryAnimalId." OR a.id = ".$secondaryAnimalId;
        $results = $this->conn->query($sql)->fetchAll();

        //Only continue if animals actually exist for both animalIds
        if(count($results) != 2) { return false; }

        $primaryAnimalResultArray = null;
        $secondaryAnimalResultArray = null;
        foreach ($results as $result) {
            $id = intval($result['id']);
            switch ($id) {
                case $primaryAnimalId: $primaryAnimalResultArray = $result; break;
                case $secondaryAnimalId: $secondaryAnimalResultArray = $result; break;
                default: break;
            }
        }

        //Only continue if animals actually exist for both animalIds
        if($primaryAnimalResultArray == null || $secondaryAnimalResultArray == null) { return false; }

        /* 2. merge values */
        $isAnimalIdMergeSuccessFul = $this->mergeAnimalIdValuesInTables($primaryAnimalId, $secondaryAnimalId);
        $isAnimalValueMergeSuccessFul = $this->mergeMissingAnimalValuesIntoPrimaryAnimal($primaryAnimalResultArray, $secondaryAnimalResultArray);

        /* 3 Remove unnecessary duplicate */
        if($isAnimalIdMergeSuccessFul && $isAnimalValueMergeSuccessFul) {
            $this->animalRepository->deleteAnimalsById($secondaryAnimalId);
            return true;
        }
        
        /* 4 Double check animalOrderNumbers */
        DatabaseDataFixer::fixIncongruentAnimalOrderNumbers($this->conn, null);
        
        return false;
    }


    /**
     * @param CommandUtil $cmdUtil
     */
    public function fixDuplicateAnimalsSyncedAndImportedPairs(CommandUtil $cmdUtil)
    {
        $this->setCmdUtil($cmdUtil);

        $this->writeLn('====== Merge duplicate animals by synced and imported pairs ======');

        $sql = $this->createDuplicateSqlQuery(['uln_country_code', 'uln_number', 'date_of_birth'], false);
        $animalsGroupedByUln = $this->findGroupedDuplicateAnimals($sql);

        $totalDuplicateSets = count($animalsGroupedByUln);
        $startMessage = 'Fixing Duplicate Animals of Synced and Imported pairs';

        $this->cmdUtil->setStartTimeAndPrintIt($totalDuplicateSets, 1, $startMessage);

        $animalsToDeleteById = [];
        $loopCounter = 0;
        $batchCounter = 0;
        $skippedCounter = 0;
        $duplicateAnimalsDeleted = 0;
        foreach ($animalsGroupedByUln as $animalsGroup) {
            $loopCounter++;

            $animal1 = $animalsGroup[0];
            $animal2 = $animalsGroup[1];

            $vsmIdAnimal1 = $animal1['name'];
            $vsmIdAnimal2 = $animal2['name'];

            $gender1 = $animal1['gender'];
            $gender2 = $animal2['gender'];

            //Only process if one animal is a synced Animal and the other is an imported Animal
            if((NullChecker::isNull($vsmIdAnimal1) && NullChecker::isNull($vsmIdAnimal2)) ||
                ($gender1 <> $gender2 && $gender1 <> GenderType::NEUTER && $gender2 <> GenderType::NEUTER)
            ) {
                $skippedCounter++;
                continue;
            }

            if ($vsmIdAnimal1 != null || $vsmIdAnimal2 != null) {

                /* 1. Identify primary animal */

                //Default
                $primaryAnimal = $animal2;
                $secondaryAnimal = $animal1;

                //No gender fix necessary the gendered animal is set as the primary animal, if it exists
                if($vsmIdAnimal1 != null && $vsmIdAnimal2 != null) {
                    if($gender1 != GenderType::NEUTER) {
                        $primaryAnimal = $animal1;
                        $secondaryAnimal = $animal2;
                    }

                } else {
                    if ($vsmIdAnimal1 != null) {
                        $primaryAnimal = $animal1;
                        $secondaryAnimal = $animal2;
                    }
                }

                $primaryAnimalId = $primaryAnimal['id'];
                $secondaryAnimalId = $secondaryAnimal['id'];

                /* 2. update gender */

                /* 3. merge values */
                $this->mergeAnimalIdValuesInTables($primaryAnimalId, $secondaryAnimalId);
                $this->mergeMissingAnimalValuesIntoPrimaryAnimal($primaryAnimal, $secondaryAnimal);

                /* 4 Remove unnecessary duplicate */
                $animalsToDeleteById[] = $secondaryAnimalId;
                $batchCounter++;

            } else {
                $skippedCounter++;
            }

            if($loopCounter == $totalDuplicateSets ||
                ($batchCounter%self::BATCH_SIZE == 0 && $batchCounter != 0)) {
                $this->animalRepository->deleteAnimalsById($animalsToDeleteById);
                $duplicateAnimalsDeleted += $batchCounter;
                $batchCounter = 0;
            }
            $this->cmdUtil->advanceProgressBar(1, 'fixed|inBatch|skipped: '.$duplicateAnimalsDeleted.'|'.$batchCounter.'|'.$skippedCounter);
        }

        /* 5 Double check animalOrderNumbers */
        DatabaseDataFixer::fixIncongruentAnimalOrderNumbers($this->conn, null);

        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }


    /**
     * @param CommandUtil $cmdUtil
     */
    public function fixDuplicateAnimalsGroupedOnUlnVsmIdDateOfBirth(CommandUtil $cmdUtil)
    {
        $this->setCmdUtil($cmdUtil);

        $this->writeLn('====== Merge duplicate animals by vsmId, dateOfBirth & ULN ======');

        $sql = $this->createDuplicateSqlQuery(['name', 'date_of_birth', 'uln_number', 'uln_country_code'], false);
        $animalsGroupedByUln = $this->findGroupedDuplicateAnimals($sql);

        $totalDuplicateSets = count($animalsGroupedByUln);
        $startMessage = 'Fixing Duplicate Animals';

        $this->cmdUtil->setStartTimeAndPrintIt($totalDuplicateSets, 1, $startMessage);

        $animalsToDeleteById = [];
        $loopCounter = 0;
        $batchCounter = 0;
        $skippedCounter = 0;
        $duplicateAnimalsDeleted = 0;
        foreach ($animalsGroupedByUln as $animalsGroup) {
            $loopCounter++;

            $animal1 = $animalsGroup[0];
            $animal2 = $animalsGroup[1];

            $vsmIdAnimal1 = $animal1['name'];
            $vsmIdAnimal2 = $animal2['name'];

            $gender1 = $animal1['gender'];
            $gender2 = $animal2['gender'];

            if ($vsmIdAnimal1 != null || $vsmIdAnimal2 != null) {

                /* 1. Identify primary animal */

                //Default
                $primaryAnimal = $animal2;
                $secondaryAnimal = $animal1;

                //No gender fix necessary the gendered animal is set as the primary animal, if it exists
                if($vsmIdAnimal1 != null && $vsmIdAnimal2 != null) {
                    if($gender1 != GenderType::NEUTER) {
                        $primaryAnimal = $animal1;
                        $secondaryAnimal = $animal2;
                    }

                } else {
                    if ($vsmIdAnimal1 != null) {
                        $primaryAnimal = $animal1;
                        $secondaryAnimal = $animal2;
                    }
                }

                $primaryAnimalId = $primaryAnimal['id'];
                $secondaryAnimalId = $secondaryAnimal['id'];

                /* 2. update gender */

                /* 3. merge values */
                $this->mergeAnimalIdValuesInTables($primaryAnimalId, $secondaryAnimalId);
                $this->mergeMissingAnimalValuesIntoPrimaryAnimal($primaryAnimal, $secondaryAnimal);

                /* 4 Remove unnecessary duplicate */
                $animalsToDeleteById[] = $secondaryAnimalId;
                $batchCounter++;

            } else {
                $skippedCounter++;
            }

            if($loopCounter == $totalDuplicateSets ||
                ($batchCounter%self::BATCH_SIZE == 0 && $batchCounter != 0)) {
                $this->animalRepository->deleteAnimalsById($animalsToDeleteById);
                $duplicateAnimalsDeleted += $batchCounter;
                $batchCounter = 0;
            }
            $this->cmdUtil->advanceProgressBar(1, 'fixed|inBatch|skipped: '.$duplicateAnimalsDeleted.'|'.$batchCounter.'|'.$skippedCounter);
        }

        /* 5 Double check animalOrderNumbers */
        DatabaseDataFixer::fixIncongruentAnimalOrderNumbers($this->conn, null);

        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }



    /**
     * @param array $valuesToMatchOn
     * @param boolean $matchUlnOfParents
     * @param boolean $includeMultiDuplicates
     * @return string
     */
    private function createDuplicateSqlQuery(array $valuesToMatchOn, $matchUlnOfParents = true, $includeMultiDuplicates = false)
    {
        if(!is_array($valuesToMatchOn)) { return null; }
        if(count($valuesToMatchOn) == 0) { return null; }

        $innerSelectString = '';
        $joinOnString = '';
        $concatenatedSearchKey = 'CONCAT(';

        foreach ($valuesToMatchOn as $columnHeader) {
            $innerSelectString = $innerSelectString.'n.'.$columnHeader.',';
            $joinOnString = $joinOnString.' g.'.$columnHeader.' = a.'.$columnHeader.' AND';

            if($columnHeader == 'date_of_birth') {
                $concatenatedSearchKey = $concatenatedSearchKey.'DATE(a.'.$columnHeader.'),';
            } else {
                $concatenatedSearchKey = $concatenatedSearchKey.'a.'.$columnHeader.',';
            }
        }
        $innerSelectString = rtrim($innerSelectString, ',');
        $joinOnString = rtrim($joinOnString, ' AND');
        $concatenatedSearchKey = rtrim($concatenatedSearchKey, ',').')';

        $parentUlnFilter = '';
        if($matchUlnOfParents) {
            $parentUlnFilter = ' ,m.uln_country_code,m.uln_number,f.uln_country_code,f.uln_number ';
        }

        $countFilter = $includeMultiDuplicates ? ' > 1' : ' = 2';

        $sql = "SELECT ".$concatenatedSearchKey." as ".self::SEARCH_KEY.", a.*,
                    CONCAT(mother.uln_country_code, mother.uln_number, mother.date_of_birth) as ".self::ULN_DATE_OF_BIRTH_MOTHER.",
                    CONCAT(father.uln_country_code, father.uln_number, father.date_of_birth) as ".self::ULN_DATE_OF_BIRTH_FATHER."
                FROM animal a
                LEFT JOIN animal mother ON mother.id = a.parent_mother_id
                LEFT JOIN animal father ON father.id = a.parent_father_id
                INNER JOIN (
                    SELECT ".$innerSelectString." FROM animal n
                      LEFT JOIN animal m ON m.id = n.parent_mother_id
                      LEFT JOIN animal f ON f.id = n.parent_father_id
                    GROUP BY ".$innerSelectString.$parentUlnFilter." HAVING COUNT(*) ".$countFilter." 
                    -- NOTE THAT DUPLICATES ABOVE 2 PER SET MUST BE CHECKED MANUALLY!
                    )g ON ".$joinOnString;

        return $sql;
    }


    /**
     * @param string $sqlQuery
     * @return array
     */
    private function findGroupedDuplicateAnimals($sqlQuery)
    {
        $results = $this->conn->query($sqlQuery)->fetchAll();

        $animalsGroupedBySearchKey = [];
        foreach ($results as $result) {
            $searchKey = $result[self::SEARCH_KEY];

            if(array_key_exists($searchKey, $animalsGroupedBySearchKey)) {
                $group = $animalsGroupedBySearchKey[$searchKey];
            } else {
                $group = [];
            }

            $group[] = $result;
            $animalsGroupedBySearchKey[$searchKey] = $group;
        }

        return $animalsGroupedBySearchKey;
    }


    /**
     * @param int $primaryAnimalId
     * @param int $secondaryAnimalId
     * @throws \Doctrine\DBAL\DBALException
     * @return boolean
     *
     */
    public function mergeAnimalIdValuesInTables($primaryAnimalId, $secondaryAnimalId)
    {
        if((!is_int($primaryAnimalId) && !ctype_digit($primaryAnimalId)) ||
            (!is_int($secondaryAnimalId) && !ctype_digit($secondaryAnimalId))) { return false; }


        //Delete records where in tables where only unique animalIds are allowed
        $this->deleteRecords($secondaryAnimalId,'animal_id',['animal_cache', 'result_table_breed_grades']);

        //Check in which tables have the secondaryAnimalId
        $tableNamesByVariableType = [
            [ self::TABLE_NAME => 'breed_index',            self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'breed_value',            self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'declare_arrival',        self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'declare_export',         self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'declare_import',         self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'declare_depart',         self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'declare_tag_replace',    self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'declare_loss',           self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'declare_birth',          self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'exterior',               self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'body_fat',               self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'weight',                 self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'muscle_thickness',       self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'tail_length',            self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'declare_weight',         self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'mate',                   self::VARIABLE_TYPE => 'stud_ram_id' ],
            [ self::TABLE_NAME => 'mate',                   self::VARIABLE_TYPE => 'stud_ewe_id' ],
            [ self::TABLE_NAME => 'animal',                 self::VARIABLE_TYPE => 'parent_mother_id' ],
            [ self::TABLE_NAME => 'animal',                 self::VARIABLE_TYPE => 'parent_father_id' ],
            [ self::TABLE_NAME => 'litter',                 self::VARIABLE_TYPE => 'animal_mother_id' ],
            [ self::TABLE_NAME => 'litter',                 self::VARIABLE_TYPE => 'animal_father_id' ],
            [ self::TABLE_NAME => 'animal_residence',       self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'breed_values_set',       self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'ulns_history',           self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'blindness_factor',       self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'predicate',              self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'worm_resistance',              self::VARIABLE_TYPE => 'animal_id' ],
        ];

        $mergeResults = $this->mergeColumnValuesInTables($primaryAnimalId, $secondaryAnimalId, $tableNamesByVariableType);

        if (is_array($mergeResults)) {

            if($mergeResults[self::ARE_MEASUREMENTS_UPDATED]) {
                /** @var MeasurementRepository $measurementsRepository */
                $measurementsRepository = $this->em->getRepository(Measurement::class);
                $measurementsRepository->setAnimalIdAndDateValues();
            }
            return $mergeResults[self::IS_MERGE_SUCCESSFUL];
        }

        return false;
    }


    /**
     * @param array $primaryAnimalResultArray
     * @param array $secondaryAnimalResultArray
     * @return boolean
     */
    public function mergeMissingAnimalValuesIntoPrimaryAnimal($primaryAnimalResultArray, $secondaryAnimalResultArray)
    {
        if(!is_array($primaryAnimalResultArray) || !is_array($secondaryAnimalResultArray)) { return false; }
        if(count($primaryAnimalResultArray) == 0 || count($secondaryAnimalResultArray) == 0) { return false; }

        $primaryAnimalId = Utils::getNullCheckedArrayValue('id', $primaryAnimalResultArray);
        $secondaryAnimalId = Utils::getNullCheckedArrayValue('id', $secondaryAnimalResultArray);

        if($primaryAnimalId == null || $secondaryAnimalId == null) { return false; }

        if((!is_int($primaryAnimalId) && !ctype_digit($primaryAnimalId)) ||
            (!is_int($secondaryAnimalId) && !ctype_digit($secondaryAnimalId))) { return false; }

        $animalSqlBeginning = 'UPDATE animal SET ';
        $animalSqlMiddle = '';
        $animalSqlEnd = ' WHERE id = '.$primaryAnimalResultArray['id'];

        /* Keep values of primary animal if filled
           if empty complement the data with that of the secondary animal */

        $columnHeaders = [
            'parent_father_id', 'parent_mother_id', 'location_id', 'pedigree_country_code', 'pedigree_number', 'name',
            'date_of_birth', 'transfer_state', 'uln_country_code', 'uln_number', 'animal_order_number', 'is_import_animal',
            'is_export_animal', 'is_departed_animal', 'animal_country_origin', 'pedigree_register_id', 'ubn_of_birth', 'location_of_birth_id', 'scrapie_genotype', 'predicate', 'predicate_score', 'nickname', 'blindness_factor',
            'myo_max', 'collar_color', 'collar_number', 'heterosis', 'recombination', 'updated_gene_diversity'
        ];

        foreach ($columnHeaders as $columnHeader) {
            $valuePrimaryValue = $primaryAnimalResultArray[$columnHeader];
            $valueSecondaryValue = $secondaryAnimalResultArray[$columnHeader];

            if($valuePrimaryValue === null && $valueSecondaryValue !== null) {
                $animalSqlMiddle = $animalSqlMiddle.' '.$columnHeader." = '".$valueSecondaryValue."',";
            }
        }

        /* The following values have special conditions when merging */
        
        //dateOfDeath & isAlive
        $dateOfDeath1 = $primaryAnimalResultArray['date_of_death'];
        $dateOfDeath2 = $secondaryAnimalResultArray['date_of_death'];
        $isAlive1 = $primaryAnimalResultArray['is_alive'];
        $isAlive2 = $secondaryAnimalResultArray['is_alive'];

        if ($dateOfDeath1 != null && $isAlive1 == true) {
            $animalSqlMiddle = $animalSqlMiddle.' is_alive = FALSE,';
            
        } elseif ($dateOfDeath1 == null && $dateOfDeath2 != null) {
            $animalSqlMiddle = $animalSqlMiddle." is_alive = FALSE, date_of_death = '".$dateOfDeath2."',";
            
        } elseif ($isAlive2 == true && $isAlive1 == false && $dateOfDeath1 == null && $dateOfDeath2 == null) {
            $animalSqlMiddle = $animalSqlMiddle.' is_alive = TRUE,';
        }
        
        //litterId: related to mother and dateOfBirth
        $litterId1 = $primaryAnimalResultArray['litter_id'];
        $litterId2 = $secondaryAnimalResultArray['litter_id'];
        $ulnDateOfBirthMother1 = $primaryAnimalResultArray[self::ULN_DATE_OF_BIRTH_MOTHER];
        $ulnDateOfBirthMother2 = $secondaryAnimalResultArray[self::ULN_DATE_OF_BIRTH_MOTHER];

        if($litterId1 == null && $litterId2 != null && $ulnDateOfBirthMother1 == $ulnDateOfBirthMother2) {
            $animalSqlMiddle = $animalSqlMiddle.' litter_id = '.$litterId2.',';
        }


        //breedType
        $breedType1 = $primaryAnimalResultArray['breed_type'];
        $breedType2 = $secondaryAnimalResultArray['breed_type'];

        if (  ($breedType1 == null && $breedType2 != null) ||
              ($breedType1 == BreedType::UNDETERMINED && $breedType2 != null && $breedType2 != BreedType::UNDETERMINED)  ){
                $animalSqlMiddle = $animalSqlMiddle." breed_type = '".$breedType2."',";
        }


        //breedCode
        $breedCodeString1 = $primaryAnimalResultArray['breed_code'];
        $breedCodeString2 = $secondaryAnimalResultArray['breed_code'];

        if($breedCodeString1 == null && $breedCodeString2 != null) {
            $animalSqlMiddle = $animalSqlMiddle." breed_code = '".$breedCodeString2."',";
        }
        
        if($animalSqlMiddle != '') {
            $this->conn->exec($animalSqlBeginning.rtrim($animalSqlMiddle,',').$animalSqlEnd);
        }

        $primaryVsmId = $primaryAnimalResultArray['name'];
        $secondaryVsmId = $secondaryAnimalResultArray['name'];
        VsmIdGroupRepository::saveVsmIdGroup($this->conn, $primaryVsmId, $secondaryVsmId);

        return true;
    }


    /**
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function fixDuplicateDueToTagReplaceError(CommandUtil $cmdUtil)
    {
        $this->setCmdUtil($cmdUtil);

        $sql = "SELECT old.type as old_type, new.type as new_type, old.id as old_id, new.id as new_id, r.id as tag_replace_id,
                  replace_date, old.name as vsm_id_old, new.name as vsm_id_new, uln_number_to_replace, uln_number_replacement, uln_country_code_replacement
                FROM declare_tag_replace r
                  INNER JOIN animal old ON old.uln_number = uln_number_to_replace AND old.uln_country_code = uln_country_code_to_replace
                  INNER JOIN animal new ON new.uln_number = uln_number_replacement AND new.uln_country_code = uln_country_code_replacement
                  INNER JOIN declare_base b ON b.id = r.id
                WHERE old.id NOTNULL AND new.id NOTNULL AND old.date_of_birth = new.date_of_birth
                      AND (b.request_state = '".RequestStateType::FINISHED."' 
                        OR b.request_state = '".RequestStateType::FINISHED_WITH_WARNING."')
                      AND new.type = 'Neuter' AND new.name ISNULL
                ORDER BY replace_date";
        $results = $this->conn->query($sql)->fetchAll();
        
        $totalCount = count($results);
        if($totalCount == 0) {
            $this->cmdUtil->writeln('There are no duplicate animals due to tagReplace errors!');
            return true;
        }

        $unSuccessFulMergeCount = 0;
        $this->cmdUtil->setStartTimeAndPrintIt($totalCount, 1);
        foreach ($results as $result) {
            //Use the old animal with the correct gender as the primaryId so no gender change is necessary
            $primaryAnimalId = $result['old_id'];
            $secondaryAnimalId = $result['new_id'];

            //Update the old uln to the new one in the old/imported animal first,
            //so the new uln is not overwritten during the merge
            $newUlnCountryCode = $result['uln_country_code_replacement'];
            $newUlnNumber = $result['uln_number_replacement'];
            $sql = "UPDATE animal SET uln_country_code = '".$newUlnCountryCode."', uln_number = '".$newUlnNumber."' WHERE id = ".$primaryAnimalId;
            $this->conn->exec($sql);

            $isSuccessFul = $this->mergeAnimalPairByIds($primaryAnimalId, $secondaryAnimalId);
            if(!$isSuccessFul) { $unSuccessFulMergeCount++; }
            $this->cmdUtil->advanceProgressBar(1, 'Failed merges: '.$unSuccessFulMergeCount);
        }
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();

        return $unSuccessFulMergeCount == 0 ? true : false;
    }


    /**
     * @param CommandUtil $cmdUtil
     * @param $animalIds
     */
    private function displayAnimalValues(CommandUtil $cmdUtil, $animalIds)
    {
        $this->setCmdUtil($cmdUtil);

        if(is_int($animalIds) || ctype_digit($animalIds)) {
            $animalIdFilterString = ' a.id = '.$animalIds;
        } elseif (is_array($animalIds)) {
            $animalIdFilterString = SqlUtil::getFilterStringByIdsArray($animalIds, 'a.id');
        } else {
            return;
        }

        $sql = "SELECT a.*, l.ubn, r.abbreviation as pedigree FROM animal a
                LEFT JOIN location l ON l.id = a.location_id 
                LEFT JOIN pedigree_register r ON r.id = a.pedigree_register_id
                WHERE ".$animalIdFilterString;
        $results = $this->conn->query($sql)->fetchAll();

        foreach ($results as $result) {
            $animalId = $result['id'];
            $ubn = $result['ubn'];
            $uln = $result['uln_country_code'].' '.$result['uln_number'];
            $stn = $result['pedigree_country_code'].' '.$result['pedigree_number'];
            $type = $result['type'];
            $dateOfBirth = $result['date_of_birth'];
            $vsmId = $result['name'];
            $breedType = $result['breed_type'];
            $breedCode = $result['breed_code'];
            $scrapieGenoType = $result['scrapie_genotype'];
            $pedigree = $result['pedigree'];

            $this->cmdUtil->writeln('animalId: '.$animalId.' | '.$uln.' | '.$stn.' | '.$type.' | '.$dateOfBirth.' | '.$vsmId.' | '.$breedType.' | '.
                $breedCode.' | '.$scrapieGenoType.' | '.$pedigree.' | '.$ubn);
        }
    }


    /**
     * @param CommandUtil|null $cmdUtil
     */
    public function fixMultipleDuplicateAnimalsAfterMigration(CommandUtil $cmdUtil = null)
    {
        $this->setCmdUtil($cmdUtil);

        $this->writeLn('====== Merge multi duplicate animals after migration ======');

        $sql = $this->createDuplicateSqlQuery(['uln_country_code', 'uln_number'], false, true);
        $animalsGroupedByUln = $this->findGroupedDuplicateAnimals($sql);

        $totalDuplicateSets = count($animalsGroupedByUln);
        if ($totalDuplicateSets === 0) { return; }

        $loopCounter = 0;
        $batchCounter = 0;
        $skippedCounter = 0;
        $duplicateAnimalsDeleted = 0;
        $animalsToDeleteById = [];

        $skippedDueToGender = 0;
        $skippedDueBirthDate = 0;

        $foreignKeyMissingCount = 0;
        $exception = null;

        $this->cmdUtil->setStartTimeAndPrintIt($totalDuplicateSets, 1);
        foreach ($animalsGroupedByUln as $uln => $animalsGroup) {
            $loopCounter++;

            /* 1. Identify primary animal */
            $keyPrimaryAnimal = $this->getKeyPrimaryAnimal($animalsGroup);
            $primaryAnimalValues = $animalsGroup[$keyPrimaryAnimal];
            $primaryAnimalId = $primaryAnimalValues['id'];
            $genderPrimaryAnimal = $primaryAnimalValues['gender'];
            $dateOfBirthPrimaryAnimal = $primaryAnimalValues['date_of_birth'];

            foreach ($animalsGroup as $key => $secondaryAnimalValues) {
                if ($key === $keyPrimaryAnimal) { continue; }

                $secondaryAnimalId = $secondaryAnimalValues['id'];
                $genderSecondaryAnimal = $secondaryAnimalValues['gender'];
                $dateOfBirthSecondaryAnimal = $secondaryAnimalValues['date_of_birth'];

                /* 2. Validation
                    Skip if genders are mismatched
                    or if dates of birth are both not null and mismatched
                */
                if ($genderPrimaryAnimal !== GenderType::NEUTER
                && $genderSecondaryAnimal !== $genderPrimaryAnimal) {
                    $skippedCounter++; $skippedDueToGender++;

                } elseif ($dateOfBirthPrimaryAnimal !== $dateOfBirthSecondaryAnimal
                && $dateOfBirthSecondaryAnimal !== null) {
                    $skippedCounter++; $skippedDueBirthDate++;

                } else {

                    try {
                        /* 3 Fix gender of  */

                        /* 4. merge values */
                        $this->mergeAnimalIdValuesInTables($primaryAnimalId, $secondaryAnimalId);
                        $this->mergeMissingAnimalValuesIntoPrimaryAnimal($primaryAnimalValues, $secondaryAnimalValues);

                        /* 5 Remove unnecessary duplicate */
                        $animalsToDeleteById[] = $secondaryAnimalId;
                        $batchCounter++;
                    } catch (\Exception $exception) {
                        $foreignKeyMissingCount++;
                    }
                }

                if($batchCounter%self::BATCH_SIZE === 0 && $batchCounter !== 0) {
                    $this->animalRepository->deleteAnimalsById($animalsToDeleteById);
                    $duplicateAnimalsDeleted += $batchCounter;
                    $batchCounter = 0;
                    $animalsToDeleteById = [];
                }
            }

            if($loopCounter === $totalDuplicateSets ||
                ($batchCounter%self::BATCH_SIZE === 0 && $batchCounter !== 0)) {
                $this->animalRepository->deleteAnimalsById($animalsToDeleteById);
                $duplicateAnimalsDeleted += $batchCounter;
                $batchCounter = 0;
                $animalsToDeleteById = [];
            }
            $this->cmdUtil->advanceProgressBar(1, 'fixed|inBatch: '.$duplicateAnimalsDeleted.'|'.$batchCounter
                .'  skipped birthDate|gender|total: '.$skippedDueBirthDate.'|'.$skippedDueToGender.'|'.$skippedCounter);
        }
        $this->animalRepository->deleteAnimalsById($animalsToDeleteById);

        /* 5 Double check animalOrderNumbers */
        DatabaseDataFixer::fixIncongruentAnimalOrderNumbers($this->conn, null);

        $this->cmdUtil->setEndTimeAndPrintFinalOverview();

        if ($foreignKeyMissingCount > 0) {
            $this->writeLn($exception->getMessage());
            $this->writeLn('Running fixMultipleDuplicateAnimalsAfterMigration again due to '.$foreignKeyMissingCount.' of these errors ...');
            $this->fixMultipleDuplicateAnimalsAfterMigration($this->cmdUtil);
        }
    }


    /**
     * @param array $animalsGroup
     * @return int|string
     * @throws \Exception
     */
    private function getKeyPrimaryAnimal($animalsGroup)
    {
        $validationCount = 7;
        //Validations
        $containsNoImportedAnimals = true;
        $containsOnlyNeuters = true;
        $containsNoDateOfBirth = true;
        $containsNoDateOfDeath = true;
        $containsNoValidBreedCode = true;
        $containsNoBreedCode = true;
        $containsNoLitterId = true;

        $searchKey = $animalsGroup[0][self::SEARCH_KEY];
        foreach ($animalsGroup as $key => $values) {
            //Prioritize imported Animal over synced Animal
            if ($containsNoImportedAnimals && $values['name'] !== null) {
                $containsNoImportedAnimals = false;
            }

            if ($containsOnlyNeuters && $values['gender'] !== GenderType::NEUTER) {
                $containsOnlyNeuters = false;
            }

            if ($containsNoDateOfBirth && $values['date_of_birth'] !== null) {
                $containsNoDateOfBirth = false;
            }

            if ($containsNoDateOfDeath && $values['date_of_death'] !== null) {
                $containsNoDateOfDeath = false;
            }

            if ($containsNoLitterId && $values['litter_id'] !== null) {
                $containsNoLitterId = false;
            }

            $breedCode = $values['breed_code'];
            if ($containsNoBreedCode && $breedCode !== null && $breedCode !== '') {
                $containsNoBreedCode = false;
            }

            if ($containsNoValidBreedCode && BreedCodeUtil::isValidBreedCodeString($breedCode)) {
                $containsNoValidBreedCode = false;
            }


            if (!$containsNoImportedAnimals && !$containsOnlyNeuters && !$containsNoDateOfBirth && !$containsNoValidBreedCode && !$containsNoBreedCode
                && !$containsNoDateOfDeath && !$containsNoLitterId) {
                break;
            }
        }

        for ($i = 1; $i <= $validationCount; $i++) {
            //First check if there is one animal that satisfies all conditions, if that is not the case recheck it with one validation less each loop.
            foreach ($animalsGroup as $key => $values) {
                $vsmId = $values['name'];
                $gender = $values['gender'];
                $dateOfBirth = $values['date_of_birth'];
                $dateOfDeath = $values['date_of_death'];
                $breedCode = $values['breed_code'];
                $litterId = $values['litter_id'];
                if (!$containsNoImportedAnimals && $vsmId === null && $i <= $validationCount - 0) { continue; }
                if (!$containsOnlyNeuters && $gender === GenderType::NEUTER && $i <= $validationCount - 1) { continue; }
                if (!$containsNoDateOfBirth && $dateOfBirth === null && $i <= $validationCount - 2) { continue; }
                if (!$containsNoDateOfDeath && $dateOfDeath === null && $i <= $validationCount - 3) { continue; }
                if (!$containsNoLitterId && $litterId === null && $i <= $validationCount - 4) { continue; }
                if (!$containsNoBreedCode && ($breedCode === null || $breedCode === '') && $i <= $validationCount - 5) { continue; }
                if (!$containsNoValidBreedCode && !BreedCodeUtil::isValidBreedCodeString($breedCode) && $i <= $validationCount - 6) { continue; }

                //Return first key to pass these validations
                return $key;
            }
        }

        throw new \Exception('Something is incorrect in the getKeyPrimaryAnimal function of LitterMigrator, key: '.$searchKey);
    }


}