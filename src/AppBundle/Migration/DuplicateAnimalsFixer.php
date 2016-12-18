<?php


namespace AppBundle\Migration;


use AppBundle\Component\Utils;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Enumerator\BreedType;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\GenderChanger;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The old legacy version used entities and did not into account some nuances.
 *
 *
 * Class DuplicateAnimalsFixer
 *
 * @ORM\Entity(repositoryClass="AppBundle\Migration")
 * @package AppBundle\Migration
 */
class DuplicateAnimalsFixer
{
    const SEARCH_KEY = 'search_key';
    const ULN_DATE_OF_BIRTH_MOTHER = 'uln_date_of_birth_mother';
    const ULN_DATE_OF_BIRTH_FATHER = 'uln_date_of_birth_father';
    const VARIABLE_TYPE = 'variable_type';
    const TABLE_NAME = 'table_name';
    const BATCH_SIZE = 1000;

    /** @var ObjectManager $em */
    private $em;

    /** @var AnimalRepository $animalRepository */
    private $animalRepository;

    /** @var CommandUtil */
    private $cmdUtil;

    /** @var OutputInterface */
    private $output;

    /** @var GenderChanger */
    private $genderChanger;

    /** @var Connection */
    private $conn;


    /**
     * DuplicateAnimalsFixer constructor.
     * @param ObjectManager $em
     * @param OutputInterface $output
     * @param CommandUtil $cmdUtil
     */
    public function __construct(ObjectManager $em, OutputInterface $output, CommandUtil $cmdUtil)
    {
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->output = $output;
        $this->cmdUtil = $cmdUtil;

        /** @var GenderChanger genderChanger */
        $this->genderChanger = new GenderChanger($this->em);

        /** @var AnimalRepository $animalRepository */
        $this->animalRepository = $this->em->getRepository(Animal::class);
    }


    public function fixDuplicateAnimalsWithIdenticalVsmIds()
    {
        $this->fixDuplicateAnimalsGroupedOnUlnStnVsmIdDateOfBirthDeathAndParents();
    }


    public function fixDuplicateSyncedVsMigratedAnimals()
    {
        //TODO
        $this->output->writeln('STILL TO DO');
    }
    


    private function fixDuplicateAnimalsGroupedOnUlnStnVsmIdDateOfBirthDeathAndParents()
    {
        $sql = $this->createDuplicateSqlQuery(['name', 'date_of_birth', 'uln_number', 'uln_country_code',
            'pedigree_country_code', 'pedigree_number', 'gender']);
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

            /* Assuming there are only 2 animals per duplicate pair, as checked in the database by sql-query,
             * Keep the imported animals and transfer the data from the synced animals
             * At this moment (2016nov2) the synced animals have the latest declare data
             * (depart & loss) and some measurements (fat, muscle, weight, breed_values_set)
             */

            $vsmIdAnimal1 = $animal1['name'];
            $vsmIdAnimal2 = $animal2['name'];

            if ($vsmIdAnimal1 != null || $vsmIdAnimal2 != null) {

                /* 1. Identify primary animal */
                if ($vsmIdAnimal1 != null) {
                    $primaryAnimal = $animal1;
                    $secondaryAnimal = $animal2;
                } else {
                    $primaryAnimal = $animal2;
                    $secondaryAnimal = $animal1;
                }

                $primaryAnimalId = $primaryAnimal['id'];
                $secondaryAnimalId = $secondaryAnimal['id'];

                //No gender fix necessary, because the imported animal is the primaryAnimal

                /* 2. */
                $this->mergeAnimalIdValuesInTables($primaryAnimalId, $secondaryAnimalId);
                $this->mergeMissingAnimalValuesIntoPrimaryAnimal($primaryAnimal, $secondaryAnimal);

                /* 3 Remove unnecessary duplicate */
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
            $this->cmdUtil->advanceProgressBar(1, 'DuplicateAnimals fixed|inBatch|skipped: '.$duplicateAnimalsDeleted.'|'.$batchCounter.'|'.$skippedCounter);
        }

        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }



    /**
     * @param array $valuesToMatchOn
     * @param boolean $matchUlnOfParents
     * @return string
     */
    private function createDuplicateSqlQuery(array $valuesToMatchOn, $matchUlnOfParents = true)
    {
        if(!is_array($valuesToMatchOn)) { return null; }
        if(count($valuesToMatchOn) == 0) { return null; }

        $innerSelectString = '';
        $joinOnString = '';

        foreach ($valuesToMatchOn as $columnHeader) {
            $innerSelectString = $innerSelectString.'n.'.$columnHeader.',';
            $joinOnString = $joinOnString.' g.'.$columnHeader.' = a.'.$columnHeader.' AND';
        }
        $innerSelectString = rtrim($innerSelectString, ',');
        $joinOnString = rtrim($joinOnString, ' AND');

        $parentUlnFilter = '';
        if($matchUlnOfParents) {
            $parentUlnFilter = ' ,m.uln_country_code,m.uln_number,f.uln_country_code,f.uln_number ';
        }

        $sql = "SELECT CONCAT(a.uln_country_code, a.uln_number, DATE(a.date_of_birth), a.name) as ".self::SEARCH_KEY.", a.*,
                    CONCAT(mother.uln_country_code, mother.uln_number, mother.date_of_birth) as ".self::ULN_DATE_OF_BIRTH_MOTHER.",
                    CONCAT(father.uln_country_code, father.uln_number, father.date_of_birth) as ".self::ULN_DATE_OF_BIRTH_FATHER."
                FROM animal a
                LEFT JOIN animal mother ON mother.id = a.parent_mother_id
                LEFT JOIN animal father ON father.id = a.parent_father_id
                INNER JOIN (
                    SELECT ".$innerSelectString." FROM animal n
                      LEFT JOIN animal m ON m.id = n.parent_mother_id
                      LEFT JOIN animal f ON f.id = n.parent_father_id
                    GROUP BY ".$innerSelectString.$parentUlnFilter." HAVING COUNT(*) = 2 
                    -- NOTE THAT DUPLICATES ABOVE 2 PER SET MUST BE CHECKED MANUALLY!
                    )g ON ".$joinOnString;

        return $sql;
    }


    /**
     * @param string $sqlQuery
     * @return ArrayCollection
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
     *
     */
    public function mergeAnimalIdValuesInTables($primaryAnimalId, $secondaryAnimalId)
    {
        if(!is_int($primaryAnimalId) || !ctype_digit($primaryAnimalId) ||
            !is_int($secondaryAnimalId) || !ctype_digit($secondaryAnimalId)) { return; }

        //Check in which tables have the secondaryAnimalId
        $tableNamesByVariableType = [
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
            [ self::TABLE_NAME => 'animal_cache',           self::VARIABLE_TYPE => 'animal_id' ],
            [ self::TABLE_NAME => 'predicate',              self::VARIABLE_TYPE => 'animal_id' ],
        ];

        $sql = '';
        $counter = 0;
        foreach ($tableNamesByVariableType as $tableNameByVariableType) {
            $counter++;
            $tableName = $tableNameByVariableType[self::TABLE_NAME];
            $variableType = $tableNameByVariableType[self::VARIABLE_TYPE];

            $sql = $sql."SELECT ".$counter." as count, '".$tableName."' as ".self::TABLE_NAME.", '".$variableType.
                "' as ".self::VARIABLE_TYPE." FROM ".$tableName." WHERE ".$variableType." = ".$secondaryAnimalId." UNION ";
        }
        $sql = rtrim($sql, 'UNION ');
        $results = $this->conn->query($sql)->fetchAll();

        $secondaryAnimalIsInAnyTable = count($results) != 0;

        $sqlUpdateQueries = [];
        if($secondaryAnimalIsInAnyTable) {
            foreach ($results as $result) {
                $tableName = $result[self::TABLE_NAME];
                $variableType = $result[self::VARIABLE_TYPE];

                $uniqueUpdateKey = $tableName.'-'.$variableType;
                if(!array_key_exists($uniqueUpdateKey, $sqlUpdateQueries)) {
                    $sql = "UPDATE ".$tableName." SET ".$variableType." = ".$primaryAnimalId." WHERE ".$variableType." = ".$secondaryAnimalId;
                    $sqlUpdateQueries[$uniqueUpdateKey] = $sql;
                }
            }
        }

        //Execute updates
        foreach ($sqlUpdateQueries as $sqlUpdateQuery) {
            $this->conn->exec($sqlUpdateQuery);
        }
    }


    /**
     * @param array $primaryAnimalResultArray
     * @param array $secondaryAnimalResultArray
     */
    public function mergeMissingAnimalValuesIntoPrimaryAnimal($primaryAnimalResultArray, $secondaryAnimalResultArray)
    {
        if(!is_array($primaryAnimalResultArray) || !ctype_digit($secondaryAnimalResultArray)) { return; }
        if(count($primaryAnimalResultArray) == 0 || count($secondaryAnimalResultArray) == 0) { return; }

        $primaryAnimalId = Utils::getNullCheckedArrayValue('id', $primaryAnimalResultArray);
        $secondaryAnimalId = Utils::getNullCheckedArrayValue('id', $secondaryAnimalResultArray);

        if($primaryAnimalId == null || $secondaryAnimalId == null) { return; }

        if(!is_int($primaryAnimalId) || !ctype_digit($primaryAnimalId) ||
            !is_int($secondaryAnimalId) || !ctype_digit($secondaryAnimalId)) { return; }

        $animalSqlBeginning = 'UPDATE animal SET ';
        $animalSqlMiddle = '';
        $animalSqlEnd = ' WHERE id = '.$primaryAnimalResultArray['id'];

        /* Keep values of primary animal if filled
           if empty complement the data with that of the secondary animal */

        $columnHeaders = [
            'parent_father_id', 'parent_mother_id', 'location_id', 'pedigree_country_code', 'pedigree_number', 'name',
            'date_of_birth', 'transfer_state', 'uln_country_code', 'uln_number', 'animal_order_number', 'is_import_animal',
            'is_export_animal', 'is_departed_animal', 'animal_country_origin', 'pedigree_register_id', 'ubn_of_birth', 'location_of_birth_id', 'scrapie_genotype', 'predicate', 'predicate_score', 'nickname', 'blindness_factor',
            'myo_max', 'mixblup_block'
        ];

        foreach ($columnHeaders as $columnHeader) {
            $valuePrimaryValue = $primaryAnimalResultArray[$columnHeader];
            $valueSecondaryValue = $secondaryAnimalResultArray[$columnHeader];

            if($valuePrimaryValue === null && $valueSecondaryValue !== null) {
                $animalSqlMiddle = $animalSqlMiddle.' '.$columnHeader.' = '.$valueSecondaryValue.',';
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
            $animalSqlMiddle = $animalSqlMiddle.' is_alive = FALSE, date_of_death = '.$dateOfDeath2.',';
            
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
                $animalSqlMiddle = $animalSqlMiddle.' breed_type = '.$breedType2.',';
        }


        //breedCode AND linked breedCodesId

        $breedCodeString1 = $primaryAnimalResultArray['breed_code'];
        $breedCodeString2 = $secondaryAnimalResultArray['breed_code'];
        $breedCodesSetId1 = $primaryAnimalResultArray['breed_codes_id'];
        $breedCodesSetId2 = $secondaryAnimalResultArray['breed_codes_id'];

        if($breedCodeString1 == null && $breedCodeString2 != null) {
            $animalSqlMiddle = $animalSqlMiddle.' breed_code = '.$breedCodeString2.',';
            if($breedCodesSetId1 == null && $breedCodesSetId2 != null) {
                $animalSqlMiddle = $animalSqlMiddle.' breed_codes_id = '.$breedCodesSetId2.',';
                $sql = "UPDATE breed_codes SET animal_id = ".$primaryAnimalId." WHERE id = ".$breedCodesSetId2;
                $this->conn->exec($sql);
            }
        }
        
        if($animalSqlMiddle != '') {
            $this->conn->exec($animalSqlBeginning.rtrim($animalSqlMiddle,',').$animalSqlEnd);
        }

    }


    /**
     * @param array $primaryAnimalResultArray
     * @param array $secondaryAnimalResultArray
     */
    private function updateGender($primaryAnimalResultArray, $secondaryAnimalResultArray)
    {
        //TODO update gender AND type
    }

}