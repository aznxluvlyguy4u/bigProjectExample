<?php


namespace AppBundle\Service\Migration;

use AppBundle\Constant\JsonInputConstant;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\QueryType;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\CsvParser;
use AppBundle\Util\DatabaseDataFixer;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\Translation;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class AnimalTableImporter
 *
 * Importing 'DierTabel' csv file to animal_migration_table database table,
 * implementing some fixes to the data in the animal_migration_table,
 * and very minor fixes to the animal database table.
 */
class AnimalTableImporter extends Migrator2017JunServiceBase implements IMigratorService
{
    const BATCH_SIZE = 250000;

    //Search arrays
    private $pedigreeRegisterIdsByAbbreviation;


    /** @inheritdoc */
    public function __construct(ObjectManager $em, $rootDir)
    {
        parent::__construct($em, $rootDir, self::BATCH_SIZE);
        $this->getCsvOptions()->setFileName($this->filenames[self::ANIMAL_TABLE]);
    }


    /** @inheritdoc */
    public function run(CommandUtil $cmdUtil)
    {
        parent::run($cmdUtil);

        $option = $this->cmdUtil->generateMultiLineQuestion([
            ' ', "\n",
            'Choose option: ', "\n",
            '1: Import AnimalTable csv file into database', "\n",
            '2: Update animal_migration_table empty animal table values', "\n",
            '3: Extract breederNumbers from pedigreeNumbers and ubnOfBirths', "\n",
            '4: Fix animal_migration_table values, including issues as requested by Reinard', "\n",
            '----------------------------------------------------', "\n",
//            '2: Export animal_migration_table to csv', "\n",
//            '3: Import animal_migration_table from exported csv', "\n",
//            '4: Export vsm_id_group to csv', "\n",
//            '5: Import vsm_id_group from exported csv', "\n",
//            '6: Export uln by animalId to csv', "\n",
//            '7: Import uln by animalId to csv', "\n",
            '----------------------------------------------------', "\n",
            '20: Print pedigreeRegisters in csv file', "\n",
            'exit AnimalTableImporter (other)', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {
            case 1: $this->importAnimalTableCsvFileIntoDatabase(); break;
            case 2: $this->updateValues(); break;
            case 3: $this->extractBreederNumbers(); break;
            case 4: $this->fixValues(); break;
//            case 5:
//                break;
//            case 6:
//                break;
//            case 7:
//                break;
            case 20: $this->printPedigreeRegistersInCsvFile(); break;
                break;
            default: $this->writeLn('Exited AnimalTableImporter'); return;
        }
        $this->run($this->cmdUtil);
    }


    private function parseAnimalTableCsv()
    {
        if (!is_array($this->data) || count($this->data) === 0) {
            $this->writeLn('Parsing animal table import file: '.$this->csvOptions->getInputFolder().$this->csvOptions->getFileName(). '...');
            $this->data = CsvParser::parse($this->csvOptions);
            $this->writeLn('Parsing complete');
        }
    }


    /**
     *
     */
    public function importAnimalTableCsvFileIntoDatabase()
    {
        $this->writeLn('=== Importing records from CSV File in to animal_migration_table ===');

        $this->parseAnimalTableCsv();

        $this->writeLn('Initializing search arrays ...');

        //Initialize searchArrays
        $sql = "SELECT vsm_id FROM animal_migration_table";
        $results = $this->conn->query($sql)->fetchAll();
        $processedAnimals = SqlUtil::groupSqlResultsOfKey1ByKey2('vsm_id', 'vsm_id', $results);

        DoctrineUtil::updateTableSequence($this->conn, ['animal_migration_table']);

        $insertBatchSet = $this->sqlBatchProcessor
            ->purgeAllSets()
            ->createBatchSet(QueryType::INSERT)
            ->getSet(QueryType::INSERT)
        ;

        $sqlBase = "INSERT INTO animal_migration_table (id, vsm_id, uln_origin, stn_origin, uln_country_code, uln_number, animal_order_number,
						pedigree_country_code, pedigree_number, nick_name, father_vsm_id, mother_vsm_id, gender_in_file, date_of_birth,breed_code,ubn_of_birth,pedigree_register_id,breed_type,scrapie_genotype
						)VALUES ";
        $insertBatchSet->setSqlQueryBase($sqlBase);

        $this->sqlBatchProcessor
            ->start(count($this->data))
        ;


        foreach ($this->data as $record) {

            $vsmId = intval($record[0]);
            if(array_key_exists($vsmId, $processedAnimals)) {
                $insertBatchSet->incrementAlreadyDoneCount();
                $this->sqlBatchProcessor->advanceProgressBar();
                continue;
            }

            $uln = StringUtil::getNullAsStringOrWrapInQuotes($record[3]);
            $ulnParts = $this->parseUln($record[3]);
            $ulnCountryCode = StringUtil::getNullAsStringOrWrapInQuotes($ulnParts[JsonInputConstant::ULN_COUNTRY_CODE]);
            $ulnNumber = StringUtil::getNullAsStringOrWrapInQuotes($ulnParts[JsonInputConstant::ULN_NUMBER]);

            if($ulnCountryCode == "'XD'") { // These are testAnimals and should be skipped
                $insertBatchSet->incrementSkippedCount();
                $this->sqlBatchProcessor->advanceProgressBar();
                continue;
            }

            $stnImport = StringUtil::getNullAsStringOrWrapInQuotes($record[1]);
            $stnParts = $this->parseStn($record[1]);
            $pedigreeCountryCode = StringUtil::getNullAsStringOrWrapInQuotes($stnParts[JsonInputConstant::PEDIGREE_COUNTRY_CODE]);
            $pedigreeNumber = StringUtil::getNullAsStringOrWrapInQuotes($stnParts[JsonInputConstant::PEDIGREE_NUMBER]);

            $animalOrderNumber = 'NULL';
            if($record[2] != null && $record[2] != '') {
                $animalOrderNumber = StringUtil::getNullAsStringOrWrapInQuotes(StringUtil::padAnimalOrderNumberWithZeroes($record[2]));
            }

            $nickName = StringUtil::getNullAsStringOrWrapInQuotes(utf8_encode(StringUtil::escapeSingleApostrophes($record[4])));
            $fatherVsmId = $this->getParentVsmIdForSqlQuery($record[5]);
            $motherVsmId = $this->getParentVsmIdForSqlQuery($record[6]);
            $genderInFile = StringUtil::getNullAsStringOrWrapInQuotes($this->parseGender($record[7]));
            $dateOfBirthString = $this->parseDateString($record[8]);
            $breedCode = StringUtil::getNullAsStringOrWrapInQuotes($record[9]);
            $ubnOfBirth = StringUtil::getNullAsStringOrWrapInQuotes($record[10]); //ubnOfBreeder

            $pedigreeRegisterId = $this->getPedigreeRegisterId($record[11]);
            $breedType = SqlUtil::getNullCheckedValueForSqlQuery(Translation::getEnglish(strtoupper($record[12])), true);
            $scrapieGenotype = SqlUtil::getNullCheckedValueForSqlQuery($record[13], true);

            //Insert new record, process it as a batch

            $sqlInsertGroup = "(nextval('animal_migration_table_id_seq'),".$vsmId.",".$uln.",".$stnImport.",".$ulnCountryCode.",".$ulnNumber.",".$animalOrderNumber.",".$pedigreeCountryCode.",".$pedigreeNumber.",".$nickName.",".$fatherVsmId.",".$motherVsmId.",".$genderInFile.",".$dateOfBirthString.",".$breedCode.",".$ubnOfBirth.",".$pedigreeRegisterId.",".$breedType.",".$scrapieGenotype.")";

            $insertBatchSet->appendValuesString($sqlInsertGroup);

            $this->sqlBatchProcessor
                ->processAtBatchSize()
                ->advanceProgressBar()
            ;
        }
        $this->sqlBatchProcessor->end();

        DoctrineUtil::updateTableSequence($this->conn, ['animal_migration_table']);

        $this->initializeStartValues();
    }


    /**
     * @param string $pedigreeRegisterStringInCsvFile
     * @return int|null
     */
    private function getPedigreeRegisterId($pedigreeRegisterStringInCsvFile)
    {
        $this->initializePedigreeRegisterIdsByAbbreviation();
        $abbreviation = $this->parsePedigreeRegisterAbbreviation($pedigreeRegisterStringInCsvFile);
        return ArrayUtil::get($abbreviation, $this->pedigreeRegisterIdsByAbbreviation, 'NULL');
    }


    private function initializePedigreeRegisterIdsByAbbreviation()
    {
        if (!is_array($this->pedigreeRegisterIdsByAbbreviation) || count($this->pedigreeRegisterIdsByAbbreviation) === 0) {
            $sql = "SELECT id, abbreviation FROM pedigree_register";
            $results = $this->conn->query($sql)->fetchAll();
            $this->pedigreeRegisterIdsByAbbreviation = SqlUtil::groupSqlResultsOfKey1ByKey2('id', 'abbreviation', $results, true);
        }
    }


    /**
     * @param $pedigreeRegisterStringInCsvFile
     * @return string|null
     */
    private function parsePedigreeRegisterAbbreviation($pedigreeRegisterStringInCsvFile)
    {
        if (is_string($pedigreeRegisterStringInCsvFile)) {
            return explode(' : ', $pedigreeRegisterStringInCsvFile)[0];
        }
        return null;
    }



    private function initializeStartValues()
    {
        $this->writeLn('=== Initialize first values after csv import to animal_migration_table ===');

        $queries = [

            'Update incongruent animal_id where vsmId = name in animal table ...' =>
                $this->getUpdateIncongruentAnimalIdsSqlQuery(),

            //Only run this query AFTER updating the animalId values!
            'Set is_new_import_animal = TRUE for newly imported animals without an animalId, secondary_vsm_id, NOR replaced uln ...' =>
                "UPDATE animal_migration_table SET is_new_import_animal = TRUE
                    WHERE animal_id ISNULL
                          AND vsm_id NOT IN (
                              SELECT CAST(secondary_vsm_id AS INTEGER) FROM vsm_id_group
                          )
                          AND CONCAT(uln_country_code,uln_number) NOT IN (
                              SELECT CONCAT(uln_country_code, uln_number) as uln FROM tag WHERE tag_status = 'REPLACED'
                          )
                          AND is_new_import_animal = FALSE",

        ];

        foreach ($queries as $title => $sql) {
            $this->updateBySql($title, $sql);
        }
    }


    /**
     * @return string
     */
    private function getUpdateIncongruentAnimalIdsSqlQuery()
    {
        return "UPDATE animal_migration_table SET animal_id = v.animal_id
                    FROM (
                        SELECT vsm_id, a.id
                        FROM animal_migration_table m
                            INNER JOIN animal a ON CAST(a.name AS INTEGER) = m.vsm_id
                                                   AND (animal_id ISNULL
                                                        OR animal_id <> a.id
                                                   )
                        WHERE a.name NOTNULL AND vsm_id NOT IN (
                            --SKIP duplicate vsmIds in database
                            SELECT CAST(name AS INTEGER)
                            FROM animal
                            WHERE name NOTNULL
                            GROUP BY name HAVING COUNT(*) > 1
                        )
                    ) AS v(vsm_id, animal_id) WHERE animal_migration_table.vsm_id = v.vsm_id";
    }


    private function updateValues()
    {
        $this->writeLn('=== Update incongruent/empty values in animal_migration_table ===');

        $queries = [

            'Update incongruent animal_id where vsmId = name in animal table ...' =>
                $this->getUpdateIncongruentAnimalIdsSqlQuery(),

            'Update incongruent gender_in_database where animal_id = id in animal table (only run after animal_id update) ...' =>
                "UPDATE animal_migration_table SET gender_in_database = v.gender_in_database
                    FROM (
                           SELECT animal_id, gender
                           FROM animal_migration_table m
                             INNER JOIN animal a ON a.id = m.animal_id AND (m.gender_in_database <> a.gender OR m.gender_in_database ISNULL)
                         ) AS v(animal_id, gender_in_database) WHERE animal_migration_table.animal_id = v.animal_id",

            'Update incongruent father_id where father_vsm_id = name in animal table ...' =>
                "UPDATE animal_migration_table SET father_id = v.father_id
                    FROM (
                           SELECT father_vsm_id, dad.id
                           FROM animal_migration_table m
                             INNER JOIN animal dad ON CAST(dad.name AS INTEGER) = m.father_vsm_id
                                                      AND (father_id ISNULL OR father_id <> dad.id)
                                                      AND dad.type = 'Ewe'
                           WHERE dad.name NOTNULL AND vsm_id NOT IN (
                             --SKIP duplicate vsmIds in database
                             SELECT CAST(name AS INTEGER)
                             FROM animal
                             WHERE name NOTNULL
                             GROUP BY name HAVING COUNT(*) > 1
                           )
                         ) AS v(father_vsm_id, father_id) WHERE animal_migration_table.father_vsm_id = v.father_vsm_id",

            'Update incongruent mother_id where mother_vsm_id = name in animal table ...' =>
                "UPDATE animal_migration_table SET mother_id = v.mother_id
                    FROM (
                           SELECT mother_vsm_id, mom.id
                           FROM animal_migration_table m
                             INNER JOIN animal mom ON CAST(mom.name AS INTEGER) = m.mother_vsm_id
                                                      AND (mother_id ISNULL OR mother_id <> mom.id)
                                                      AND mom.type = 'Ewe'
                           WHERE mom.name NOTNULL AND vsm_id NOT IN (
                             --SKIP duplicate vsmIds in database
                             SELECT CAST(name AS INTEGER)
                             FROM animal
                             WHERE name NOTNULL
                             GROUP BY name HAVING COUNT(*) > 1
                           )
                         ) AS v(mother_vsm_id, mother_id) WHERE animal_migration_table.mother_vsm_id = v.mother_vsm_id",

            'Update incongruent location_of_birth_id where ubn_of_birth = ubn in location table ...' =>
            "UPDATE animal_migration_table SET location_of_birth_id = v.location_id
                FROM (
                  SELECT m.id, l.id
                  FROM animal_migration_table m
                    INNER JOIN location l ON l.ubn = m.ubn_of_birth
                                             AND (m.location_of_birth_id ISNULL
                                                    OR m.location_of_birth_id <> l.id)
                                             AND l.is_active
                  WHERE l.ubn NOT IN (
                    --ignore duplicate active ubns
                    SELECT ubn FROM location WHERE is_active
                    GROUP BY ubn HAVING COUNT(*) > 1
                  )
                ) AS v(id, location_id) WHERE animal_migration_table.id = v.id",

            'Fill missing uln_country_code and uln_number from uln that is in stn_origin column ...' =>
            "UPDATE animal_migration_table SET uln_country_code = v.uln_country_code, uln_number = v.uln_number, is_uln_updated = TRUE
                FROM (
                  SELECT
                    id,
                    substr(stn_origin, 1,2) as uln_country_code,
                    substr(stn_origin, 4, length(stn_origin)) as uln_number,
                    regexp_matches(stn_origin, '([A-Z]{2})+[ ]+([0-9]{8,12})')
                  FROM animal_migration_table
                  WHERE uln_number ISNULL
                ) AS v(id, uln_country_code, uln_number, regex_matches)
                WHERE animal_migration_table.id = v.id
                   AND animal_migration_table.uln_number ISNULL",

            'Fix incongruent animalOrderNumbers ...' =>
            "UPDATE animal_migration_table SET animal_order_number = substr(uln_number, length(uln_number)-4,5)
                WHERE animal_order_number <> substr(uln_number, length(uln_number)-4,5) OR
                      (animal_order_number ISNULL AND uln_number NOTNULL)",

        ];

        foreach ($queries as $title => $sql) {
            $this->updateBySql($title, $sql);
        }
    }


    private function fixValues()
    {
        $this->markUnreliableParents();
        $this->fixGenderOfNeutersByMigrationValues();
        $this->fixValuesByInstructionsOfReinard();
    }


    private function markUnreliableParents()
    {
        $sql = "UPDATE animal_migration_table SET is_unreliable_parent = TRUE 
                    WHERE vsm_id IN (
                      --Males that are also mothers in csv
                      SELECT amt.vsm_id
                      FROM animal_migration_table amt
                        INNER JOIN (
                                     SELECT m.vsm_id, COUNT(m.id) as mother_count_in_csv
                                     FROM animal_migration_table m
                                       INNER JOIN animal_migration_table c ON c.mother_vsm_id = m.vsm_id
                                     WHERE m.gender_in_file = 'MALE'
                                     GROUP BY m.vsm_id
                                   )g ON g.vsm_id = amt.vsm_id
                        LEFT JOIN (
                                    SELECT m.vsm_id, COUNT(m.id) as father_count_in_csv
                                    FROM animal_migration_table m
                                      INNER JOIN animal_migration_table c ON c.father_vsm_id = m.vsm_id
                                    WHERE m.gender_in_file = 'MALE'
                                    GROUP BY m.vsm_id
                                  )mc ON mc.vsm_id = amt.vsm_id
                        LEFT JOIN pedigree_register r ON r.id = amt.pedigree_register_id
                      UNION
                      --Females that are also fathers in csv
                      SELECT amt.vsm_id
                      FROM animal_migration_table amt
                        INNER JOIN (
                                     SELECT m.vsm_id, COUNT(m.id) as father_count_in_csv
                                     FROM animal_migration_table m
                                       INNER JOIN animal_migration_table c ON c.father_vsm_id = m.vsm_id
                                     WHERE m.gender_in_file = 'FEMALE'
                                     GROUP BY m.vsm_id
                                   )g ON g.vsm_id = amt.vsm_id
                        LEFT JOIN (
                                    SELECT m.vsm_id, COUNT(m.id) as mother_count_in_csv
                                    FROM animal_migration_table m
                                      INNER JOIN animal_migration_table c ON c.mother_vsm_id = m.vsm_id
                                    WHERE m.gender_in_file = 'FEMALE'
                                    GROUP BY m.vsm_id
                                  )mc ON mc.vsm_id = amt.vsm_id
                        LEFT JOIN pedigree_register r ON r.id = amt.pedigree_register_id   
                    ) AND is_unreliable_parent = FALSE";
        $this->updateBySql('Mark unreliable parents (Ewes as father, Rams as mother) ...', $sql);
    }


    private function fixGenderOfNeutersByMigrationValues()
    {
        $this->writeLn('=== Fix Neuters in animal table by new genders in animal_migration_table ===');

        $queries = [];
        $neuterGender = GenderType::NEUTER;

        $totalUpdateCount = 0;

        foreach (['Ram' => 'MALE', 'Ewe' => 'FEMALE'] as $type => $gender) {

            $sql =
                "UPDATE animal SET type = '$type', gender = '$gender' WHERE id IN(
                  SELECT animal_id
                  FROM animal_migration_table
                  WHERE gender_in_file = '$gender' AND gender_in_database = '$neuterGender'
                        AND animal_id NOTNULL
                        AND is_unreliable_parent = FALSE
                ) AND type = 'Neuter'";

            $updateCount = $this->updateBySql('Updating Neuters in animal table by '.$type.' gender in migration data ...', $sql);
            $totalUpdateCount += $updateCount;

            if($updateCount > 0) {

                $table = strtolower($type);

                $queries['Inserting new records in ram table ...'] =
                    "INSERT INTO $table (id, object_type)
                      SELECT animal_id, '$type'
                      FROM animal_migration_table
                        LEFT JOIN $table r ON r.id = animal_id
                      WHERE gender_in_file = '$gender' AND gender_in_database = '$neuterGender'
                            AND animal_id NOTNULL
                            AND r.id ISNULL --check if the record does not already exists
                            AND is_unreliable_parent = FALSE";

                $queries['Deleting orphaned records in neuter table ...'] =
                    "DELETE FROM neuter WHERE id IN (
                      SELECT animal_id
                      FROM animal_migration_table
                        LEFT JOIN neuter n ON n.id = animal_id
                      WHERE gender_in_file = '$gender' AND gender_in_database = '$neuterGender'
                            AND animal_id NOTNULL
                            AND n.id NOTNULL --check if the record still exists
                            AND is_unreliable_parent = FALSE
                      )";

                $queries['Saving Neuter to '.$type.' change in gender_history_item ...'] =
                    "INSERT INTO gender_history_item (animal_id, log_date, previous_gender, new_gender)
                      SELECT amt.animal_id, NOW(), 'Neuter' as previous_gender, '$type' as new_gender
                      FROM animal_migration_table amt
                        LEFT JOIN gender_history_item g ON g.animal_id = amt.animal_id
                        LEFT JOIN (
                          --Find the last gender_history_item of the animal
                          SELECT last_g.* FROM gender_history_item last_g
                            INNER JOIN (
                                         SELECT animal_id, MAX(log_date) as max_log_date FROM gender_history_item
                                         GROUP BY animal_id
                                       )gg ON gg.animal_id = last_g.animal_id AND gg.max_log_date = last_g.log_date
                          )last_g ON last_g.animal_id = g.animal_id
                      WHERE gender_in_file = '$gender' AND gender_in_database = '$neuterGender'
                            AND amt.animal_id NOTNULL
                            AND is_unreliable_parent = FALSE
                            --check if the record does not already exists
                            AND g.animal_id ISNULL OR
                            (last_g.previous_gender <> 'Neuter' AND last_g.new_gender <> '$type')";

                foreach ($queries as $title => $sql) {
                    $this->updateBySql($title, $sql);
                }

            }
        }

        if ($totalUpdateCount > 0) {
            DatabaseDataFixer::fixGenderTables($this->conn, $this->cmdUtil);
        }
    }


    private function extractBreederNumbers()
    {
        $this->writeLn('=== Extracting breederNumbers from pedigreeNumbers and ubnOfBirths and update breeder_number table ===');

        $sql = "SELECT m.ubn_of_birth,
                  substr(stn_origin, 4, 5) as breeder_number,
                  regexp_matches(stn_origin, '([A-Z]{2}[ ][A-Z0-9]{5}[-][a-zA-Z0-9]{5})')
                FROM animal_migration_table m
                  --LEFT JOIN breeder_number b ON b.breeder_number = substr(stn_origin, 4, 5)
                WHERE m.ubn_of_birth NOTNULL";
        $ungroupedResults = $this->conn->query($sql)->fetchAll();

        $results = [];
        foreach ($ungroupedResults as $result) {
            $ubnOfBirth = $result['ubn_of_birth'];
            $breederNumber = $result['breeder_number'];
            if (!key_exists($breederNumber, $results)) {
                $results[$breederNumber] = $ubnOfBirth;
            }
        }

        $sql = "SELECT breeder_number, ubn_of_birth FROM breeder_number";
        $currentUbnOfBirthByBreederNumbers = SqlUtil::groupSqlResultsOfKey1ByKey2(
            'ubn_of_birth', 'breeder_number', $this->conn->query($sql)->fetchAll());

        DoctrineUtil::updateTableSequence($this->conn, ['breeder_number']);

        $this->sqlBatchProcessor
            ->purgeAllSets()
            ->createBatchSet(QueryType::INSERT)
            ->createBatchSet(QueryType::UPDATE)
        ;

        $insertBatchSet = $this->sqlBatchProcessor->getSet(QueryType::INSERT);
        $updateBatchSet = $this->sqlBatchProcessor->getSet(QueryType::UPDATE);

        $insertBatchSet->setSqlQueryBase("INSERT INTO breeder_number (breeder_number, ubn_of_birth, source) VALUES ");

        $updateSqlBaseStart = "UPDATE breeder_number 
                                SET ubn_of_birth = v.ubn_of_birth, source = 'ANIMAL_MIGRATION_TABLE'
                                FROM ( VALUES ";
        $updateSqlBaseEnd = ") AS v(breeder_number, ubn_of_birth) 
                               WHERE breeder_number.breeder_number = v.breeder_number
                                AND breeder_number.ubn_of_birth <> v.ubn_of_birth";
        $updateBatchSet->setSqlQueryBase($updateSqlBaseStart);
        $updateBatchSet->setSqlQueryBaseEnd($updateSqlBaseEnd);

        $this->sqlBatchProcessor->start(count($results));

        $breederNumbersToBeInserted = [];
        $breederNumbersToBeUpdated = [];

        foreach ($results as $breederNumber => $ubnOfBirth) {

            if (key_exists($breederNumber, $currentUbnOfBirthByBreederNumbers)) {
                //Record already exists
                if ($currentUbnOfBirthByBreederNumbers[$breederNumber] === $ubnOfBirth) {
                    $insertBatchSet->incrementSkippedCount();
                    $updateBatchSet->incrementSkippedCount();
                    $this->sqlBatchProcessor->advanceProgressBar();
                    continue;

                } else {
                    if (key_exists($breederNumber, $breederNumbersToBeUpdated)) {
                        //BreedNumber is already in update batch
                        $insertBatchSet->incrementSkippedCount();
                        $updateBatchSet->incrementSkippedCount();
                        $this->sqlBatchProcessor->advanceProgressBar();
                        continue;
                    } else {
                        //Record values must be updated
                        $updateBatchSet->appendValuesString("('".$breederNumber."','".$ubnOfBirth."')");
                        $insertBatchSet->incrementSkippedCount();
                        $updateBatchSet->incrementBatchCount();
                        $breederNumbersToBeUpdated[$breederNumber] = $breederNumber;
                    }
                }

            } else {
                //BreedNumber is already in insert batch
                if (key_exists($breederNumber, $breederNumbersToBeInserted)) {
                    $insertBatchSet->incrementSkippedCount();
                    $updateBatchSet->incrementSkippedCount();
                    $this->sqlBatchProcessor->advanceProgressBar();
                    continue;

                } else {
                    //BreedNumber record does not exist yet & is not in batch
                    $insertBatchSet->appendValuesString("('".$breederNumber."','".$ubnOfBirth."','ANIMAL_MIGRATION_TABLE')");
                    $insertBatchSet->incrementBatchCount();
                    $updateBatchSet->incrementSkippedCount();
                    $breederNumbersToBeInserted[$breederNumber] = $breederNumber;
                }
            }
            $this->sqlBatchProcessor
                ->processAtBatchSize()
                ->advanceProgressBar()
            ;
        }
        $breederNumbersToBeInserted = null;
        $results = null;
        $this->sqlBatchProcessor->end()->purgeAllSets();

        DoctrineUtil::updateTableSequence($this->conn, ['breeder_number']);
    }


    /**
     * Note! Fix neuter genders first
     */
    private function fixValuesByInstructionsOfReinard()
    {
        $this->writeLn('=== Fix values in animal_migration_table using the instructions from Reinard ===');

        /*
         * The following notes are from the file of Reinard
         * FL100_20170524_Instructie-inlezen-data.docx
         * 2017 May 24
         */

        $queries = [
            /*
             * 1. Er zijn behoorlijk wat FL100 dieren die als stamboek ‘NTS: Nederlands Texels Schapenstamboek’
             * of ‘TSNH: Texels Schapenstamboek in Noord-Holland’ of ‘* Onbekend stamboek schaap’ hebben,
             * en daarnaast ook de status ‘Volbloed’ hebben. Deze dieren mogen allemaal als NFS worden ingelezen.
             */
            'Fix 1) Fix FL100 pedigreeRegisters ...' =>
                "UPDATE animal_migration_table
                    SET pedigree_register_id = (SELECT id FROM pedigree_register WHERE abbreviation = 'NFS')
                    WHERE breed_code = 'FL100'
                      AND (pedigree_register_id IN (
                        SELECT id
                        FROM pedigree_register
                        WHERE abbreviation = 'NTS' OR abbreviation = 'TSNH'
                      ) OR pedigree_register_id ISNULL)
                      AND breed_type = 'PURE_BRED'",

            /*
             * 2. Er is een groot aantal dieren waar de rasstatus voor dieren met rasbalk ‘FL100’ niet is ingevuld.
             * Wanneer de beide ouders bekend zijn EN de rasbalk is FL100 dan mogen deze dieren
             * de status ‘Volbloed’ krijgen (het gaat om zo’n 8000 dieren waar dit voor geldt).
             */
            'Fix 2) Fill missing PURE_BRED values for FL100 ...' =>
                "UPDATE animal_migration_table SET breed_type = 'PURE_BRED'
                      WHERE vsm_id 
                      IN (
                            SELECT c.vsm_id FROM animal_migration_table c
                              INNER JOIN animal_migration_table f ON f.vsm_id = c.father_vsm_id
                              INNER JOIN animal_migration_table m ON m.vsm_id = c.mother_vsm_id
                            WHERE c.breed_code = 'FL100' AND c.breed_type ISNULL
                                  AND ((m.gender_in_database <> 'NEUTER' AND m.gender_in_database <> 'MALE') OR m.gender_in_database ISNULL)
                                  AND m.is_unreliable_parent = FALSE
                                  AND ((f.gender_in_database <> 'NEUTER' AND f.gender_in_database <> 'FEMALE') OR f.gender_in_database ISNULL)
                                  AND f.is_unreliable_parent = FALSE
                                  AND f.breed_code = 'FL100' AND m.breed_code = 'FL100'   
                          )",

            /*
             * 3. Waar status is ‘Volbloed’ of ‘Register’, rasbalk is ‘FL100’ en veld voor stamboek is _niet_ gevuld,
             * maak daar van ‘NFS’ als stamboek.
             */
            'Fix 3) Set predigreeRegister = NFS where breed_code = FL100 and breed_type = PURE_BRED or REGISTER ...'=>
                "UPDATE animal_migration_table
                    SET pedigree_register_id = (SELECT id FROM pedigree_register WHERE abbreviation = 'NFS')
                    WHERE vsm_id IN (
                      SELECT vsm_id FROM animal_migration_table
                      WHERE (breed_type = 'REGISTER' OR breed_type = 'PURE_BRED')
                            AND breed_code = 'FL100' AND pedigree_register_id ISNULL
                    ) AND pedigree_register_id ISNULL",

            /*
             * 4. Waar het werknummer in het stamboeknummer bij dieren met de rasbalk ‘FL100’ *6* posities bevatten,
             * is de eerste positie altijd een letter. Knip deze letter eraf,
             * lees de rest van het werknummer binnen het stamboeknummer in als het werknummer met 5 posities.
             * Plaats de letter die eraf geknipt is in het veld voor ‘Naam’.
             *
             *      a. Voorbeeld: NL 09019-N00029 wordt dan NL 09019-00029
             *         waarbij de letter N in het veld voor ‘Naam’ wordt gezet.
             *
             * 5. Waar het werknummer in het stamboeknummer bij dieren met de rasbalk ‘FL100’
             * *5* posities bevatten *EN* de eerste positie een letter is, doe daar het volgende.
             * Kopieer deze letter, houdt het werknummer intact en kopieer de letter in het veld voor ‘Naam’.
             */
            'Fix 4 & 5: letter extraction) extracting the prefix letters in last part of STN' =>
                "UPDATE animal_migration_table
                    SET nick_name = v.stn_prefix_letters, stn_prefix_letters = v.stn_prefix_letters
                    FROM (
                      -- length animalOrderNumber part of stn = 5, and has 1 leading letters
                      SELECT vsm_id, regexp_matches(stn_origin, '([A-Z]{2}[ ][A-Z0-9]{5}[-][a-zA-Z0-9]{5})'),
                        substr(stn_origin, 10,1),
                        regexp_matches(substr(stn_origin, 10,1), '[A-Z]{1}'),
                        regexp_matches(substr(stn_origin, 11,1), '[0-9]{1}') as trailing_check
                      FROM animal_migration_table
                      WHERE length(stn_origin) = 14
                            AND breed_code = 'FL100'
                      UNION
                      -- length animalOrderNumber part of stn = 5, and has 2 leading letters
                      SELECT vsm_id, regexp_matches(stn_origin, '([A-Z]{2}[ ][A-Z0-9]{5}[-][a-zA-Z0-9]{5})'),
                        substr(stn_origin, 10,2),
                        regexp_matches(substr(stn_origin, 10,2), '[A-Z]{2}'),
                        null as trailing_check
                      FROM animal_migration_table
                      WHERE length(stn_origin) = 14
                            AND breed_code = 'FL100'
                      UNION
                      -- length animalOrderNumber part of stn = 6, and has 2 leading letters
                      SELECT
                        vsm_id, regexp_matches(stn_origin, '([A-Z]{2}[ ][A-Z0-9]{5}[-][a-zA-Z0-9]{6})'),
                        substr(stn_origin, 10,2),
                        regexp_matches(substr(stn_origin, 10,2), '[A-Z]{2}'),
                        null as trailing_check
                      FROM animal_migration_table
                      WHERE length(stn_origin) = 15
                            AND breed_code = 'FL100'
                      UNION
                      -- length animalOrderNumber part of stn = 6, and has 1 leading letter
                      SELECT
                        vsm_id, regexp_matches(stn_origin, '([A-Z]{2}[ ][A-Z0-9]{5}[-][a-zA-Z0-9]{6})'),
                        substr(stn_origin, 10,1),
                        regexp_matches(substr(stn_origin, 10,1), '[A-Z]{1}'),
                        regexp_matches(substr(stn_origin, 11,1), '[0-9]{1}') as trailing_check
                      FROM animal_migration_table
                      WHERE length(stn_origin) = 15
                            AND breed_code = 'FL100'
                    ) AS v(vsm_id, regex1, stn_prefix_letters, regex2, trailing_check)
                    WHERE animal_migration_table.vsm_id = v.vsm_id
                          AND (nick_name ISNULL OR nick_name <> v.stn_prefix_letters
                              OR animal_migration_table.stn_prefix_letters ISNULL
                              OR animal_migration_table.stn_prefix_letters <> v.stn_prefix_letters
                          )",

            /*
             * 4. Waar het werknummer in het stamboeknummer bij dieren met de rasbalk ‘FL100’ *6* posities bevatten,
             * is de eerste positie altijd een letter. Knip deze letter eraf,
             * lees de rest van het werknummer binnen het stamboeknummer in als het werknummer met 5 posities.
             * Plaats de letter die eraf geknipt is in het veld voor ‘Naam’.
             */
            'Fix 4: STN fix) If last part of STN has length of 6, cut of the first letter' =>
                "UPDATE animal_migration_table
                    SET pedigree_country_code = v.pedigree_country_code, pedigree_number = v.pedigree_number, is_stn_updated = TRUE
                    FROM (
                      SELECT
                        vsm_id, stn_origin,
                        regexp_matches(stn_origin, '([A-Z]{2}[ ][A-Z0-9]{5}[-][a-zA-Z0-9]{6})'),
                        substr(stn_origin, 1,2) as pedigree_country_code,
                        CONCAT(substr(stn_origin, 4,6),substr(stn_origin, 11,5)) as pedigree_number
                      FROM animal_migration_table
                      WHERE length(stn_origin) = 15
                            AND breed_code = 'FL100'
                    ) AS v(vsm_id, stn_origin, regex, pedigree_country_code, pedigree_number)
                    WHERE animal_migration_table.vsm_id = v.vsm_id
                      AND (
                            animal_migration_table.pedigree_country_code ISNULL OR
                            animal_migration_table.pedigree_country_code <> v.pedigree_country_code OR
                            animal_migration_table.pedigree_number ISNULL OR
                            animal_migration_table.pedigree_number <> v.pedigree_number
                          )",

            /*
             * 6. Waar het stamboeknummer is gelijk aan het ULN nummer (bijv. bij NL 100125536154), kijk of er op het
             * zelfde geboorte UBN (in dit geval UBN 1121115) of er dieren zijn met een stamboeknummer
             * met daarin het – teken. Dit blijkt er te zijn, bijv. NL 09084-36155.
             * Het stamboeknummer van NL 100125536154 moet dus zijn NL 09084-36154.
             */
            'Fix 6, part 1: in animal_migration_table)' =>
                "UPDATE animal_migration_table SET pedigree_country_code = new_pedigree_country_code, pedigree_number = new_pedigree_number, is_stn_updated = TRUE
                FROM (
                       SELECT amt.vsm_id, uln_country_code,
                         --uln_number, amt.ubn_of_birth, g.breeder_number, substr(uln_number, length(uln_number)-4,5) as animal_order_number,
                         CONCAT(g.breeder_number,'-',substr(uln_number, length(uln_number)-4,5)) as new_pedigree_number
                       FROM animal_migration_table amt
                         INNER JOIN (
                                      --In case of multiple breeders per ubn, select the lowest breederNumber
                                      SELECT b.ubn_of_birth, MIN(b.breeder_number) as breeder_number
                                      FROM breeder_number b
                                        INNER JOIN (
                                                     --Prioritize data from animal migration table
                                                     SELECT ubn_of_birth, MIN(source) as source
                                                     FROM breeder_number
                                                     GROUP BY ubn_of_birth
                                                   )g ON g.ubn_of_birth = b.ubn_of_birth AND g.source = b.source
                                      GROUP BY b.ubn_of_birth
                                    )g ON g.ubn_of_birth = amt.ubn_of_birth
                       WHERE pedigree_number ISNULL AND uln_number NOTNULL AND breed_code ='FL100'
                ) AS v(vsm_id, new_pedigree_country_code, new_pedigree_number) WHERE animal_migration_table.vsm_id = v.vsm_id",

            'Fix 6, part 2: in animal table)' =>
                "UPDATE animal SET pedigree_country_code = new_pedigree_country_code, pedigree_number = new_pedigree_number
                      FROM (
                          SELECT a.id as animal_id, uln_country_code,
                          --uln_number, a.ubn_of_birth, g.breeder_number, substr(uln_number, length(uln_number)-4,5) as animal_order_number,
                          CONCAT(g.breeder_number,'-',substr(uln_number, length(uln_number)-4,5)) as new_pedigree_number
                          FROM animal a
                          INNER JOIN (
                          --In case of multiple breeders per ubn, select the lowest breederNumber
                          SELECT b.ubn_of_birth, MIN(b.breeder_number) as breeder_number
                          FROM breeder_number b
                          INNER JOIN (
                          --Prioritize data from animal migration table
                          SELECT ubn_of_birth, MIN(source) as source
                          FROM breeder_number
                          GROUP BY ubn_of_birth
                          )g ON g.ubn_of_birth = b.ubn_of_birth AND g.source = b.source
                          GROUP BY b.ubn_of_birth
                          )g ON g.ubn_of_birth = a.ubn_of_birth
                          WHERE pedigree_number ISNULL AND uln_number NOTNULL AND breed_code ='FL100'
                      ) AS v(animal_id, new_pedigree_country_code, new_pedigree_number) WHERE animal.id = animal_id"
        ];

        foreach ($queries as $title => $sql) {
            $this->updateBySql($title, $sql);
        }
    }



    /**
     *
     */
    private function printPedigreeRegistersInCsvFile()
    {
        $this->parseAnimalTableCsv();
        $this->writeLn('=== Print PedigreeRegisters in CSV file ===');

        $pedigreeRegistersInCsvFile = [];

        $registerCount = 0;
        $this->cmdUtil->setStartTimeAndPrintIt(count($this->data)+1, 1);
        foreach ($this->data as $record) {

            $pedigreeRegister = $record[11];

            if (is_string($pedigreeRegister) && $pedigreeRegister != '' && !key_exists($pedigreeRegister, $pedigreeRegistersInCsvFile)) {
                $pedigreeRegistersInCsvFile[$pedigreeRegister] = $pedigreeRegister;
                $registerCount++;
            }

            $this->cmdUtil->advanceProgressBar(1, 'found unique registers: '.$registerCount);
        }
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();

        $this->writeLn($pedigreeRegistersInCsvFile);
        $this->cmdUtil->printClosingLine();
    }


    /**
     * @param $parentVsmId
     * @return string
     */
    private function getParentVsmIdForSqlQuery($parentVsmId)
    {
        if (!is_int($parentVsmId) && !ctype_digit($parentVsmId)) { $parentVsmId = null; }
        return SqlUtil::getNullCheckedValueForSqlQuery($parentVsmId, false);
    }
}