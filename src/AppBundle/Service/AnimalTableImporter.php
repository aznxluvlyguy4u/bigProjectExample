<?php


namespace AppBundle\Service;

use AppBundle\Component\Builder\CsvOptions;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Enumerator\GenderType;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\CsvParser;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\FilesystemUtil;
use AppBundle\Util\SqlBatchProcessorWithProgressBar;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\Translation;
use AppBundle\Util\Validator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class AnimalTableImporter
 */
class AnimalTableImporter
{
    const DEFAULT_OPTION = 0;
    const INSERT_BATCH_SIZE = 10000;

    //CsvOptions
    const IMPORT_SUB_FOLDER = 'vsm2017jun/';
    const ANIMAL_TABLE_FILENAME = '20170411_1022_Diertabel.csv';
    /** @var CsvOptions */
    private $csvOptions;

    /** @var EntityManagerInterface|ObjectManager */
    private $em;
    /** @var Connection */
    private $conn;
    /** @var CommandUtil */
    private $cmdUtil;
    /** @var string */
    private $rootDir;
    /** @var SqlBatchProcessorWithProgressBar */
    private $sqlBatchProcessor;

    /** @var array */
    private $data;

    //Search arrays
    private $pedigreeRegisterIdsByAbbreviation;


    /**
     * VsmMigratorService constructor.
     * @param ObjectManager $em
     * @param string $rootDir
     */
    public function __construct(ObjectManager $em, $rootDir)
    {
        $this->em = $em;
        $this->conn = $this->em->getConnection();
        $this->rootDir = $rootDir;

        $this->data = [];

        $this->csvOptions = (new CsvOptions())
            ->appendDefaultInputFolder(self::IMPORT_SUB_FOLDER)
            ->appendDefaultOutputFolder(self::IMPORT_SUB_FOLDER)
            ->setFileName(self::ANIMAL_TABLE_FILENAME)
            ->ignoreFirstLine()
            ->setSemicolonSeparator()
        ;
    }


    /**
     * @param CommandUtil $cmdUtil
     */
    public function run(CommandUtil $cmdUtil)
    {
        if($this->cmdUtil === null) { $this->cmdUtil = $cmdUtil; }
        $this->cmdUtil->writeln(DoctrineUtil::getDatabaseHostAndNameString($this->em));
        $this->cmdUtil->writeln('');

        //Setup folders if missing
        FilesystemUtil::createFolderPathsFromCsvOptionsIfNull($this->rootDir, $this->csvOptions);

        $this->sqlBatchProcessor = new SqlBatchProcessorWithProgressBar($this->conn, $this->cmdUtil, self::INSERT_BATCH_SIZE);

        $option = $this->cmdUtil->generateMultiLineQuestion([
            ' ', "\n",
            'Choose option: ', "\n",
            '1: Import AnimalTable csv file into database', "\n",
            '2: Update animal_migration_table empty animal table values', "\n",
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
//            case 3:
//                break;
//            case 4:
//                break;
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


    private function parseCsv()
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

        $this->parseCsv();

        $this->writeLn('Initializing search arrays ...');

        //Initialize searchArrays
        $sql = "SELECT vsm_id FROM animal_migration_table";
        $results = $this->conn->query($sql)->fetchAll();
        $processedAnimals = SqlUtil::groupSqlResultsOfKey1ByKey2('vsm_id', 'vsm_id', $results);

        DoctrineUtil::updateTableSequence($this->conn, ['animal_migration_table']);

        $sqlBase = "INSERT INTO animal_migration_table (id, vsm_id, uln_origin, stn_origin, uln_country_code, uln_number, animal_order_number,
						pedigree_country_code, pedigree_number, nick_name, father_vsm_id, mother_vsm_id, gender_in_file, date_of_birth,breed_code,ubn_of_birth,pedigree_register_id,breed_type,scrapie_genotype
						)VALUES ";

        $this->sqlBatchProcessor
            ->setSqlQueryBase($sqlBase)
            ->start(count($this->data))
        ;


        foreach ($this->data as $record) {

            $vsmId = intval($record[0]);
            if(array_key_exists($vsmId, $processedAnimals)) {
                $this->sqlBatchProcessor
                    ->incrementAlreadyImportedCount()
                    ->advanceProgressBar();
                continue;
            }

            $uln = StringUtil::getNullAsStringOrWrapInQuotes($record[3]);
            $ulnParts = $this->parseUln($record[3]);
            $ulnCountryCode = StringUtil::getNullAsStringOrWrapInQuotes($ulnParts[JsonInputConstant::ULN_COUNTRY_CODE]);
            $ulnNumber = StringUtil::getNullAsStringOrWrapInQuotes($ulnParts[JsonInputConstant::ULN_NUMBER]);

            if($ulnCountryCode == "'XD'") { // These are testAnimals and should be skipped
                $this->sqlBatchProcessor
                    ->incrementSkippedCount()
                    ->advanceProgressBar();
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
            $dateOfBirthString = StringUtil::getNullAsStringOrWrapInQuotes($record[8]);
            $breedCode = StringUtil::getNullAsStringOrWrapInQuotes($record[9]);
            $ubnOfBirth = StringUtil::getNullAsStringOrWrapInQuotes($record[10]); //ubnOfBreeder

            $pedigreeRegisterId = $this->getPedigreeRegisterId($record[11]);
            $breedType = SqlUtil::getNullCheckedValueForSqlQuery(Translation::getEnglish(strtoupper($record[12])), true);
            $scrapieGenotype = SqlUtil::getNullCheckedValueForSqlQuery($record[13], true);

            //Insert new record, process it as a batch

            $sqlInsertGroup = "(nextval('animal_migration_table_id_seq'),".$vsmId.",".$uln.",".$stnImport.",".$ulnCountryCode.",".$ulnNumber.",".$animalOrderNumber.",".$pedigreeCountryCode.",".$pedigreeNumber.",".$nickName.",".$fatherVsmId.",".$motherVsmId.",".$genderInFile.",".$dateOfBirthString.",".$breedCode.",".$ubnOfBirth.",".$pedigreeRegisterId.",".$breedType.",".$scrapieGenotype.")";

            $this->sqlBatchProcessor
                ->appendInsertString($sqlInsertGroup)
                ->insertAtBatchSize()
                ->advanceProgressBar()
            ;
        }
        $this->sqlBatchProcessor->end();

        DoctrineUtil::updateTableSequence($this->conn, ['animal_migration_table']);
    }


    /**
     * @param $line
     */
    private function writeLn($line)
    {
        $this->cmdUtil->writeln($line);
    }


    /**
     *
     * @param string $ulnString
     * @return array
     */
    public static function parseUln($ulnString)
    {
        if(Validator::verifyUlnFormat($ulnString, true)) {
            $parts = explode(' ', $ulnString);
            $parts[0] = str_replace('GB', 'UK', $parts[0]);
        } else {
            $parts = [null, null];
        }

        return [
            JsonInputConstant::ULN_COUNTRY_CODE => $parts[0],
            JsonInputConstant::ULN_NUMBER => $parts[1],
        ];

    }


    /**
     * @param string $gender
     * @return string
     */
    public static function parseGender($gender)
    {
        //The only genders in the file are 'M' and 'V'
        switch ($gender) {
            case GenderType::M: return GenderType::MALE;
            case GenderType::V: return GenderType::FEMALE;
            default: return GenderType::NEUTER;
        }
    }


    /**
     * @param string $stnString
     * @return array
     */
    public static function parseStn($stnString)
    {
        if(Validator::verifyPedigreeCountryCodeAndNumberFormat($stnString, true)) {
            $parts = explode(' ', $stnString);
            $parts[0] = str_replace('GB', 'UK', $parts[0]);
        } else {
            $parts = [null, null];
        }

        return [
            JsonInputConstant::PEDIGREE_COUNTRY_CODE => $parts[0],
            JsonInputConstant::PEDIGREE_NUMBER => $parts[1],
        ];
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



    private function updateValues()
    {
        $queries = [

            'Update incongruent animal_id where vsmId = name in animal table ...' =>
                "UPDATE animal_migration_table SET animal_id = v.animal_id
                    FROM (
                        SELECT vsm_id, a.id
                        FROM animal_migration_table m
                            INNER JOIN animal a ON CAST(a.name AS INTEGER) = m.vsm_id
                                                   AND (animal_id ISNULL
                                                        OR animal_id <> a.id
                                                   )
                        WHERE a.name NOTNULL AND vsm_id NOT IN (
                            --SKIP duplicate vsmIds in database
                            SELECT CAST(a.name AS INTEGER)
                            FROM animal
                            WHERE name NOTNULL
                            GROUP BY name HAVING COUNT(*) > 1
                        )
                    ) AS v(vsm_id, animal_id) WHERE animal_migration_table.vsm_id = v.vsm_id",

            'Update incongruent gender_in_database where vsmId = name in animal table ...' =>
                "UPDATE animal_migration_table SET gender_in_database = v.gender_in_database
                    FROM (
                        SELECT vsm_id, a.gender
                        FROM animal_migration_table m
                            INNER JOIN animal a ON CAST(a.name AS INTEGER) = m.vsm_id
                                                   AND (m.gender_in_database <> a.gender
                                                        OR m.gender_in_database ISNULL AND a.gender NOTNULL
                                                   )
                        WHERE a.name NOTNULL AND vsm_id NOT IN (
                            --SKIP duplicate vsmIds in database
                            SELECT CAST(a.name AS INTEGER)
                            FROM animal
                            WHERE name NOTNULL
                            GROUP BY name HAVING COUNT(*) > 1
                        )
                    ) AS v(vsm_id, gender_in_database) WHERE animal_migration_table.vsm_id = v.vsm_id",

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
                             SELECT CAST(dad.name AS INTEGER)
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
                             SELECT CAST(mom.name AS INTEGER)
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
                ) AS v(id, location_id) WHERE animal_migration_table.id = v.id"

        ];

        foreach ($queries as $title => $sql) {
            $this->updateBySql($title, $sql);
        }
    }


    private function updateBySql($title, $sql)
    {
        $this->writeLn($title);
        $count = SqlUtil::updateWithCount($this->conn, $sql);
        $prefix = $count === 0 ? 'No' : $count;
        $this->writeLn($prefix . ' records updated');
    }



    /**
     *
     */
    private function printPedigreeRegistersInCsvFile()
    {
        $this->parseCsv();
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