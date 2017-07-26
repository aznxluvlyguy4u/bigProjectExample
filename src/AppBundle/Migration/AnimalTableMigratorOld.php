<?php


namespace AppBundle\Migration;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\AnimalMigrationTable;
use AppBundle\Entity\AnimalMigrationTableRepository;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\BreederNumber;
use AppBundle\Entity\BreederNumberRepository;
use AppBundle\Entity\DeclareTagReplace;
use AppBundle\Entity\DeclareTagReplaceRepository;
use AppBundle\Entity\PedigreeRegister;
use AppBundle\Entity\VsmIdGroup;
use AppBundle\Entity\VsmIdGroupRepository;
use AppBundle\Enumerator\ColumnType;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\Specie;
use AppBundle\Service\AnimalTableImporter;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Util\Translation;
use AppBundle\Util\Validator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Output\OutputInterface;

class AnimalTableMigratorOld extends MigratorBase
{
	const PRINT_OUT_INVALID_UBNS_OF_BIRTH = true;
	const PRINT_OUT_FILENAME_INCORRECT_GENDERS = true;
	const UBNS_PER_ROW = 8;
	const INSERT_BATCH_SIZE = 1000;
	const UPDATE_BATCH_SIZE = 1000;
	const FILENAME_CORRECTED_CSV = '2016nov_gecorrigeerde_diertabel.csv';
	const FILENAME_INCORRECT_ULNS = 'incorrect_ulns.csv';
	const FILENAME_INCORRECT_GENDERS = 'incorrect_genders.csv';
	const FILENAME_CSV_EXPORT = 'animal_migration_table.csv';
	const FILENAME_ANIMALS_NOT_FOUND_FILLING_PEDIGREE_REGISTERS = 'dieren_niet_gevonden_voor_vullen_stamboeken_2016dec19.csv';

	const TABLE_NAME_IN_SNAKE_CASE = 'animal_migration_table';
	
	const VALUE = 'VALUE';
	const ABBREVIATION = 'ABBREVIATION';

	//Column name
	const VSM_ID = 'VSM_ID';
	const STN = 'STN';
	const ANIMAL_ORDER_NUMBER = 'ANIMAL_ORDER_NUMBER';
	const ULN = 'ULN';
	const NICKNAME = 'NICKNAME';
	const VSM_ID_FATHER = 'VSM_ID_FATHER';
	const VSM_ID_MOTHER = 'VSM_ID_MOTHER';
	const GENDER = 'GENDER';
	const DATE_OF_BIRTH = 'DATE_OF_BIRTH';
	const BREED_CODE = 'BREED_CODE';
	const UBN_OF_BIRTH = 'UBN_OF_BIRTH';
	const PEDIGREE_REGISTER = 'PEDIGREE_REGISTER';
	const BREED_TYPE = 'BREED_TYPE';
	const SCRAPIE_GENOTYPE = 'SCRAPIE_GENOTYPE';

	//PedigreeRegister
	const PR_UNKNOWN = '* Onbekend stamboek Schaap';
	const PR_EN_MANAGEMENT = 'EN-Management';
	const PR_EN_BASIS = 'EN-Basis';
	const PR_EN_MANAGEMENT_ABBREVIATION = null; //Set here or edit in db later
	const PR_EN_BASIS_ABBREVIATION = null; //Set here or edit in db later


	//IncorrectGenderFile
	const INCORRECT_GENDER_COLUMN_HEADERS = 'vsmId;GeslachtInCsv;isMoederInCsv;isVaderInCsv;GeslachtInDatabase;isMoederInDatabase;isVaderInDatabase;kinderenInCsvAlsMoeder;kinderenInCsvAlsVader; kinderenInDatabaseAlsMoeder; kinderenInDatabaseAlsVader;uln;stn;';

	/** @var AnimalMigrationTableRepository */
	private $animalMigrationTableRepository;

	/** @var BreederNumberRepository */
	private $breederNumberRepository;

	/** @var ArrayCollection $animalIdsByVsmId */
	private $animalIdsByVsmId;

	/** @var string */
	private $columnHeaders;

	/** @var array */
	private $parentVsmIdsUpdated;

	/** @var array */
	private $animalsByAnimalId;
	
	/** @var array */
	private $animalIdsOnLocation;
	
	/** @var array */
	private $animalIdByVsmId;
	
	/** @var array */
	private $genderByAnimalId;

	/** @var array */
	private $currentlyUsedUlns;

	/** @var array */
	private $historicUsedUlns;

	/** @var array */
	private $primaryVsmIdsForSecondaryIds;

	/**
	 * AnimalTableMigrator constructor.
	 * @param CommandUtil $cmdUtil
	 * @param ObjectManager $em
	 * @param OutputInterface $outputInterface
	 * @param array $data
	 * @param string $rootDir
	 * @param string $columnHeaders
	 */
	public function __construct(CommandUtil $cmdUtil, ObjectManager $em, OutputInterface $outputInterface, array $data, $rootDir, $columnHeaders = null)
	{
		parent::__construct($cmdUtil, $em, $outputInterface, $data, $rootDir);
		$this->animalMigrationTableRepository = $this->em->getRepository(AnimalMigrationTable::class);
		$this->breederNumberRepository = $this->em->getRepository(BreederNumber::class);
		$this->vsmIdGroupRepository = $this->em->getRepository(VsmIdGroup::class);
		$this->animalIdsByVsmId = $this->animalRepository->getAnimalPrimaryKeysByVsmId();
		$this->columnHeaders = $columnHeaders;
	}


	public function fixVsmIds()
	{
		//SearchArray
		$sql = "SELECT CONCAT(uln_country_code,uln_number,DATE(date_of_birth)) as search_key, name FROM animal;";
		$results = $this->conn->query($sql)->fetchAll();

		$ulnAndDateOfBirthByVsmIds = [];
		foreach ($results as $result) {
			$ulnAndDateOfBirthByVsmIds[$result['search_key']] = $result['name'];
		}

		$vsmIdsNotFound = 0;
		$correctVsmIds = 0;

		$updateString = '';
		$updateStringMother = '';
		$updateStringFather = '';
		$count = 0;
		$inBatchCount = 0;
		$updatedCount = 0;

		$totalCount = count($this->data);

		$this->cmdUtil->setStartTimeAndPrintIt($totalCount, 1);
		foreach ($this->data as $record) {

			$uln = StringUtil::getNullAsStringOrWrapInQuotes($record[3]);
			$ulnParts = AnimalTableImporter::parseUln($record[3]);

			$searchKey = $ulnParts[JsonInputConstant::ULN_COUNTRY_CODE].$ulnParts[JsonInputConstant::ULN_NUMBER].TimeUtil::fillDateStringWithLeadingZeroes($record[8]);

			if(array_key_exists($searchKey, $ulnAndDateOfBirthByVsmIds)) {

				$vsmIdInAnimal = intval($ulnAndDateOfBirthByVsmIds[$searchKey]);
				$vsmIdInCsv = intval($record[0]);

				if($vsmIdInAnimal != $vsmIdInCsv && $vsmIdInAnimal != null && $vsmIdInCsv != null && $vsmIdInAnimal != '' && $vsmIdInCsv != '') {
					$updateString = $updateString."('".$vsmIdInAnimal."','".$vsmIdInCsv."'),";

					$genderInFile = AnimalTableImporter::parseGender($record[7]);
					if($genderInFile == GenderType::FEMALE) {
						$updateStringMother = $updateStringMother."('".$vsmIdInAnimal."','".$vsmIdInCsv."'),";
					} elseif ($genderInFile == GenderType::MALE) {
						$updateStringFather = $updateStringFather."('".$vsmIdInAnimal."','".$vsmIdInCsv."'),";
					}
					$inBatchCount++;
				}

				$correctVsmIds++;
			} else {
				$vsmIdsNotFound++;
			}


			$count++;
			if($count == $totalCount || ($inBatchCount%self::UPDATE_BATCH_SIZE == 0 && $count != 0)) {

				$updateString = rtrim($updateString, ',');
				$updateStringMother = rtrim($updateStringMother, ',');
				$updateStringFather = rtrim($updateStringFather, ',');

				if($updateString != '') {
					$sql = "UPDATE animal_migration_table as a SET vsm_id = CAST(v.vsm_id_in_csv AS INTEGER)
						FROM (VALUES ".$updateString."
							 ) as v(vsm_id_in_animal, vsm_id_in_csv) WHERE a.vsm_id = CAST(v.vsm_id_in_animal AS INTEGER)";
					$this->conn->exec($sql);
				}

				if($updateStringMother != '') {
					$sql = "UPDATE animal_migration_table as a SET mother_vsm_id = CAST(v.vsm_id_in_csv AS INTEGER)
						FROM (VALUES ".$updateStringMother."
							 ) as v(vsm_id_in_animal, vsm_id_in_csv) WHERE a.mother_vsm_id = CAST(v.vsm_id_in_animal AS INTEGER)";
					$this->conn->exec($sql);
				}

				if($updateStringFather != '') {
					$sql = "UPDATE animal_migration_table as a SET father_vsm_id = CAST(v.vsm_id_in_csv AS INTEGER)
						FROM (VALUES ".$updateStringFather."
							 ) as v(vsm_id_in_animal, vsm_id_in_csv) WHERE a.father_vsm_id = CAST(v.vsm_id_in_animal AS INTEGER)";
					$this->conn->exec($sql);
				}

				if($updateString != '') {
					$sql = "UPDATE animal as a SET name = v.vsm_id_in_csv
						FROM (VALUES ".$updateString."
							 ) as v(vsm_id_in_animal, vsm_id_in_csv) WHERE a.name = v.vsm_id_in_animal";
					$this->conn->exec($sql);
				}

				//Reset batch string and counters
				$updateString = '';
				$updateStringMother = '';
				$updateStringFather = '';
				$updatedCount += $inBatchCount;
				$inBatchCount = 0;
			} else {
				$inBatchCount++;
			}

			$this->cmdUtil->advanceProgressBar(1,'VsmIds notFound|correct||updated|inBatch: '.$vsmIdsNotFound.'|'.$correctVsmIds.'||'.$updatedCount.'|'.$inBatchCount);
		}
		$this->cmdUtil->setEndTimeAndPrintFinalOverview();
	}


	public function fixVsmIdsPart2()
	{
		$this->output->writeln('Creating searchArrays ...');
		$recordsByUln = [];
		foreach ($this->data as $record) {
			$ulnParts = AnimalTableImporter::parseUln($record[3]);
			$uln = $ulnParts[JsonInputConstant::ULN_COUNTRY_CODE].$ulnParts[JsonInputConstant::ULN_NUMBER];
			$recordsByUln[$uln] = $record;
		}
		
		$sql = "SELECT a.id, CONCAT(a.uln_country_code,a.uln_number) as uln, a.name
				FROM animal a
				  INNER JOIN (
							   SELECT n.name FROM animal n
							   GROUP BY n.name HAVING COUNT(*) = 2
							 )g ON  g.name = a.name";
		$results = $this->conn->query($sql)->fetchAll();

		$vsmIdsNotFound = 0;
		$correctVsmIds = 0;

		$updateString = '';
		$count = 0;
		$inBatchCount = 0;
		$updatedCount = 0;

		$totalCount = count($results);

		$this->cmdUtil->setStartTimeAndPrintIt($totalCount, 1);
		foreach ($results as $result) {

			$animalId = intval($result['id']);
			$uln = $result['uln'];
			$vsmIdInAnimal = $result['name'];
			
			if(array_key_exists($uln, $recordsByUln)) {

				$record = $recordsByUln[$uln];
				$vsmIdInCsv = strval($record[0]);

				if($vsmIdInCsv != $vsmIdInAnimal && $vsmIdInAnimal != null && $vsmIdInCsv != null && $vsmIdInAnimal != '' && $vsmIdInCsv != '') {
					$updateString = $updateString."(".$animalId.",'".$vsmIdInCsv."'),";
					$inBatchCount++;
				}

				$correctVsmIds++;
			} else {
				$vsmIdsNotFound++;
			}


			$count++;
			if($count == $totalCount || ($inBatchCount%self::UPDATE_BATCH_SIZE == 0 && $count != 0)) {

				$updateString = rtrim($updateString, ',');

				if($updateString != '') {
					$sql = "UPDATE animal as a SET name = v.vsm_id_in_csv
						FROM (VALUES ".$updateString."
							 ) as v(animal_id, vsm_id_in_csv) WHERE a.id = v.animal_id";
					$this->conn->exec($sql);
				}

				//Reset batch string and counters
				$updateString = '';
				$updatedCount += $inBatchCount;
				$inBatchCount = 0;
			} else {
				$inBatchCount++;
			}

			$this->cmdUtil->advanceProgressBar(1,'VsmIds notFound|correct||updated|inBatch: '.$vsmIdsNotFound.'|'.$correctVsmIds.'||'.$updatedCount.'|'.$inBatchCount);
		}
		$this->cmdUtil->setEndTimeAndPrintFinalOverview();
	}

	
	public function fixDeclareTagTransfers()
	{
		$sqlFindDuplicateImportedDeclareTagTransfers =
			"SELECT r.id FROM declare_tag_replace r
						  INNER JOIN (
									   SELECT uln_number_to_replace, uln_number_replacement, uln_country_code_replacement, uln_country_code_to_replace,
										 --return the oldest logDate of duplicate imported tagReplaces, because those are likely the FAILED declares
										 MIN(log_date) as min_log_date FROM declare_tag_replace z
										 INNER JOIN declare_base ON declare_base.id = z.id
									   WHERE declare_base.request_state = 'IMPORTED'
									   GROUP BY uln_number_to_replace, uln_number_replacement, uln_country_code_replacement, uln_country_code_to_replace HAVING COUNT(*) > 1
									 )g ON g.uln_number_to_replace = r.uln_number_to_replace AND g.uln_number_replacement = r.uln_number_replacement
										   AND g.uln_country_code_replacement = r.uln_country_code_replacement AND g.uln_country_code_to_replace = r.uln_country_code_to_replace
						  INNER JOIN declare_base b ON b.id = r.id AND b.log_date = min_log_date
						--Filter out the FAILED ones because that is not an error
						";


		$sqlFindDuplicateImportedAndFinishedPairedDeclareTagTransfers =
			"SELECT r.id FROM declare_tag_replace r
			  INNER JOIN (
						   SELECT uln_number_to_replace, uln_number_replacement, uln_country_code_replacement, uln_country_code_to_replace FROM declare_tag_replace z
							 INNER JOIN declare_base b ON b.id = z.id
						   --Filter out the FAILED ones because that is not an error
						   WHERE b.request_state = 'FINISHED' OR b. request_state = 'IMPORTED' OR b.request_state = 'FINISHED_WITH_WARNING'
						   GROUP BY uln_number_to_replace, uln_number_replacement, uln_country_code_replacement, uln_country_code_to_replace HAVING COUNT(*) > 1
						 )g ON g.uln_number_to_replace = r.uln_number_to_replace AND g.uln_number_replacement = r.uln_number_replacement
							   AND g.uln_country_code_replacement = r.uln_country_code_replacement AND g.uln_country_code_to_replace = r.uln_country_code_to_replace
			INNER JOIN declare_base bb ON bb.id = r.id
			WHERE bb. request_state = 'IMPORTED'";

		foreach (['IMPORTED' => $sqlFindDuplicateImportedDeclareTagTransfers,
				  'IMPORTED-FINISHED paired' => $sqlFindDuplicateImportedAndFinishedPairedDeclareTagTransfers]
				as $key => $sqlFindDuplicate) {
			$results = $this->conn->query($sqlFindDuplicate)->fetchAll();
			$DuplicateImportedDeclareTagTransfersCount = count($results);
			if($DuplicateImportedDeclareTagTransfersCount == 0) {
				$this->output->writeln('There are no duplicate '.$key.' declareTagTransfers');
			} else {
				foreach(['declare_tag_replace', 'declare_base'] as $tableName) {
					$sql = "DELETE FROM ".$tableName." WHERE id IN ( ".$sqlFindDuplicate." )";
					$this->conn->exec($sql);
				}
				$this->output->writeln($DuplicateImportedDeclareTagTransfersCount.' Duplicate '.$key.' declareTagTransfers deleted!');
			}
		}
	}
	
	
	public function addMissingAnimalsToMigrationTable()
	{
		$sql = "SELECT MAX(id) as max_id FROM animal_migration_table";
		$maxId = $this->conn->query($sql)->fetch()['max_id'];
		$this->output->writeln(['','animal_migration_table max id: '.$maxId]);
		
		$sql = "SELECT CONCAT(a.uln_country_code, a.uln_number, DATE(a.date_of_birth)) as search_key,
				  a.date_of_birth, a.uln_country_code, a.uln_number, a.name, a.gender
				FROM animal a
				  LEFT JOIN animal_migration_table t
					ON t.date_of_birth = a.date_of_birth AND t.uln_country_code = a.uln_country_code AND t.uln_number = a.uln_number
				  INNER JOIN (
							   SELECT n.name
							   FROM animal n
							   GROUP BY n.name HAVING COUNT(*) > 1
							   -- NOTE THAT DUPLICATES ABOVE 2 PER SET MUST BE CHECKED MANUALLY!
							 )g ON  g.name = a.name
				WHERE t.id ISNULL";
		$results = $this->conn->query($sql)->fetchAll();
		
		$totalCount = count($results);
		if($totalCount == 0) { return; }
		
		$animalsMissingFromMigrationTableByUlnAndDateOfBirth = [];
		foreach ($results as $result) {
			$animalsMissingFromMigrationTableByUlnAndDateOfBirth[$result['search_key']] = $result;
		}


		$sql = "SELECT name, gender FROM animal WHERE name NOTNULL";
		$results = $this->conn->query($sql)->fetchAll();
		$genderInDatabaseByVsmIdSearchArray = [];
		foreach ($results as $result) {
			$genderInDatabaseByVsmIdSearchArray[intval($result['name'])] = $result['gender'];
		}

		$sql = "SELECT id, abbreviation, full_name FROM pedigree_register";
		$results = $this->conn->query($sql)->fetchAll();
		$pedigreeRegisterIdByAbbreviationSearchArray = [];
		foreach ($results as $result) {
			$pedigreeRegisterIdByAbbreviationSearchArray[$result['abbreviation']] = intval($result['id']);
		}

		$locationIdByUbnSearchArray = $this->generateLatestLocationSearchArray();

		$animalsSkipped = 0;
		$animalsAddedToMigrationTable = 0;
		$this->cmdUtil->setStartTimeAndPrintIt(count($this->data)+1, 1);
		foreach ($this->data as $record) {

			$uln = StringUtil::getNullAsStringOrWrapInQuotes($record[3]);
			$ulnParts = AnimalTableImporter::parseUln($record[3]);

			$searchKey = $ulnParts[JsonInputConstant::ULN_COUNTRY_CODE].$ulnParts[JsonInputConstant::ULN_NUMBER].TimeUtil::fillDateStringWithLeadingZeroes($record[8]);

			if(array_key_exists($searchKey, $animalsMissingFromMigrationTableByUlnAndDateOfBirth)) {

				$ulnCountryCode = StringUtil::getNullAsStringOrWrapInQuotes($ulnParts[JsonInputConstant::ULN_COUNTRY_CODE]);
				$ulnNumber = StringUtil::getNullAsStringOrWrapInQuotes($ulnParts[JsonInputConstant::ULN_NUMBER]);

				$vsmId = intval($record[0]);
				$stnImport = StringUtil::getNullAsStringOrWrapInQuotes($record[1]);
				$stnParts = AnimalTableImporter::parseStn($record[1]);
				$pedigreeCountryCode = StringUtil::getNullAsStringOrWrapInQuotes($stnParts[JsonInputConstant::PEDIGREE_COUNTRY_CODE]);
				$pedigreeNumber = StringUtil::getNullAsStringOrWrapInQuotes($stnParts[JsonInputConstant::PEDIGREE_NUMBER]);

				$animalOrderNumber = 'NULL';
				if($record[2] != null && $record[2] != '') {
					$animalOrderNumber = StringUtil::getNullAsStringOrWrapInQuotes(StringUtil::padAnimalOrderNumberWithZeroes($record[2]));
				}

				$nickName = StringUtil::getNullAsStringOrWrapInQuotes(utf8_encode(StringUtil::escapeSingleApostrophes($record[4])));
				$fatherVsmId = SqlUtil::getNullCheckedValueForSqlQuery($record[5], false);
				$motherVsmId = SqlUtil::getNullCheckedValueForSqlQuery($record[6], false);
				$genderInFile = StringUtil::getNullAsStringOrWrapInQuotes(AnimalTableImporter::parseGender($record[7]));
				$dateOfBirthString = StringUtil::getNullAsStringOrWrapInQuotes(TimeUtil::fillDateStringWithLeadingZeroes($record[8]));
				$breedCode = StringUtil::getNullAsStringOrWrapInQuotes($record[9]);
				$ubnOfBirth = StringUtil::getNullAsStringOrWrapInQuotes($record[10]); //ubnOfBreeder
				$locationOfBirth = SqlUtil::getSearchArrayCheckedValueForSqlQuery($record[10], $locationIdByUbnSearchArray, false);

				$pedigreeRegister = self::parsePedigreeRegister($record[11]);
				$pedigreeRegisterFullname = $pedigreeRegister[self::VALUE];
				$pedigreeRegisterAbbreviation = $pedigreeRegister[self::ABBREVIATION];

				$pedigreeRegisterId = SqlUtil::getSearchArrayCheckedValueForSqlQuery($pedigreeRegisterAbbreviation, $pedigreeRegisterIdByAbbreviationSearchArray, false);
				$breedType = SqlUtil::getNullCheckedValueForSqlQuery(Translation::getEnglish(strtoupper($record[12])), true);
				$scrapieGenotype = SqlUtil::getNullCheckedValueForSqlQuery($record[13], true);

				$animalId = StringUtil::getNullAsString($this->findAnimalIdOfVsmId($vsmId, $ulnCountryCode, $ulnNumber));
				$fatherId = StringUtil::getNullAsString($this->findAnimalIdOfVsmId(intval($record[5]), null, null));
				$motherId = StringUtil::getNullAsString($this->findAnimalIdOfVsmId(intval($record[6]), null, null));

				$genderInDatabase = SqlUtil::getSearchArrayCheckedValueForSqlQuery($vsmId, $genderInDatabaseByVsmIdSearchArray, true);


				$sql = "INSERT INTO animal_migration_table (id, vsm_id, animal_id, uln_origin, stn_origin, uln_country_code, uln_number, animal_order_number,
							pedigree_country_code, pedigree_number, nick_name, father_vsm_id, father_id, mother_vsm_id, mother_id, gender_in_file,
							gender_in_database,date_of_birth,breed_code,ubn_of_birth,location_of_birth_id,pedigree_register_id,breed_type,scrapie_genotype
							)VALUES(nextval('animal_migration_table_id_seq'),".$vsmId.",".$animalId.",".$uln.",".$stnImport.",".$ulnCountryCode.",".$ulnNumber.",".$animalOrderNumber.",".$pedigreeCountryCode.",".$pedigreeNumber.",".$nickName.",".$fatherVsmId.",".$fatherId.",".$motherVsmId.",".$motherId.",".$genderInFile.",".$genderInDatabase.",".$dateOfBirthString.",".$breedCode.",".$ubnOfBirth.",".$locationOfBirth.",".$pedigreeRegisterId.",".$breedType.",".$scrapieGenotype.")";
				$this->conn->exec($sql);
				$animalsAddedToMigrationTable++;
			} else {
				$animalsSkipped++;
			}
			$this->cmdUtil->advanceProgressBar(1,'Animals skipped|found&Added|totalMissing: '.$animalsSkipped.'|'.$animalsAddedToMigrationTable.'|'.$totalCount);
		}
		$this->cmdUtil->setProgressBarMessage(1,'Animals skipped|found&Added|totalMissing: '.$animalsSkipped.'|'.$animalsAddedToMigrationTable.'|'.$totalCount);
		$this->cmdUtil->setEndTimeAndPrintFinalOverview();
	}


	public function fixValuesInAnimalMigrationTable()
	{
		//NOTE! This fixes stuff in the animal table
		$this->fixMinorFormattingIssuesInAnimalTable();

		//NOTE! The order of operations here is important!
		$this->deleteTestAnimals();
		$this->fixScientificNotationInStnAndUlnOrigin();
		$this->fixMinorFormattingIssues();
		$this->fixGenders();
		$this->getUbnOfBirthFromUln();
		$this->fixAnimalOrderNumbers();
		$this->fixMissingAnimalOrderNumbers();
		$this->fixUlnsAndStns();
		$this->findMissingFathers();

		//Final value checks, just in case new animals were synced
		$this->fixAnimalOrderNumbers();
		$this->checkAnimalIds();
		$this->checkGendersInDatabase();

	}


	public function importAnimalTableCsvFileIntoDatabase()
	{
        //Search Arrays
        $sql = "SELECT vsm_id FROM animal_migration_table";
        $results = $this->conn->query($sql)->fetchAll();
        $processedAnimals = [];
        foreach ($results as $result) {
            $processedAnimals[$result['vsm_id']] = $result['vsm_id'];
        }

        $sql = "SELECT name, gender FROM animal WHERE name NOTNULL";
        $results = $this->conn->query($sql)->fetchAll();
        $genderInDatabaseByVsmIdSearchArray = [];
        foreach ($results as $result) {
            $genderInDatabaseByVsmIdSearchArray[intval($result['name'])] = $result['gender'];
        }
        
        $sql = "SELECT name, gender FROM animal WHERE name NOTNULL";
        $results = $this->conn->query($sql)->fetchAll();
        $genderInDatabaseByVsmIdSearchArray = [];
        foreach ($results as $result) {
            $genderInDatabaseByVsmIdSearchArray[intval($result['name'])] = $result['gender'];
        }
        
        $sql = "SELECT id, abbreviation, full_name FROM pedigree_register";
        $results = $this->conn->query($sql)->fetchAll();
        $pedigreeRegisterIdByAbbreviationSearchArray = [];
        foreach ($results as $result) {
            $pedigreeRegisterIdByAbbreviationSearchArray[$result['abbreviation']] = intval($result['id']);
        }

        $locationIdByUbnSearchArray = $this->generateLatestLocationSearchArray();


		$animalsSkipped = 0;
		$animalsAlreadyInDatabase = 0;
        $this->cmdUtil->setStartTimeAndPrintIt(count($this->data)+1, 1);
		foreach ($this->data as $record) {

			$uln = StringUtil::getNullAsStringOrWrapInQuotes($record[3]);
			$ulnParts = AnimalTableImporter::parseUln($record[3]);
			$ulnCountryCode = StringUtil::getNullAsStringOrWrapInQuotes($ulnParts[JsonInputConstant::ULN_COUNTRY_CODE]);
			$ulnNumber = StringUtil::getNullAsStringOrWrapInQuotes($ulnParts[JsonInputConstant::ULN_NUMBER]);

			if($ulnCountryCode == "'XD'") { $animalsSkipped++; $this->cmdUtil->advanceProgressBar(1); continue; } // These are testAnimals and should be skipped

            $vsmId = intval($record[0]);

            if(array_key_exists($vsmId, $processedAnimals)) { $animalsAlreadyInDatabase++; $this->cmdUtil->advanceProgressBar(1); continue; }

            $stnImport = StringUtil::getNullAsStringOrWrapInQuotes($record[1]);
            $stnParts = AnimalTableImporter::parseStn($record[1]);
            $pedigreeCountryCode = StringUtil::getNullAsStringOrWrapInQuotes($stnParts[JsonInputConstant::PEDIGREE_COUNTRY_CODE]);
            $pedigreeNumber = StringUtil::getNullAsStringOrWrapInQuotes($stnParts[JsonInputConstant::PEDIGREE_NUMBER]);

            $animalOrderNumber = 'NULL';
            if($record[2] != null && $record[2] != '') {
                $animalOrderNumber = StringUtil::getNullAsStringOrWrapInQuotes(StringUtil::padAnimalOrderNumberWithZeroes($record[2]));
            }

			$nickname = StringUtil::getNullAsStringOrWrapInQuotes(utf8_encode(StringUtil::escapeSingleApostrophes($record[4])));
            $fatherVsmId = SqlUtil::getNullCheckedValueForSqlQuery($record[5], false);
            $motherVsmId = SqlUtil::getNullCheckedValueForSqlQuery($record[6], false);
            $genderInFile = StringUtil::getNullAsStringOrWrapInQuotes(AnimalTableImporter::parseGender($record[7]));
			$dateOfBirthString = StringUtil::getNullAsStringOrWrapInQuotes($record[8]);
			$breedCode = StringUtil::getNullAsStringOrWrapInQuotes($record[9]);
			$ubnOfBirth = StringUtil::getNullAsStringOrWrapInQuotes($record[10]); //ubnOfBreeder
            $locationOfBirth = SqlUtil::getSearchArrayCheckedValueForSqlQuery($record[10], $locationIdByUbnSearchArray, false);

			$pedigreeRegister = self::parsePedigreeRegister($record[11]);
			$pedigreeRegisterFullname = $pedigreeRegister[self::VALUE];
			$pedigreeRegisterAbbreviation = $pedigreeRegister[self::ABBREVIATION];

            $pedigreeRegisterId = SqlUtil::getSearchArrayCheckedValueForSqlQuery($pedigreeRegisterAbbreviation, $pedigreeRegisterIdByAbbreviationSearchArray, false);
            $breedType = SqlUtil::getNullCheckedValueForSqlQuery(Translation::getEnglish(strtoupper($record[12])), true);
			$scrapieGenotype = SqlUtil::getNullCheckedValueForSqlQuery($record[13], true);

			$animalId = StringUtil::getNullAsString($this->findAnimalIdOfVsmId($vsmId, $ulnCountryCode, $ulnNumber));
			$fatherId = StringUtil::getNullAsString($this->findAnimalIdOfVsmId(intval($record[5]), null, null));
			$motherId = StringUtil::getNullAsString($this->findAnimalIdOfVsmId(intval($record[6]), null, null));

            $genderInDatabase = SqlUtil::getSearchArrayCheckedValueForSqlQuery($vsmId, $genderInDatabaseByVsmIdSearchArray, true);


			$sql = "INSERT INTO animal_migration_table (id, vsm_id, animal_id, uln_origin, stn_origin, uln_country_code, uln_number, animal_order_number,
						pedigree_country_code, pedigree_number, nickname, father_vsm_id, father_id, mother_vsm_id, mother_id, gender_in_file,
						gender_in_database,date_of_birth,breed_code,ubn_of_birth,location_of_birth_id,pedigree_register_id,breed_type,scrapie_genotype
						)VALUES(nextval('animal_migration_table_id_seq'),".$vsmId.",".$animalId.",".$uln.",".$stnImport.",".$ulnCountryCode.",".$ulnNumber.",".$animalOrderNumber.",".$pedigreeCountryCode.",".$pedigreeNumber.",".$nickname.",".$fatherVsmId.",".$fatherId.",".$motherVsmId.",".$motherId.",".$genderInFile.",".$genderInDatabase.",".$dateOfBirthString.",".$breedCode.",".$ubnOfBirth.",".$locationOfBirth.",".$pedigreeRegisterId.",".$breedType.",".$scrapieGenotype.")";
			$this->conn->exec($sql);

            $this->cmdUtil->advanceProgressBar(1);
		}
        $this->cmdUtil->setProgressBarMessage('Animals skipped: '.$animalsSkipped.' | Animals already in database: '.$animalsAlreadyInDatabase);
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
	}


	/**
	 * @param string $vsmId
	 * @param string $ulnCountryCode
	 * @param string $ulnNumber
	 * @return int
	 */
	private function findAnimalIdOfVsmId($vsmId, $ulnCountryCode = null, $ulnNumber = null)
	{
        if($this->animalIdsByVsmId != null) {
            if($this->animalIdsByVsmId->containsKey($vsmId)) {
                return intval($this->animalIdsByVsmId->get($vsmId));
            }
        }

        if($ulnCountryCode != null && $ulnNumber != null) {

            $ulnCountryCode = trim("$ulnCountryCode", "'");
            $ulnNumber = trim("$ulnNumber", "'");

            $ulnNumber = StringUtil::padUlnNumberWithZeroes($ulnNumber);

            //Note that duplicate animals will not be in this result, because at least one of the duplicate animals will have a vsmId
            $sql = "SELECT id FROM animal WHERE uln_country_code = '".$ulnCountryCode."' AND uln_number = '".$ulnNumber."'";
            $animalId = $this->conn->query($sql)->fetch()['id'];

            if($animalId != null) {
                return intval($animalId);
            }

            //Finally check if the uln has been replaced
            /** @var DeclareTagReplaceRepository $declareTagReplaceRepository */
            $declareTagReplaceRepository = $this->em->getRepository(DeclareTagReplace::class);
            $newestUln = $declareTagReplaceRepository->getNewReplacementUln($ulnCountryCode.$ulnNumber);

            if($newestUln != null) {
                $ulnParts = Utils::getUlnFromString($newestUln);
                $ulnCountryCode = $ulnParts[Constant::ULN_COUNTRY_CODE_NAMESPACE];
                $ulnNumber = $ulnParts[Constant::ULN_NUMBER_NAMESPACE];

                $sql = "SELECT id FROM animal WHERE uln_country_code = '".$ulnCountryCode."' AND uln_number = '".$ulnNumber."'";
                $animalId = $this->conn->query($sql)->fetch()['id'];

                if($animalId != null) { return $animalId; }
            }
        }

		return null;
	}


	private function deleteTestAnimals()
	{
		$sql = "SELECT COUNT(*) FROM animal_migration_table a
				WHERE substring(a.stn_origin, 1,2) = 'XD' OR substring(a.uln_origin, 1,2) = 'XD'";
		$count = $this->conn->query($sql)->fetch()['count'];

		if($count == 0) {
			$this->output->writeln('All XD testAnimals have already been deleted from stn_origin and uln_origin');
			return;
		}

		$sql = "DELETE FROM animal_migration_table a
				WHERE substring(a.stn_origin, 1,2) = 'XD' OR substring(a.uln_origin, 1,2) = 'XD'";
		$this->conn->exec($sql);

		$this->output->writeln($count.' XD testAnimals have been deleted from stn_origin and uln_origin');
	}


	private function fixScientificNotationInStnAndUlnOrigin()
	{
		$sql = "SELECT id, stn_origin FROM animal_migration_table
				WHERE stn_origin SIMILAR TO '%[+]%'";
		$stnResults = $this->conn->query($sql)->fetchAll();

		$sql = "SELECT id, uln_origin FROM animal_migration_table
				WHERE uln_origin SIMILAR TO '%[+]%'";
		$ulnResults = $this->conn->query($sql)->fetchAll();

		$stnCount = count($stnResults);
		$ulnCount = count($ulnResults);
		if($ulnCount + $stnCount == 0) {
			$this->output->writeln('All ScientificNotations in stn_origin and uln_origin have already fixed');
			return;
		}

		foreach ($stnResults as $stnResult) {
			$stnOrigin = strval(intval(floatval(strtr($stnResult['stn_origin'],[',' =>'.']))));
			$sql = "UPDATE animal_migration_table SET stn_origin = '".$stnOrigin."'
					WHERE id = ".$stnResult['id'];
			$this->conn->exec($sql);
		}
		$this->output->writeln($stnCount.' scientificNotations in stn_origin fixed');

		foreach ($ulnResults as $ulnResult) {
			$ulnOrigin = strval(intval(floatval(strtr($ulnResult['uln_origin'],[',' =>'.']))));
			$sql = "UPDATE animal_migration_table SET uln_origin = '".$ulnOrigin."'
					WHERE id = ".$ulnResult['id'];
			$this->conn->exec($sql);
		}
		$this->output->writeln($ulnCount.' scientificNotations in uln_origin fixed');
	}


	private function fixMinorFormattingIssuesInAnimalTable()
	{
		$sql = "SELECT COUNT(*) FROM animal WHERE uln_country_code = 'GB'";
		$gbCount = $this->conn->query($sql)->fetch()['count'];
		if($gbCount > 0) {
			$sql = "UPDATE animal SET uln_country_code = 'UK' WHERE uln_country_code = 'GB'";
			$this->conn->exec($sql);
		}
	}


	private function fixMinorFormattingIssues()
	{
		$sql = "UPDATE animal_migration_table SET stn_origin = NULL WHERE stn_origin = ' '";
		$this->conn->exec($sql);

		$sql = "SELECT id, uln_origin, stn_origin FROM animal_migration_table";
		$results = $this->conn->query($sql)->fetchAll();

//		//Find uln & stn with non alpha numeric
//		foreach ($results as $result) {
//			$id = $result['id'];
//			$ulnOrigin = strtr($result['uln_origin'], ['-' => '', ' ' => '']);
//			$stnOrigin = strtr($result['stn_origin'], ['-' => '', ' ' => '']);
//
//			if($ulnOrigin != null || $ulnOrigin != '') {
//				if(!ctype_alnum($ulnOrigin)) { dump(['uln: '.$id => $result['uln_origin'].' == '.$result['stn_origin']]); }
//			}
//
//			if($stnOrigin != null || $stnOrigin != '') {
//				if(!ctype_alnum($stnOrigin)) { dump(['stn: '.$id => $result['stn_origin'].' == '.$result['uln_origin']]); }
//			}
//		}
	}


	public function migrate()
	{
		$checkAnimalIdsExcludingIdOfParents = false;
		$migrateAnimals = true;
		$migrateParents = true;
		$updateLocationIdsOfBirth = true;
		$clearPedigreeCountryCodesAndNumbersWithoutPedigreeRegisters = true;


		//TODO
		/*
		 * NOTE!!!! FIRST UPDATE THE ANIMALS IN THE DATABASE TO THE CORRECT GENDER !!!!
		 * The correct gender is in the gender_in_file. Read that one to the database
		 * If EWE don't import children as father
		 * If RAM don't import children as mother
		 *
		 * VsmIds for duplicate animals are found in the vsm_id_group table
		 */

		if($checkAnimalIdsExcludingIdOfParents) {
			$this->output->writeln('Check animalIds...');
			$this->checkAnimalIds(false);
		}
		
		if($migrateAnimals) {
			$this->cmdUtil->setStartTimeAndPrintIt(9,1,'Retrieving data ...');

			//SeachArrays
			$this->cmdUtil->advanceProgressBar(1, 'Retrieving data and generating newestUlnByOldUln searchArray ...');
			$newestUlnByOldUln = $this->declareTagReplaceRepository->getNewReplacementUlnSearchArray();

			$this->cmdUtil->advanceProgressBar(1, 'Retrieving data and generating primaryVsmIdsBySecondaryVsmId searchArrays ...');
			$this->primaryVsmIdsForSecondaryIds = $this->vsmIdGroupRepository->getPrimaryVsmIdsBySecondaryVsmId();

			$this->cmdUtil->advanceProgressBar(1, 'Retrieving data and generating animalData searchArrays ...');
			$this->resetAnimalIdVsmLocationAndGenderSearchArrays();

			$this->cmdUtil->advanceProgressBar(1, 'Retrieving animalMigration data ...');

			//Only process animals where genders match will those in the database
			$sql = "SELECT a.id, a.vsm_id, a.animal_id, a.uln_country_code, a.uln_number, a.animal_order_number, a.pedigree_country_code,
				  a.pedigree_number, a.nickname, a.father_vsm_id, a.father_id, a.mother_vsm_id, a.mother_id, a.gender_in_file,
				  a.date_of_birth, a.breed_code, a.ubn_of_birth, a.location_of_birth_id, a.pedigree_register_id, a.breed_type, a.scrapie_genotype
				FROM animal_migration_table a
				--Exclude duplicate ulns
				INNER JOIN (
					SELECT uln_number, uln_country_code FROM animal_migration_table t
					GROUP BY uln_country_code, uln_number HAVING COUNT(*) = 1
					)u ON u.uln_country_code = a.uln_country_code AND u.uln_number = a.uln_number
				--Exclude duplicate stns
				LEFT JOIN (
					SELECT id as double_pedigree_id FROM animal_migration_table x
					INNER JOIN (
						SELECT pedigree_number, pedigree_country_code FROM animal_migration_table z
						GROUP BY pedigree_country_code, pedigree_number HAVING COUNT(*) > 1
						)y ON x.pedigree_country_code = y.pedigree_country_code AND x.pedigree_number = y.pedigree_number
					)p ON double_pedigree_id = a.id
				WHERE p.double_pedigree_id ISNULL AND
				  --Exclude animals with mismatching genders
					  (gender_in_database ISNULL OR gender_in_file = gender_in_database) AND a.is_record_migrated = FALSE 
				ORDER BY date_of_birth";
			$results = $this->conn->query($sql)->fetchAll();

			$this->cmdUtil->setProgressBarMessage('Fixing possible missing ewe/ram/neuter table records');
			$missingTableExtentions = AnimalRepository::fixMissingAnimalTableExtentions($this->conn);
			$this->cmdUtil->setProgressBarMessage($missingTableExtentions.' missing ewe/ram/neuter table records added');
			
			$this->cmdUtil->setEndTimeAndPrintFinalOverview();

			$this->output->writeln('Retrieved data and created searchArrays');

			//First import animals

			$newAnimals = 0;
			$skippedAnimals = 0;
			$updatedAnimals = 0;

			$this->parentVsmIdsUpdated = [];

			$this->cmdUtil->setStartTimeAndPrintIt(count($results), 1);

			$insertString = '';
			$migrationTableCheckListIds = [];
			$insertBatchCount = 0;

			$sql = "SELECT MAX(id) FROM animal";
			$maxAnimalId = $this->conn->query($sql)->fetch()['max'];

			foreach ($results as $result) {
				$migrationTableId = $result['id'];
				$vsmId = $result['vsm_id'];
				$gender = $result['gender_in_file'];

				$animalId = null;
				$currentGenderInDatabase = null;
				if(array_key_exists($vsmId, $this->animalIdByVsmId)) {
					$animalId = $this->animalIdByVsmId[$vsmId];

					if(array_key_exists($animalId, $this->genderByAnimalId)) {
						$currentGenderInDatabase = $this->genderByAnimalId[$animalId];
					}
				}

				//Skip duplicateVsmIds!
				$isDuplicateVsmId = array_key_exists($vsmId, $this->primaryVsmIdsForSecondaryIds);
				$isGenderMismatched = false;
				if($currentGenderInDatabase != null && $currentGenderInDatabase != GenderType::NEUTER) {
					//NOTE! Neuters must be given the new gender, if the gender will be updated!
					$isGenderMismatched = $currentGenderInDatabase != $gender;
				}

				if($isDuplicateVsmId || $isGenderMismatched) {
					$sql = "UPDATE animal_migration_table SET is_record_migrated = TRUE WHERE id = ".$migrationTableId;
					$this->conn->exec($sql);
					$skippedAnimals++;
					continue;
				}

				$ulnCountryCode = $result['uln_country_code'];
				$ulnNumber = $result['uln_number'];
				$animalOrderNumber = $result['animal_order_number'];
				$pedigreeCountryCode = $result['pedigree_country_code'];
				$pedigreeNumber = $result['pedigree_number'];
				$pedigreeRegisterId = $result['pedigree_register_id'];
				if($pedigreeRegisterId == null) {
					$pedigreeCountryCode = null;
					$pedigreeNumber = null;
				}
				$nickname = $result['nickname'];
				$type = GenderChangerForMigrationOnly::getClassNameByGender($gender);

				/*
				 * Get animalId from vsmId to make sure the gender is correct
				 */
				$fatherVsmId = $this->getPrimaryVsmId($result['father_vsm_id']);
				$fatherId = $this->getGenderCheckedAnimalId($fatherVsmId, $this->animalIdByVsmId, $this->genderByAnimalId, GenderType::MALE);
				$motherVsmId = $this->getPrimaryVsmId($result['mother_vsm_id']);
				$motherId = $this->getGenderCheckedAnimalId($motherVsmId, $this->animalIdByVsmId, $this->genderByAnimalId, GenderType::FEMALE);

				$dateOfBirth = $result['date_of_birth'];
				$breedCode = $result['breed_code'];
				$ubnOfBirth = $result['ubn_of_birth'];
				$locationOfBirthId = $result['location_of_birth_id'];
				$breedType = $result['breed_type'];
				$scrapieGenotype = $result['scrapie_genotype'];


				//If animal has been synced, just use the current uln data
				if(!array_key_exists($animalId, $this->animalIdsOnLocation)) {
					//Get newest uln
					if(is_string($ulnCountryCode) && is_string($ulnNumber) ) {
						if(array_key_exists($ulnCountryCode.$ulnNumber, $newestUlnByOldUln)) {
							$ulnParts = $newestUlnByOldUln[$ulnCountryCode.$ulnNumber];
							if(is_array($ulnParts)) {
								$ulnCountryCode = Utils::getNullCheckedArrayValue(Constant::ULN_COUNTRY_CODE_NAMESPACE, $ulnParts);
								$ulnNumber = Utils::getNullCheckedArrayValue(Constant::ULN_NUMBER_NAMESPACE, $ulnParts);
							}
						}
					}
				}


				$vsmIdSql = SqlUtil::getNullCheckedValueForSqlQuery($vsmId, true);
				$ulnCountryCodeSql = SqlUtil::getNullCheckedValueForSqlQuery($ulnCountryCode, true);
				$ulnNumberSql = SqlUtil::getNullCheckedValueForSqlQuery($ulnNumber, true);
				$animalOrderNumberSql = SqlUtil::getNullCheckedValueForSqlQuery($animalOrderNumber, true);
				$pedigreeCountryCodeSql = SqlUtil::getNullCheckedValueForSqlQuery($pedigreeCountryCode, true);
				$pedigreeNumberSql =  SqlUtil::getNullCheckedValueForSqlQuery($pedigreeNumber, true);
				$nicknameSql = SqlUtil::getNullCheckedValueForSqlQuery(utf8_encode(StringUtil::escapeSingleApostrophes($nickname)), true);
				$fatherIdSql = SqlUtil::getNullCheckedValueForSqlQuery($fatherId, false);
				$motherIdSql = SqlUtil::getNullCheckedValueForSqlQuery($motherId, false);
				$genderSql = SqlUtil::getNullCheckedValueForSqlQuery($gender, true);
				$dateOfBirthSql = SqlUtil::getNullCheckedValueForSqlQuery($dateOfBirth, true);
				$breedCodeSql = SqlUtil::getNullCheckedValueForSqlQuery($breedCode, true);
				$ubnOfBirthSql = SqlUtil::getNullCheckedValueForSqlQuery($ubnOfBirth, true);
				$locationOfBirthIdSql = SqlUtil::getNullCheckedValueForSqlQuery($locationOfBirthId, false);
				$pedigreeRegisterIdSql = SqlUtil::getNullCheckedValueForSqlQuery($pedigreeRegisterId, true);
				$breedTypeSql = SqlUtil::getNullCheckedValueForSqlQuery($breedType, true);
				$scrapieGenotypeSql = SqlUtil::getNullCheckedValueForSqlQuery($scrapieGenotype, true);


				if($animalId != null) {
					$oldValues = $this->animalsByAnimalId[$animalId];

					//Check if animal values need to be updated
					$haveValuesChanged = $vsmId != $oldValues['name'] ||
						$animalId != $oldValues['id'] ||
						$ulnCountryCode != $oldValues['uln_country_code'] ||
						$ulnNumber != $oldValues['uln_number'] ||
						$animalOrderNumber != $oldValues['animal_order_number'] ||
						$pedigreeCountryCode != $oldValues['pedigree_country_code'] ||
						$pedigreeNumber != $oldValues['pedigree_number'] ||
						$nickname != $oldValues['nickname'] ||
						$fatherId != $oldValues['parent_father_id'] ||
						$motherId != $oldValues['parent_mother_id'] ||
						$gender != $oldValues['gender'] ||
						$dateOfBirth != $oldValues['date_of_birth'] ||
						$breedCode != $oldValues['breed_code'] ||
						$ubnOfBirth != $oldValues['ubn_of_birth'] ||
						$locationOfBirthId != $oldValues['location_of_birth_id'] ||
						$pedigreeRegisterId != $oldValues['pedigree_register_id'] ||
						$breedType != $oldValues['breed_type'] ||
						$scrapieGenotype != $oldValues['scrapie_genotype'];
						
					if($haveValuesChanged) {
						$sql = "UPDATE animal SET name = ".$vsmIdSql.",
							uln_country_code = ".$ulnCountryCodeSql.",
							uln_number = ".$ulnNumberSql.",
							animal_order_number = ".$animalOrderNumberSql.",
						  	pedigree_country_code = ".$pedigreeCountryCodeSql.",
						  	pedigree_number = ".$pedigreeNumberSql.",
						  	nickname = ".$nicknameSql.",
						  	parent_father_id = ".$fatherIdSql.", 
						  	parent_mother_id = ".$motherIdSql.",
						  	gender = ".$genderSql.",
						  	date_of_birth = ".$dateOfBirthSql.",
						  	breed_code = ".$breedCodeSql.",
						  	ubn_of_birth = ".$ubnOfBirthSql.",
						  	location_of_birth_id = ".$locationOfBirthIdSql.",
						  	pedigree_register_id = ".$pedigreeRegisterIdSql.",
						  	breed_type = ".$breedTypeSql.",
						  	scrapie_genotype = ".$scrapieGenotypeSql."
							 WHERE id = ".$animalId;
						$this->conn->exec($sql);

						$sql = "UPDATE animal_migration_table SET is_record_migrated = TRUE WHERE id = ".$migrationTableId;
						$this->conn->exec($sql);

						//Update gender
						$oldType = $oldValues['type'];
						$typeChanged =  $type != $oldType;
						if($typeChanged && $oldType == GenderChangerForMigrationOnly::getClassNameByGender(GenderType::NEUTER)) {
							GenderChangerForMigrationOnly::changeGenderOfNeuter($this->em, $animalId, $gender);
						}
						
						//Update searchArrays
						$this->animalIdByVsmId[$vsmId] = $animalId;
						$this->genderByAnimalId[$animalId] = $gender;

						$updatedAnimals++;
					} else {
						$sql = "UPDATE animal_migration_table SET is_record_migrated = TRUE WHERE id = ".$migrationTableId;
						$this->conn->exec($sql);
						$skippedAnimals++;
					}

				} else {

					//Insert new animal, process it as a batch

					$insertBatchCount++;
					$migrationTableCheckListIds[$migrationTableId] = $migrationTableId;

					$maxAnimalId++;
					$insertString = $insertString."(".$maxAnimalId.",".$vsmIdSql.",".$ulnCountryCodeSql.",".$ulnNumberSql.",".$animalOrderNumberSql
						.",".$pedigreeCountryCodeSql.",".$pedigreeNumberSql.",".$nicknameSql.",".$fatherIdSql
						.",".$motherIdSql.",".$genderSql.",".$dateOfBirthSql.",".$breedCodeSql.",".$ubnOfBirthSql
						.",".$locationOfBirthIdSql.",".$pedigreeRegisterIdSql.",".$breedTypeSql.",".$scrapieGenotypeSql
						.",3,3,TRUE,FALSE,FALSE,FALSE,'".$type."')";

					if(!($insertBatchCount%self::INSERT_BATCH_SIZE == 0 && $insertBatchCount != 0)) {
						$insertString = $insertString.',';
					}
				}


				//Inserting by Batch
				if($insertBatchCount%self::INSERT_BATCH_SIZE == 0 && $insertBatchCount != 0) {
					$this->insertByBatch($migrationTableCheckListIds, $insertString);

					//Reset batch values AFTER insert
					$insertString = '';
					$migrationTableCheckListIds = [];
					$insertBatchCount = 0;
					$newAnimals += self::INSERT_BATCH_SIZE;
				}

				$this->cmdUtil->advanceProgressBar(1, 'Migrating animalData new|updated|skipped: '.$newAnimals.'|'.$updatedAnimals.'|'.$skippedAnimals.'  insertBatch: '.$insertBatchCount);
			}

			if($insertString != '') {
				//Final batch insert
				$this->insertByBatch($migrationTableCheckListIds, $insertString);
				$newAnimals += $insertBatchCount;
				$insertBatchCount = 0;
			}
			$this->cmdUtil->advanceProgressBar(1, 'Migrating animalData new|updated|skipped: '.$newAnimals.'|'.$updatedAnimals.'|'.$skippedAnimals.'  insertBatch: '.$insertBatchCount);

			$this->cmdUtil->setEndTimeAndPrintFinalOverview();
		}


		//Fix secondary vsmIds
		$sql = "SELECT a.id, v.secondary_vsm_id, v.primary_vsm_id FROM animal a
  				INNER JOIN vsm_id_group v ON v.secondary_vsm_id = a.name";
		$results = $this->conn->query($sql)->fetchAll();

		if(count($results) > 0) {
			$this->cmdUtil->setStartTimeAndPrintIt(count($results),1);
			foreach ($results as $result) {
				$animalId = $result['id'];
				$primaryVsmId = $result['primary_vsm_id'];
				$secondaryVsmId = $result['secondary_vsm_id'];

				$sql = "UPDATE animal SET name = '".$primaryVsmId."' WHERE id = ".$animalId;
				$this->conn->exec($sql);
				$this->cmdUtil->advanceProgressBar(1, 'Fixing secondaryVsmIds');
			}
		}
		$this->cmdUtil->setEndTimeAndPrintFinalOverview();

		if($migrateParents) {
			//Double check the data again
			$this->resetAnimalIdVsmLocationAndGenderSearchArrays();
			$this->migrateParents();
			
			$this->setMissingFathersOnAnimal();
			$this->setMissingFathersOnLitters();
		}
		
		if($updateLocationIdsOfBirth) { $this->animalRepository->updateAllLocationOfBirths($this->cmdUtil);	}
		if($clearPedigreeCountryCodesAndNumbersWithoutPedigreeRegisters) {
			$this->animalRepository->removePedigreeCountryCodeAndNumberIfPedigreeRegisterIsMissing($this->output);
		}

		if($migrateParents) { $this->fixParentAnimalIdsInMigrationTable(); }
	}


	/**
	 * @param string $vsmId
	 * @return string
	 */
	private function getPrimaryVsmId($vsmId)
	{
		if(array_key_exists($vsmId, $this->primaryVsmIdsForSecondaryIds)) {
			return $this->primaryVsmIdsForSecondaryIds[$vsmId];
		}
		return $vsmId;
	}


	private function updateByBatch($updateString)
	{
		//Remove possible trailing comma
		$updateString = rtrim($updateString,',');

		$sql = "UPDATE animal SET name = v.vsm_id, pedigree_country_code = v.pedigree_country_code, pedigree_number = v.pedigree_number,
				nickname = v.nick_name, parent_father_id = CAST(v.father_id AS INTEGER), parent_mother_id = CAST(v.mother_id AS INTEGER), gender = v.gender, type = v.type,
				 breed_code = v.breed_code, ubn_of_birth = v.ubn_of_birth, 
				 location_of_birth_id = CAST(v.location_of_birth AS INTEGER), pedigree_register_id = CAST(v.pedigree_register_id AS INTEGER), breed_type = v.breed_type,
				 scrapie_genotype = v.scrapie_genotype
				FROM (VALUES ".$updateString.") AS v(animal_id, vsm_id, pedigree_country_code, pedigree_number, nick_name,
				father_id, mother_id, gender, type, breed_code, ubn_of_birth, location_of_birth, pedigree_register_id,
				breed_type, scrapie_genotype) WHERE v.animal_id = animal.id ";
		$this->conn->exec($sql);

		//Add new records in ewe/ram/neuter tables
		AnimalRepository::fixMissingAnimalTableExtentions($this->conn);
	}


	private function insertByBatch(array $migrationTableCheckListIds, $insertString)
	{
		//Remove possible trailing comma
		$insertString = rtrim($insertString,',');

		//Insert new animal
		$sql = "INSERT INTO animal (id, name, uln_country_code, uln_number, animal_order_number,
						  pedigree_country_code, pedigree_number, nickname, parent_father_id, 
						  parent_mother_id, gender, date_of_birth, breed_code, ubn_of_birth,
						  location_of_birth_id, pedigree_register_id, breed_type, scrapie_genotype,
						  animal_type, animal_category, is_alive, is_departed_animal, is_export_animal, is_import_animal,
						  type						  
						)VALUES ".$insertString;
		$this->conn->exec($sql);

		//Add new records in ewe/ram/neuter tables
		AnimalRepository::fixMissingAnimalTableExtentions($this->conn);

		//Check records that are updated
		$updateString = '';
		$finalId = end($migrationTableCheckListIds);
		foreach ($migrationTableCheckListIds as $id) {
			$updateString = $updateString.' id = '.$id;
			if($id !== $finalId) {
				$updateString = $updateString.' OR';
			}				
		}

		$sql = "UPDATE animal_migration_table SET is_record_migrated = TRUE WHERE ".$updateString;
		$this->conn->exec($sql);
	}


	/**
	 * @param string $vsmId
	 * @param array $animalIdByVsmId
	 * @param array $genderByAnimalId
	 * @param string $targetGender
	 * @return null|int
	 */
	private function getGenderCheckedAnimalId($vsmId, $animalIdByVsmId, $genderByAnimalId, $targetGender)
	{
		if(!is_array($animalIdByVsmId) || !is_array($genderByAnimalId)) { return null; }
			
		if(ctype_digit($vsmId)) {
			if(array_key_exists($vsmId, $animalIdByVsmId)) {

				$animalId = $animalIdByVsmId[$vsmId];

				if(array_key_exists($animalId, $genderByAnimalId)) {

						$gender = $genderByAnimalId[$animalId];

						if(($targetGender == GenderType::MALE && $gender == GenderType::MALE) ||
							$targetGender == GenderType::FEMALE && $gender == GenderType::FEMALE) {
							return intval($animalId);
						}
				}
			}
		}

		return null;
	}
	
	
	/**
	 * @param int $animalId
	 * @param string $vsmId
	 * @param array $animalIdByVsmId
	 * @param array $genderByAnimalId
	 * @param string $parent
	 * @return null|int
	 */
	private function checkAndFillEmptyAnimalId($animalId, $vsmId, $animalIdByVsmId, $genderByAnimalId, $parent)
	{
		if(!array_key_exists($animalId, $genderByAnimalId) && !is_array($animalIdByVsmId)) { return null; }

		if($animalId != null) {
			$gender = $genderByAnimalId[$animalId];
			if(array_key_exists($vsmId, $this->parentVsmIdsUpdated)) {
				if(($parent == Constant::FATHER_NAMESPACE && $gender == GenderType::MALE) ||
					$parent == Constant::MOTHER_NAMESPACE && $gender == GenderType::FEMALE) {
					return intval($animalId);
				}

			} elseif(ctype_digit($vsmId)) {
				if(array_key_exists($vsmId, $animalIdByVsmId)) {

					$animalId = $animalIdByVsmId[$vsmId];

					//Update animalMigrationTable
					if(!array_key_exists($vsmId, $this->parentVsmIdsUpdated)) {

						if($gender == GenderType::FEMALE) {
							$sql = "UPDATE animal_migration_table SET mother_id = ".$animalId." WHERE mother_vsm_id = '".$vsmId."'";
							$this->conn->exec($sql);
							$sql = "UPDATE animal_migration_table SET father_id = NULL WHERE father_vsm_id = '".$vsmId."'";
							$this->conn->exec($sql);
						} elseif($gender == GenderType::MALE) {
							$sql = "UPDATE animal_migration_table SET father_id = ".$animalId." WHERE father_vsm_id = '".$vsmId."'";
							$this->conn->exec($sql);
							$sql = "UPDATE animal_migration_table SET mother_id = NULL WHERE mother_vsm_id = '".$vsmId."'";
							$this->conn->exec($sql);
						}
						//Skip if gender is not known

						$this->parentVsmIdsUpdated[$vsmId] = $vsmId;
					}

					if(($parent == Constant::FATHER_NAMESPACE && $gender == GenderType::MALE) ||
						$parent == Constant::MOTHER_NAMESPACE && $gender == GenderType::FEMALE) {
						return intval($animalId);
					}
				}
			}
		}
		
		return null;
	}

	
	private function migrateParents()
	{
		$sql = "SELECT a.id as animal_id, vsm_id,
				  father_vsm_id, f.id as father_id_from_vsm_id, a.parent_father_id
				FROM animal a
				  INNER JOIN animal_migration_table t ON a.name = cast(t.vsm_id as varchar(255))
				  LEFT JOIN animal f ON f.name = cast(t.father_vsm_id as varchar(255))
				WHERE f.id NOTNULL AND a.parent_father_id ISNULL AND f.type = 'Ram'";
		$resultsOfFather = $this->conn->query($sql)->fetchAll();

		$sql = "SELECT a.id as animal_id, vsm_id,
				  mother_vsm_id, m.id as mother_id_from_vsm_id, a.parent_mother_id
				FROM animal a
				  INNER JOIN animal_migration_table t ON a.name = cast(t.vsm_id as varchar(255))
				  LEFT JOIN animal m ON m.name = cast(t.mother_vsm_id as varchar(255))
				WHERE m.id NOTNULL AND a.parent_mother_id ISNULL AND m.type = 'Ewe'";
		$resultsOfMothers = $this->conn->query($sql)->fetchAll();

		$fatherUpdateString = '';
		$motherUpdateString = '';
		$fathersToUpdateCount = 0;
		$fathersUpdatedCount = 0;
		$mothersToUpdateCount = 0;
		$mothersUpdatedCount = 0;
		$overallCount = 0;
		$totalCountFather = count($resultsOfFather);
		$totalCountMother = count($resultsOfMothers);

		if($totalCountFather+$totalCountMother == 0) {
			$this->output->writeln('All missing parents have already been set');
			return;
		}

		$this->cmdUtil->setStartTimeAndPrintIt($totalCountFather + $totalCountMother, 1);

		$loopCounter = 0;
		foreach ($resultsOfFather as $result) {
			$animalId = $result['animal_id'];
			$fatherIdFromVsmId = $result['father_id_from_vsm_id'];
			$currentFatherId = $result['parent_father_id'];

			if($currentFatherId == null && $fatherIdFromVsmId != null) {
				$fatherUpdateString = $fatherUpdateString.'('.$fatherIdFromVsmId.','.$animalId.'),';
				$fathersToUpdateCount++;
			}

			$overallCount++;
			$loopCounter++;

			//Update fathers
			if(($totalCountFather == $loopCounter || ($fathersToUpdateCount%self::UPDATE_BATCH_SIZE == 0 && $fathersToUpdateCount != 0))
				&& $fatherUpdateString != '') {
				$fatherUpdateString = rtrim($fatherUpdateString, ',');
				$sql = "UPDATE animal as a SET parent_father_id = c.new_father_id
				FROM (VALUES ".$fatherUpdateString.") as c(new_father_id, id) WHERE c.id = a.id ";
				$this->conn->exec($sql);
				//Reset batch values
				$fatherUpdateString = '';
				$fathersUpdatedCount += $fathersToUpdateCount;
				$fathersToUpdateCount = 0;
			}
			
			$this->cmdUtil->advanceProgressBar(1, 'Fathers updated|inBatch: '.$fathersUpdatedCount.'|'.$fathersToUpdateCount.' - '
				.'Mothers updated|inBatch: '.$mothersUpdatedCount.'|'.$mothersToUpdateCount);
		}

		$loopCounter = 0;
		foreach ($resultsOfMothers as $result) {
			$animalId = $result['animal_id'];
			$motherIdFromVsmId = $result['mother_id_from_vsm_id'];
			$currentMotherId = $result['parent_mother_id'];

			if($currentMotherId == null && $motherIdFromVsmId != null) {
				$motherUpdateString = $motherUpdateString.'('.$motherIdFromVsmId.','.$animalId.'),';
				$mothersToUpdateCount++;
			}

			$overallCount++;
			$loopCounter++;
			

			//Update mothers
			if(($totalCountMother == $loopCounter || ($mothersToUpdateCount%self::UPDATE_BATCH_SIZE == 0 && $mothersToUpdateCount != 0))
			&& $motherUpdateString != '') {
				$motherUpdateString = rtrim($motherUpdateString, ',');
				$sql = "UPDATE animal as a SET parent_mother_id = c.new_mother_id
				FROM (VALUES ".$motherUpdateString.") as c(new_mother_id, id) WHERE c.id = a.id ";
				$this->conn->exec($sql);
				//Reset batch values
				$motherUpdateString = '';
				$mothersUpdatedCount += $mothersToUpdateCount;
				$mothersToUpdateCount = 0;
			}
			$this->cmdUtil->advanceProgressBar(1, 'Fathers updated|inBatch: '.$fathersUpdatedCount.'|'.$fathersToUpdateCount.' - '
												 .'Mothers updated|inBatch: '.$mothersUpdatedCount.'|'.$mothersToUpdateCount);
		}
		$this->cmdUtil->setEndTimeAndPrintFinalOverview();
	}
	
	
	private function setMissingFathersOnAnimal()
	{
		$sql = "SELECT x.id as animal_id, a.parent_mother_id, a.parent_father_id, y.parent_father_id as found_father_id
				FROM animal a
				INNER JOIN (
					SELECT CONCAT(m.parent_mother_id,'--',DATE(m.date_of_birth)) as key, m.id
					FROM animal m
					WHERE m.parent_mother_id NOTNULL AND m.parent_father_id ISNULL
					)x ON x.id = a.id
				INNER JOIN (
					SELECT CONCAT(i.parent_mother_id,'--',DATE(i.date_of_birth)) as key, i.parent_father_id
					FROM animal i
					WHERE i.parent_mother_id NOTNULL AND i.parent_father_id NOTNULL
					GROUP BY i.parent_mother_id, DATE(i.date_of_birth), i.parent_father_id
					)y ON y.key = x.key";
		$results = $this->conn->query($sql)->fetchAll();
		$totalCount = count($results);

		$this->cmdUtil->setStartTimeAndPrintIt($totalCount, 1);

		$updateString = '';
		$count = 0;
		$inBatchCount = 0;
		$updatedCount = 0;
		foreach ($results as $result) {
			$animalId = $result['animal_id'];
			$foundFatherId = $result['found_father_id'];

			$updateString = $updateString.'('.$foundFatherId.','.$animalId.')';
			$count++;
			if($count == $totalCount || $count%self::UPDATE_BATCH_SIZE == 0) {
				$sql = "UPDATE animal as a SET parent_father_id = v.found_father_id
						FROM (VALUES ".$updateString."
							 ) as v(found_father_id, animal_id) WHERE a.id = v.animal_id";
				$this->conn->exec($sql);
				//Reset batch string and counters
				$updateString = '';
				$updatedCount += $inBatchCount;
				$inBatchCount = 0;
			} else {
				$inBatchCount++;
				$updateString = $updateString.',';
			}
			$this->cmdUtil->advanceProgressBar(1, 'Set missing fathers by common mother and dateOfBirth. processed|inBatch: '.$updatedCount.'|'.$inBatchCount);
		}
		$this->cmdUtil->setEndTimeAndPrintFinalOverview();
	}


	/**
	 * Note that all litters have known mothers, so we do not have to check if they are missing
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 */
	private function setMissingFathersOnLitters()
	{
		$sql = "SELECT a.parent_father_id, l.animal_father_id, l.id as litter_id FROM litter l
				INNER JOIN animal a ON l.id = a.litter_id
				WHERE a.parent_father_id NOTNULL AND l.animal_father_id ISNULL";
		$results = $this->conn->query($sql)->fetchAll();

		$totalCount = count($results);

		if($totalCount == 0) {
			$this->output->writeln('No missing fathers in litter');
			return;
		}

		$this->cmdUtil->setStartTimeAndPrintIt($totalCount, 1);

		$updateString = '';
		$count = 0;
		$inBatchCount = 0;
		$updatedCount = 0;

		foreach($results as $result) {
			$litterId = $result['litter_id'];
			$foundFatherId = $result['parent_father_id'];

			$updateString = $updateString.'('.$foundFatherId.','.$litterId.'),';
			$count++;

			if($count == $totalCount || $count%self::UPDATE_BATCH_SIZE == 0) {
				$updateString = rtrim($updateString, ',');

				$sql = "UPDATE litter as a SET animal_father_id = v.found_father_id
						FROM (VALUES ".$updateString."
							 ) as v(found_father_id, litter_id) WHERE a.id = v.litter_id";
				$this->conn->exec($sql);

				//Reset batch string and counters
				$updateString = '';
				$updatedCount += $inBatchCount;
				$inBatchCount = 0;

			} else {
				$inBatchCount++;
			}
			$this->cmdUtil->advanceProgressBar(1, 'Set missing fathers by common mother and dateOfBirth. processed|inBatch: '.$updatedCount.'|'.$inBatchCount);
		}
		$this->cmdUtil->setEndTimeAndPrintFinalOverview();

	}
	

	/**
	 * To be safe and protect the sanctity of the database
	 * we must only save de records of the animals that have non conflicting gender data.f
	 * 
	 * Thus:
	 * Only fix the neuters in the current database.
	 * 
	 * @throws \Doctrine\DBAL\DBALException
	 */
	private function fixGendersInAnimalTable()
	{
		dump('TODO: FIX GENDERS IN ANIMAL TABLE');die;
		
		//SearchArrays
		$sql = "SELECT animal_id, gender_in_file FROM animal_migration_table WHERE animal_id NOTNULL ";
		$results = $this->conn->query($sql)->fetchAll();
		$genderInFileByAnimalId = [];
		foreach ($results as $result) {
			$animalId = $result['animal_id'];
			$genderInFile = $result['gender_in_file'];
			$genderInFileByAnimalId[$animalId] = $genderInFile;
		}


		$sql = "SELECT animal_mother_id FROM litter
				WHERE animal_mother_id NOTNULL";
		$results = $this->conn->query($sql)->fetchAll();
		$mothersIdsInLitter = [];

		foreach ($results as $result) {
			$animalMotherId = $result['animal_mother_id'];
			$mothersIdsInLitter[$animalMotherId] = $animalMotherId;
		}

		$sql = "SELECT parent_mother_id, parent_father_id FROM animal
				WHERE parent_mother_id NOTNULL and parent_father_id NOTNULL ";
		$results = $this->conn->query($sql)->fetchAll();

		$mothersInAnimalTable = [];
		$fathersInAnimalTable = [];
		foreach ($results as $result) {
			$motherId = $result['parent_mother_id'];
			$fatherId = $result['parent_father_id'];
			if($motherId != null && $motherId != 0) { $mothersInAnimalTable[$motherId] = $motherId; }
			if($fatherId != null && $fatherId != 0) { $fathersInAnimalTable[$fatherId] = $fatherId; }
		}


		$sql = "SELECT id, gender FROM animal";
		$results = $this->conn->query($sql)->fetchAll();

		$this->cmdUtil->setStartTimeAndPrintIt(count($results),1);

		$discrepancyBetweenGenders = 0;
		$gendersUpdated = 0;
		$neuters = 0;
		$maleOrFemales = 0;
		foreach ($results as $result) {
			$animalId = $result['id'];
			$genderInAnimalTable = $result['gender'];

			//TODO
			if(array_key_exists($animalId, $genderInFileByAnimalId)) {
				$genderInFile = $genderInFileByAnimalId[$animalId];
				if($genderInFile != $genderInAnimalTable) {

					$updateGender = true;
					if($genderInAnimalTable == GenderType::NEUTER) { $neuters++; }
					else {
						$maleOrFemales++;
						//Check if Male or Female already had children in the database, which will cause issues
						if($genderInAnimalTable == GenderType::MALE && array_key_exists($animalId, $mothersInAnimalTable)) {
							$this->output->writeln('id|file|table: '.$animalId.'|'.$genderInFile.'|'.$genderInAnimalTable.' has children in db');
							$updateGender = false;
						} elseif ($genderInAnimalTable == GenderType::FEMALE && array_key_exists($animalId, $fathersInAnimalTable)) {
							$this->output->writeln('id|file|table: '.$animalId.'|'.$genderInFile.'|'.$genderInAnimalTable.' has children in db');
							$updateGender = false;
						}
					}

					if($updateGender) {
						GenderChangerForMigrationOnly::changeGenderBySql($this->em, $animalId, $genderInAnimalTable, $genderInFile);
						$gendersUpdated++;
					}

					$discrepancyBetweenGenders++;
				}
			}
			$this->cmdUtil->advanceProgressBar(1, 'AnimalTable GenderFix, Updated|Discrepancy||m/f|neuter|: '.$gendersUpdated.'|'.$discrepancyBetweenGenders.'||'.$maleOrFemales.'|'.$neuters);
		}
		$this->cmdUtil->setEndTimeAndPrintFinalOverview();
	}


	/**
	 * @return bool
	 */
	private function fixGenders()
	{
		/*
		 * 1. First check if csv NEUTERS are mothers or fathers and update the genders accordingly.
		 *    Use $this->verifyNeutersInCSV();
		 *
		 * 2. Check if csv FEMALEs are only mothers
		 * 3. Check if csv MALEs are only fathers
		 *
		 * 4. Fix database genders based on the genders in the CSV file. BUT ONLY FOR NEUTERS!!! SKIP IF MALE-FEMALE are conflicting
		 *
		 *    			  |		Database values
		 *  			  |	MALE   NEUTER   FEMALE
		 * ----------------------------------------
		 *  CSV	   	 MALE |	 V		 U1		 F2M
		 * values  NEUTER |  U2      U2      U2
		 * 		   FEMALE |	 M2F     U1      V
		 *
		 * V = good, keep gender
		 * U1 = update NEUTER to gender in CSV file
		 * U2 = after step 1 we know NEUTER in csv are really NEUTER, so keep the genders in the database
		 * M2F = (after check in step 2)
		 * F2M = (after check in step 3)
		 * For M2F and F2M SKIP THEM!
		 *
		 * TODO fix animals that are simultaneously a mother and father
		 */

		/* Create searchArrays */
		$animalIdByVsmId = $this->animalRepository->getAnimalPrimaryKeysByVsmIdArray();

		//mother/father in csvFile
		$sql = "SELECT m.mother_vsm_id, m.father_vsm_id FROM animal_migration_table m";
		$results = $this->conn->query($sql)->fetchAll();

		$vsmMothers = [];
		$vsmFathers = [];
		foreach ($results as $result) {
			$motherVsmId = $result['mother_vsm_id'];
			$fatherVsmId = $result['father_vsm_id'];
			if($motherVsmId != null && $motherVsmId != 0) { $vsmMothers[$motherVsmId] = $motherVsmId; }
			if($fatherVsmId != null && $fatherVsmId != 0) { $vsmFathers[$fatherVsmId] = $fatherVsmId; }
		}

		//Gender in database, animal table
		$sql = "SELECT id, gender FROM animal";
		$results = $this->conn->query($sql)->fetchAll();

		$gendersInDatabase = [];
		foreach ($results as $result) {
			$gendersInDatabase[$result['id']] = $result['gender'];
		}

		//mother/father in database, animal table
		$sql = "SELECT a.parent_father_id, a.parent_mother_id FROM animal a";
		$results = $this->conn->query($sql)->fetchAll();

		$animalIdMothers = [];
		$animalIdFathers = [];
		foreach ($results as $result) {
			$motherAnimalId = $result['parent_mother_id'];
			$fatherAnimalId = $result['parent_father_id'];
			if($motherAnimalId != null && $motherAnimalId != 0) { $animalIdMothers[$motherAnimalId] = $motherAnimalId; }
			if($fatherAnimalId != null && $fatherAnimalId != 0) { $animalIdFathers[$fatherAnimalId] = $fatherAnimalId; }
		}

		//genders in csv file
		$sql = "SELECT m.vsm_id, gender_in_file FROM animal_migration_table m";
		$results = $this->conn->query($sql)->fetchAll();

		$vsmNeuters = [];
		$vsmMales = [];
		$vsmFemales = [];
		foreach($results as $result) {
			$vsmId = $result['vsm_id'];
			$genderInFile = $result['gender_in_file'];
			switch ($genderInFile) {
				case GenderType::FEMALE:
					$vsmFemales[$vsmId] = $vsmId;
					break;
				case GenderType::MALE:
					$vsmMales[$vsmId] = $vsmId;
					break;
				case GenderType::NEUTER:
					$vsmNeuters[$vsmId] = $vsmId;
					break;
				default:
					$vsmNeuters[$vsmId] = $vsmId;
					break;
			}
		}

		/* end of searchArrays */
		
		$animalIdsOfSimultaneousMotherAndFather = [];

		$this->output->writeln(['===== VSM in CSV =====' ,
			'NEUTERS: '.count($vsmNeuters).' MALES: '.count($vsmMales).' FEMALES: '.count($vsmFemales).' MOTHERS: '.count($vsmMothers).' FATHERS: '.count($vsmFathers)]);

		if(self::PRINT_OUT_FILENAME_INCORRECT_GENDERS) {
			file_put_contents($this->outputFolder.'/'.self::FILENAME_INCORRECT_GENDERS, self::INCORRECT_GENDER_COLUMN_HEADERS."\n", FILE_APPEND);
		}
		
		//Check NEUTERs in csv
		$areAllNeutersGenderless = true;
		$allNeuterHaveOnlyOneGender = true;
		foreach ($vsmNeuters as $vsmId) {
			$genderInFile = GenderType::NEUTER;
			$isVsmMother = array_key_exists($vsmId, $vsmMothers);
			$isVsmFather = array_key_exists($vsmId, $vsmFathers);

			$isAnimalIdMother = false;
			$isAnimalIdFather = false;
			$isFemaleInDatabase = false;
			$isMaleInDatabase = false;

			$genderInDatabase = '';
			if(array_key_exists($vsmId, $animalIdByVsmId)) {
				$animalId = $animalIdByVsmId[$vsmId];
				if($animalId != null) {
					$isAnimalIdMother = array_key_exists($animalId, $animalIdMothers);
					$isAnimalIdFather = array_key_exists($animalId, $animalIdFathers);
					if(array_key_exists($animalId, $gendersInDatabase)){
						$genderInDatabase = $gendersInDatabase[$animalId];
						if($genderInDatabase == GenderType::FEMALE) { $isFemaleInDatabase = true; }
						elseif($genderInDatabase == GenderType::MALE) { $isMaleInDatabase = true; }
						$genderInDatabase = Translation::getGenderInDutch($genderInDatabase);
					}
				}
			}
			$genderInFile = Translation::getGenderInDutch($genderInFile);

			$hasIncorrectGenders = false;
			if($isVsmMother && $isVsmFather) {
				$hasIncorrectGenders = true;
				$allNeuterHaveOnlyOneGender = false;
				$animalIdsOfSimultaneousMotherAndFather[$vsmId] = $vsmId;

			} else if($isVsmMother) {
				if(!$isAnimalIdFather && !$isMaleInDatabase) {
					$sql = "UPDATE animal_migration_table SET corrected_gender = gender_in_file, gender_in_file = '" . GenderType::FEMALE . "' WHERE vsm_id = ".$vsmId;
					$this->conn->exec($sql);
				} else {
					$hasIncorrectGenders = true;
				}
				
			} else if($isVsmFather) {
				if(!$isAnimalIdMother && !$isFemaleInDatabase) {
					$sql = "UPDATE animal_migration_table SET corrected_gender = gender_in_file, gender_in_file = '" . GenderType::MALE . "' WHERE vsm_id = ".$vsmId;
					$this->conn->exec($sql);
				} else {
					$hasIncorrectGenders = true;
				}

			}
			if($hasIncorrectGenders && self::PRINT_OUT_FILENAME_INCORRECT_GENDERS) {
				$errorMessage = $vsmId.';'.$genderInFile.';'.StringUtil::getBooleanAsString($isVsmMother).';'.StringUtil::getBooleanAsString($isVsmFather).';'.
					$genderInDatabase.';'.StringUtil::getBooleanAsString($isAnimalIdMother).';'.StringUtil::getBooleanAsString($isAnimalIdFather).';'.$this->getChildrenCountByParentTypeAsString($vsmId, $animalIdByVsmId);
				file_put_contents($this->outputFolder.'/'.self::FILENAME_INCORRECT_GENDERS, $errorMessage."\n", FILE_APPEND);
				$this->output->writeln($errorMessage);
			}
		}


		//Check FEMALEs in csv  NOTE DON'T FIX THEM TO PREVENT GENDER CONFLICTS!
		$areAllFemalesOnlyMothers = true;
		foreach ($vsmFemales as $vsmId) {
			$genderInFile = GenderType::FEMALE;
			$isVsmMother = array_key_exists($vsmId, $vsmMothers);
			$isVsmFather = array_key_exists($vsmId, $vsmFathers);

			$isAnimalIdMother = false;
			$isAnimalIdFather = false;
			$isMaleInDatabase = false;

			$genderInDatabase = '';
			if(array_key_exists($vsmId, $animalIdByVsmId)) {
				$animalId = $animalIdByVsmId[$vsmId];
				if($animalId != null) {
					$isAnimalIdMother = array_key_exists($animalId, $animalIdMothers);
					$isAnimalIdFather = array_key_exists($animalId, $animalIdFathers);
					if(array_key_exists($animalId, $gendersInDatabase)){
						$genderInDatabase = $gendersInDatabase[$animalId];
						if($genderInDatabase == GenderType::MALE) { $isMaleInDatabase = true; }
						$genderInDatabase = Translation::getGenderInDutch($genderInDatabase);
					}
				}
			}
			$genderInFile = Translation::getGenderInDutch($genderInFile);

			$hasIncorrectGenders = false;
			if($isVsmMother && $isVsmFather) {
				$hasIncorrectGenders = true;
				$areAllFemalesOnlyMothers = false;
				$animalIdsOfSimultaneousMotherAndFather[$vsmId] = $vsmId;

			} else if($isVsmFather || $isAnimalIdFather) {
				$hasIncorrectGenders = true;
				$areAllFemalesOnlyMothers = false;
				
			} else if($isMaleInDatabase) {
				$hasIncorrectGenders = true;
			}

			if($hasIncorrectGenders && self::PRINT_OUT_FILENAME_INCORRECT_GENDERS) {
				$errorMessage = $vsmId.';'.$genderInFile.';'.StringUtil::getBooleanAsString($isVsmMother).';'.StringUtil::getBooleanAsString($isVsmFather).';'.
					$genderInDatabase.';'.StringUtil::getBooleanAsString($isAnimalIdMother).';'.StringUtil::getBooleanAsString($isAnimalIdFather).';'.$this->getChildrenCountByParentTypeAsString($vsmId, $animalIdByVsmId);
				file_put_contents($this->outputFolder.'/'.self::FILENAME_INCORRECT_GENDERS, $errorMessage."\n", FILE_APPEND);
				$this->output->writeln($errorMessage);
			}
		}


		//Check MALEs in csv  NOTE DON'T FIX THEM TO PREVENT GENDER CONFLICTS!
		$areAllMalesOnlyFathers = true;
		foreach ($vsmMales as $vsmId) {
			$genderInFile = GenderType::MALE;
			$isVsmMother = array_key_exists($vsmId, $vsmMothers);
			$isVsmFather = array_key_exists($vsmId, $vsmFathers);

			$isAnimalIdMother = false;
			$isAnimalIdFather = false;
			$isFemaleInDatabase = false;

			$genderInDatabase = '';
			if(array_key_exists($vsmId, $animalIdByVsmId)) {
				$animalId = $animalIdByVsmId[$vsmId];
				if($animalId != null) {
					$isAnimalIdMother = array_key_exists($animalId, $animalIdMothers);
					$isAnimalIdFather = array_key_exists($animalId, $animalIdFathers);
					if(array_key_exists($animalId, $gendersInDatabase)){
						$genderInDatabase = $gendersInDatabase[$animalId];
						if($genderInDatabase == GenderType::FEMALE) { $isFemaleInDatabase = true; }
						$genderInDatabase = Translation::getGenderInDutch($genderInDatabase);
					}
				}
			}
			$genderInFile = Translation::getGenderInDutch($genderInFile);

			$hasIncorrectGenders = false;
			if($isVsmMother && $isVsmFather) {
				$hasIncorrectGenders = true;
				$areAllMalesOnlyFathers = false;
				$animalIdsOfSimultaneousMotherAndFather[$vsmId] = $vsmId;

			} elseif($isVsmMother || $isAnimalIdMother) {
				$hasIncorrectGenders = true;
				$areAllMalesOnlyFathers = false;
				
			} elseif($isFemaleInDatabase) {
				$hasIncorrectGenders = true;
			}

			if($hasIncorrectGenders && self::PRINT_OUT_FILENAME_INCORRECT_GENDERS) {
				$errorMessage = $vsmId.';'.$genderInFile.';'.StringUtil::getBooleanAsString($isVsmMother).';'.StringUtil::getBooleanAsString($isVsmFather).';'.
					$genderInDatabase.';'.StringUtil::getBooleanAsString($isAnimalIdMother).';'.StringUtil::getBooleanAsString($isAnimalIdFather).';'.$this->getChildrenCountByParentTypeAsString($vsmId, $animalIdByVsmId);
				file_put_contents($this->outputFolder.'/'.self::FILENAME_INCORRECT_GENDERS, $errorMessage."\n", FILE_APPEND);
				$this->output->writeln($errorMessage);
			}
		}

		return $allNeuterHaveOnlyOneGender && $areAllFemalesOnlyMothers && $areAllMalesOnlyFathers;
	} 


	/**
	 * @throws \Doctrine\DBAL\DBALException
	 */
	private function getUbnOfBirthFromUln()
	{
		$sql = "SELECT ubn_of_birth, uln_number, date_of_birth, id FROM animal_migration_table
				WHERE uln_country_code = 'NL' AND date_of_birth < '2010-01-01' AND ubn_of_birth ISNULL AND uln_number NOTNULL 
					AND is_ubn_updated = FALSE ";
		$results = $this->conn->query($sql)->fetchAll();

		$count = count($results);
		if($count == 0) { $this->output->writeln('All UbnsOfBirth have already been processed'); return; }
		$this->cmdUtil->setStartTimeAndPrintIt($count+1, 1);

		$updateCount = 0;
		foreach ($results as $result) {
			$id = $result['id'];
			$ulnNumber = $result['uln_number'];
			$ubn = StringUtil::getUbnFromUlnNumber($ulnNumber);
			if($ubn != null) {
				$sql = "UPDATE animal_migration_table SET is_ubn_updated = TRUE, ubn_of_birth = '".$ubn."' WHERE id = ".$id;
				$updateCount++;
			} else {
				$sql = "UPDATE animal_migration_table SET is_ubn_updated = TRUE WHERE id = ".$id;
			}
			$this->conn->exec($sql);
			$this->cmdUtil->advanceProgressBar(1, 'ubnsOfBirth updated: '.$updateCount);
		}
		$this->cmdUtil->setProgressBarMessage('ubnsOfBirth updated: '.$updateCount);
		$this->cmdUtil->setEndTimeAndPrintFinalOverview();
	}

	
	private function fixAnimalOrderNumbers()
	{
		//Match animalOrderNumbers with ulnNumbers
		$sql = "SELECT COUNT(*) FROM animal_migration_table
				WHERE uln_number NOTNULL AND SUBSTR(uln_number, 8,12) <> animal_order_number";
		$count = $this->conn->query($sql)->fetch()['count'];
		if($count > 0) {
			$sql = "UPDATE animal_migration_table SET animal_order_number = SUBSTR(uln_number, 8,12)
					WHERE uln_number NOTNULL AND SUBSTR(uln_number, 8,12) <> animal_order_number";
			$this->conn->exec($sql);
			$this->output->writeln($count.' non-matching AnimalOrderNumbers matched with ulnNumbers');
		}
		
		//Delete animalOrderNumbers with incorrect formatting
		$sql = "SELECT COUNT(*) FROM animal_migration_table
				WHERE animal_order_number SIMILAR TO '%[a-bA-Z]%'
				OR animal_order_number NOT SIMILAR TO '%[0-9]%'
				OR length(animal_order_number) <> 5";
		$count = $this->conn->query($sql)->fetch()['count'];
		if($count > 0) {
			$sql = "UPDATE animal_migration_table SET animal_order_number = NULL
					WHERE animal_order_number SIMILAR TO '%[a-bA-Z]%'
					OR animal_order_number NOT SIMILAR TO '%[0-9]%'
					OR length(animal_order_number) <> 5";
			$this->conn->exec($sql);
			$this->output->writeln($count.' AnimalOrderNumbers with incorrect formatting deleted');
		}
	}
	

	/**
	 * @throws \Doctrine\DBAL\DBALException
	 */
	private function fixMissingAnimalOrderNumbers()
	{
		$sql = "SELECT id, pedigree_number, uln_number, stn_origin FROM animal_migration_table
				WHERE animal_order_number ISNULL AND stn_origin NOTNULL AND is_animal_order_number_updated = FALSE";
		$results = $this->conn->query($sql)->fetchAll();

		$count = count($results);
		if($count == 0) { $this->output->writeln('All AnimalOrderNumbers have already been processed'); return; }
		$this->cmdUtil->setStartTimeAndPrintIt($count+1, 1);

		$updateCount = 0;
		foreach ($results as $result) {
			$id = $result['id'];
			$pedigreeNumber = $result['pedigree_number'];
			$ulnNumber = $result['uln_number'];
			$stnOrigin = $result['stn_origin'];

			$animalOrderNumber = null;
			if($ulnNumber != null) {
				$animalOrderNumber = StringUtil::getLast5CharactersFromString($ulnNumber);
			} else if($pedigreeNumber != null) {
				$animalOrderNumber = StringUtil::getLast5CharactersFromString($pedigreeNumber);
				if(!ctype_digit($animalOrderNumber)) { $animalOrderNumber = null; }
			} else if($stnOrigin != null) {
				$animalOrderNumber = StringUtil::getLast5CharactersFromString($stnOrigin);
				if(!ctype_digit($animalOrderNumber)) { $animalOrderNumber = null; }
			}

			if($animalOrderNumber != null) {
				$sql = "UPDATE animal_migration_table SET is_animal_order_number_updated = TRUE, animal_order_number = '".$animalOrderNumber."' WHERE id = ".$id;
			} else {
				$sql = "UPDATE animal_migration_table SET is_animal_order_number_updated = TRUE WHERE id = ".$id;
			}
			$this->conn->exec($sql);
			$this->cmdUtil->advanceProgressBar(1, 'animalOrderNumbers updated: '.$updateCount);
		}
		$this->cmdUtil->setProgressBarMessage('animalOrderNumbers updated: '.$updateCount);
		$this->cmdUtil->setEndTimeAndPrintFinalOverview();
	}


	private function fixUlnsAndStns()
	{
		//SearchArrays
		$ubnsOfBirthByBreederNumber = $this->breederNumberRepository->getUbnOfBirthByBreederNumberSearchArray();
		$breederNumberByUbnsOfBirth = array_flip($ubnsOfBirthByBreederNumber);
		$usedUlnNumbers = $this->animalMigrationTableRepository->getExistingUlnsInAnimalAndAnimalMigrationTables();
		$usedPedigreeNumbers = $this->animalMigrationTableRepository->getExistingPedigreeNumbersInAnimalAndAnimalMigrationTables();

		$this->output->writeln('=== Fix duplicate stns ===');
		$this->fixDuplicateStns();

		$this->output->writeln('=== Delete incorrect ulns and stns ===');
		$usedPedigreeNumbers = $this->deleteIncorrectUlnsAndStns($usedPedigreeNumbers, $breederNumberByUbnsOfBirth);	
		
		$this->output->writeln('=== Fix missing ulns by pedigreeNumber and ubnOfBirth ===');
		$usedUlnNumbers = $this->fixMissingUlnsByPedigreeNumberAndUbnOfBirth($usedUlnNumbers);
		$this->output->writeln('=== Fix missing ulns by pedigreeNumber only ===');
		$usedUlnNumbers = $this->fixMissingUlnsByPedigreeNumberOnly($ubnsOfBirthByBreederNumber, $usedUlnNumbers);

		$count = $this->animalMigrationTableRepository->countAnimalOrderNumbersNotMatchingUlnNumbers();
		$this->output->writeln("=== Fix ".$count." animalOrderNumbers to match updated ulnNumbers ===");
		$this->animalMigrationTableRepository->fixAnimalOrderNumberToMatchUlnNumber();

		$this->output->writeln("=== Fix duplicate ulns ===");
		$usedUlnNumbers = $this->fixDuplicateUlns($usedUlnNumbers);
	}


	private function fixDuplicateUlns($usedUlnNumbers)
	{
		$this->fixUlnDuplicatesWithMoreThan2Records();
		$this->fixUlnDuplicatesWith2RecordsOfIdenticalAnimals();
		
		return $usedUlnNumbers;
	}


	private function fixUlnDuplicatesWithMoreThan2Records()
	{
		//First fix the duplicates with more than 2

		$sql = "SELECT COUNT(*) FROM animal_migration_table a
				INNER JOIN (
					SELECT uln_country_code, uln_number FROM animal_migration_table
					WHERE uln_country_code NOTNULL AND uln_number NOTNULL
					GROUP BY uln_country_code, uln_number HAVING COUNT(*) > 2
					)d ON d.uln_country_code = a.uln_country_code AND d.uln_number = a.uln_number";
		$count = $this->conn->query($sql)->fetch()['count'];
		if($count == 0) { $this->output->writeln('Uln duplicates of more than 2 have already been deleted!'); return; }
		
		//CASE: NL 109994833896

		//Update first letter in animalOrderNumber (incl uln) to 9
		$sql = "UPDATE animal_migration_table SET animal_id = NULL, uln_number = 109994893896,
			animal_order_number = 93896, deleted_uln_origin = uln_origin, uln_origin = 'NL 109994893896' WHERE id = 958253";
		$this->conn->exec($sql);

		$sql = "DELETE FROM animal_migration_table WHERE id = 1072556";
		$this->conn->exec($sql);

		//CASE: NL 076474802053

		$sql = "SELECT * FROM animal_migration_table a
			INNER JOIN (
				SELECT uln_country_code, uln_number FROM animal_migration_table
				WHERE uln_country_code NOTNULL AND uln_number NOTNULL
				GROUP BY uln_country_code, uln_number HAVING COUNT(*) > 2
				)d ON d.uln_country_code = a.uln_country_code AND d.uln_number = a.uln_number
			WHERE uln_origin = 'NL 076474802053'";
		$results = $this->conn->query($sql)->fetchAll();


		$primaryVsmId = null;
		foreach ($results as $result) {
			if($result['date_of_birth'] != null) {
				$primaryVsmId = $result['vsm_id'];
			}
		}

		foreach ($results as $result) {
			if($result['date_of_birth'] != null) {
				$primaryVsmId = $result['vsm_id'];
			}
		}

		$vsmIdGroups = $this->getVsmIdGroups();
		foreach($results as $result) {
			$vsmId = $result['vsm_id'];
			if($result['date_of_birth'] == null) {
				$this->updateChildrenVsmIds($primaryVsmId, $vsmId);
				$vsmIdGroups = $this->saveVsmIdGroup($primaryVsmId, $vsmId, $vsmIdGroups);
				//They all don't have any parents
				$sql = "DELETE FROM animal_migration_table WHERE id = ".$result['id'];
				$this->conn->exec($sql);
			}
		}
		$this->output->writeln('Uln duplicates of more than 2 are deleted!');
	}


	/**
	 * Fixing double ulns of same animal
	 *
	 * We can only be sure a duplicate record belongs to the same animal
	 * if the following traits are identical:
	 * uln_country_code, uln_number, date_of_birth, father_vsm_id, mother_vsm_id
	 */
	private function fixUlnDuplicatesWith2RecordsOfIdenticalAnimals()
	{
		$sql ="SELECT * FROM animal_migration_table a
			  INNER JOIN (
				   SELECT uln_country_code, uln_number FROM animal_migration_table
				   WHERE uln_country_code NOTNULL AND uln_number NOTNULL
				   GROUP BY uln_country_code, uln_number, date_of_birth, father_vsm_id, mother_vsm_id HAVING COUNT(*) > 1
				 )d ON d.uln_country_code = a.uln_country_code AND d.uln_number = a.uln_number";
		$results = $this->conn->query($sql)->fetchAll();
		if(count($results) == 0) {
			$this->output->writeln('Uln duplicates with 2 records of identical animal have already been merged');
			return;
		}

		$groupedSearchArray = SqlUtil::createGroupedSearchArrayFromSqlResults($results, 'uln_country_code', 'uln_number');
		$vsmIdGroups = $this->getVsmIdGroups();

		$this->cmdUtil->setStartTimeAndPrintIt(count($groupedSearchArray),1);

		foreach ($groupedSearchArray as $ulnGroup) {

			//Merge values
			$animalId = null;
			$ulnOrigin = null;
			$validatedUlnOrigin = null;
			$stnOrigin = null;
			$validatedStnOrigin = null;
			$pedigreeCountryCode = null;
			$pedigreeNumber = null;
			$ulnCountryCode = null;
			$ulnNumber = null;
			$animalOrderNumber = null;
			$fatherVsmId = 0;
			$motherVsmId = 0;
			$genderInFile = GenderType::NEUTER;
			$genderInDatabase = GenderType::NEUTER;
			$ubnOfBirth = null;
			$breedType = null;
			$breedCode = null;
			$scrapieGenotype = null;
			$locationOfBirthId = null;
			$pedigreeRegisterId = null;
			$nickname = null;

			foreach ($ulnGroup as $animalRecord) {

				if(Validator::verifyUlnFormat($animalRecord['uln_origin'] ,true)) {
					$validatedUlnOrigin = $animalRecord['uln_origin']; }
				if(Validator::verifyPedigreeCountryCodeAndNumberFormat($animalRecord['stn_origin'],true)) {
					$validatedStnOrigin = $animalRecord['stn_origin']; }

				if($animalRecord['uln_origin'] != null) { $ulnOrigin = $animalRecord['uln_origin'];	}
				if($animalRecord['stn_origin'] != null) { $stnOrigin = $animalRecord['stn_origin']; }

				if($animalRecord['animal_id'] != null) 	{ $animalId = $animalRecord['animal_id']; }
				if($animalRecord['father_vsm_id'] != 0) { $fatherVsmId = $animalRecord['father_vsm_id']; }
				if($animalRecord['mother_vsm_id'] != 0) { $motherVsmId = $animalRecord['mother_vsm_id']; }
				if($animalRecord['gender_in_file'] != GenderType::NEUTER) 		{ $genderInFile = $animalRecord['gender_in_file']; }
				if($animalRecord['gender_in_database'] != GenderType::NEUTER) 	{ $genderInDatabase = $animalRecord['gender_in_database']; }
				if($animalRecord['ubn_of_birth'] != null) { $ubnOfBirth = $animalRecord['ubn_of_birth']; }
				if($animalRecord['breed_type'] != null) { $breedType = $animalRecord['breed_type']; }
				if($animalRecord['breed_code'] != null) { $breedCode = $animalRecord['breed_code']; }
				if($animalRecord['scrapie_genotype'] != null) { $scrapieGenotype = $animalRecord['scrapie_genotype']; }
				if($animalRecord['location_of_birth_id'] != null) { $locationOfBirthId = $animalRecord['location_of_birth_id']; }
				if($animalRecord['pedigree_register_id'] != null) { $pedigreeRegisterId = $animalRecord['pedigree_register_id']; }

				if($animalRecord['nickname'] != null) {
					$nickname = strtr($animalRecord['nickname'],
						['JT 14' => 'Oak Tree Lisa 17',
							'8,26223E+14' => 'LAX 92/02',
							'Oak Tree Jack' => '32 JT03 KHS (Oak Tree Jack)',
							'32 JT03 KHS' => '32 JT03 KHS (Oak Tree Jack)']);
				}

				//Note! That the SQL is grouped by uln_number and uln_country_code, so we can use those values directly
				$ulnCountryCode = $animalRecord['uln_country_code'];
				$ulnNumber = $animalRecord['uln_number'];

				//Use the pedigree data already processed in previous steps
				if($animalRecord['pedigree_country_code'] != null) { $pedigreeCountryCode = $animalRecord['pedigree_country_code']; }
				if($animalRecord['pedigree_number'] != null) { $pedigreeNumber = $animalRecord['pedigree_number']; }
			}

			if($validatedUlnOrigin != null) {
				$newestFormatValidatedUlnOrigin = $this->declareTagReplaceRepository->getNewReplacementUln($validatedUlnOrigin);
				if($newestFormatValidatedUlnOrigin != null) {
					$ulnOrigin = $validatedUlnOrigin;
				}
			}
			$animalOrderNumber = $ulnNumber != null ? StringUtil::getLast5CharactersFromString($ulnNumber) : null;

			if($validatedStnOrigin != null) { $stnOrigin = $validatedStnOrigin; }

			$fatherId = null;
			if($this->animalIdsByVsmId->containsKey($fatherVsmId)) { $fatherId = intval($this->animalIdsByVsmId->get($fatherVsmId)); }
			$motherId = null;
			if($this->animalIdsByVsmId->containsKey($motherVsmId)) { $motherId = intval($this->animalIdsByVsmId->get($motherVsmId)); }

			$fatherVsmIdSql = SqlUtil::getNullCheckedValueForSqlQuery($fatherVsmId, true);
			$fatherIdSql = SqlUtil::getNullCheckedValueForSqlQuery($fatherId, true);
			$motherVsmIdSql = SqlUtil::getNullCheckedValueForSqlQuery($motherVsmId, true);
			$motherIdSql = SqlUtil::getNullCheckedValueForSqlQuery($motherId, true);

			//Unifying of parentIds is not necessary here because the Sql GROUP BY already includes the parents vsmIds

			//Update the first record with merged values. Delete duplicates and save the secondary vsmIds.
			$firstId = $ulnGroup[0]['id'];
			$sql = "UPDATE animal_migration_table SET
					animal_id = ".SqlUtil::getNullCheckedValueForSqlQuery($animalId, false).", 
					uln_origin = ".SqlUtil::getNullCheckedValueForSqlQuery($ulnOrigin, true).", 
					uln_country_code = ".SqlUtil::getNullCheckedValueForSqlQuery($ulnCountryCode, true).", 
					uln_number = ".SqlUtil::getNullCheckedValueForSqlQuery($ulnNumber, true).", 
					animal_order_number = ".SqlUtil::getNullCheckedValueForSqlQuery($animalOrderNumber, true).", 
					stn_origin = ".SqlUtil::getNullCheckedValueForSqlQuery($stnOrigin, true).", 
					pedigree_country_code = ".SqlUtil::getNullCheckedValueForSqlQuery($pedigreeCountryCode, true).", 
					pedigree_number = ".SqlUtil::getNullCheckedValueForSqlQuery($pedigreeNumber, true).", 
					father_vsm_id = ".$fatherVsmIdSql.", 
					father_id = ".$fatherIdSql.", 
					mother_vsm_id = ".$motherVsmIdSql.", 
					mother_id = ".$motherIdSql.",
					gender_in_file = ".SqlUtil::getNullCheckedValueForSqlQuery($genderInFile, true).", 
					gender_in_database = ".SqlUtil::getNullCheckedValueForSqlQuery($genderInDatabase, true).", 
					ubn_of_birth = ".SqlUtil::getNullCheckedValueForSqlQuery($ubnOfBirth, true).", 
					breed_type = ".SqlUtil::getNullCheckedValueForSqlQuery($breedType, true).", 
					breed_code = ".SqlUtil::getNullCheckedValueForSqlQuery($breedCode, true).", 
					scrapie_genotype = ".SqlUtil::getNullCheckedValueForSqlQuery($scrapieGenotype, true).", 
					location_of_birth_id = ".SqlUtil::getNullCheckedValueForSqlQuery($locationOfBirthId, false).", 
					pedigree_register_id = ".SqlUtil::getNullCheckedValueForSqlQuery($pedigreeRegisterId, false).", 
					nickname = ".SqlUtil::getNullCheckedValueForSqlQuery($nickname, true)."
				WHERE id = ".$firstId;
			$this->conn->exec($sql);

			$primaryVsmId = $ulnGroup[0]['vsm_id'];

			for($i=1; $i<count($ulnGroup); $i++) {
				$animalRecord = $ulnGroup[$i];
				$id = $animalRecord['id'];
				$vsmId = $animalRecord['vsm_id'];

				$vsmIdGroups = $this->saveVsmIdGroup($primaryVsmId, $vsmId, $vsmIdGroups);

				//Update children
				$this->updateChildrenVsmIds($primaryVsmId, $vsmId);

				//DELETE DUPLICATE RECORDS
				$sql = "DELETE FROM animal_migration_table WHERE id = ".$id;
				$this->conn->exec($sql);
			}
			$this->cmdUtil->advanceProgressBar(1);
		}
		$this->cmdUtil->setProgressBarMessage('Merged double uln records of identical Animal');
		$this->cmdUtil->setEndTimeAndPrintFinalOverview();

	}


	private function fixDuplicateStns()
	{
		$sql = "SELECT a.vsm_id, a.animal_id, a.uln_origin, a.stn_origin, a.pedigree_country_code, a.pedigree_number,
					a.id, a.uln_country_code, a.uln_number, a.animal_order_number, a.father_id, a.mother_id,
 					a.father_vsm_id, a.mother_vsm_id, a.gender_in_file, a.date_of_birth, a.ubn_of_birth,
 					a.breed_type, a.breed_code, a.is_ubn_updated, a.gender_in_database, a.location_of_birth_id, a.pedigree_register_id,
 					a.nickname, a.scrapie_genotype
				FROM animal_migration_table a
				INNER JOIN (
					SELECT stn_origin FROM animal_migration_table
					WHERE stn_origin NOTNULL AND animal_migration_table.date_of_birth NOTNULL 
					GROUP BY stn_origin, date_of_birth HAVING COUNT(*) > 1
					)g ON g.stn_origin = a.stn_origin";
		$results = $this->conn->query($sql)->fetchAll();

		if(count($results) == 0) {
			$this->output->writeln('Duplicate stns have already been processed');
			return;
		}

		//SearchArrays
		$duplicatesSearchArray = SqlUtil::createGroupedSearchArrayFromSqlResults($results, 'stn_origin');

		$sql = "SELECT id, m.mother_vsm_id, m.father_vsm_id FROM animal_migration_table m";
		$results = $this->conn->query($sql)->fetchAll();

		$vsmMothers = [];
		$vsmFathers = [];
		foreach ($results as $result) {
			$migrationTableId = $result['id'];
			$motherVsmId = $result['mother_vsm_id'];
			$fatherVsmId = $result['father_vsm_id'];
			if($motherVsmId != null && $motherVsmId != 0) { $vsmMothers[$motherVsmId] = $migrationTableId; }
			if($fatherVsmId != null && $fatherVsmId != 0) { $vsmFathers[$fatherVsmId] = $migrationTableId; }
		}

		$vsmIdGroups = $this->getVsmIdGroups();


		$this->cmdUtil->setStartTimeAndPrintIt(count($duplicatesSearchArray), 1);

		//1. Fix the stn duplicates that also have dateOfBirth and ulnOrigin in common

		$countDuplicates = 0;
		foreach ($duplicatesSearchArray as $stnOrigin => $stnOriginGroup) {

			//Merge values
			$animalId = null;
			$ulnOrigin = null;
			$validatedUlnOrigin = null;
			$stnOrigin = null;
			$validatedStnOrigin = null;
			$pedigreeCountryCode = null;
			$pedigreeNumber = null;
			$ulnCountryCode = null;
			$ulnNumber = null;
			$animalOrderNumber = null;
			$fatherVsmId = 0;
			$motherVsmId = 0;
			$genderInFile = GenderType::NEUTER;
			$genderInDatabase = GenderType::NEUTER;
			$ubnOfBirth = null;
			$breedType = null;
			$breedCode = null;
			$scrapieGenotype = null;
			$locationOfBirthId = null;
			$pedigreeRegisterId = null;
			$nickname = null;

			foreach($stnOriginGroup as $animalRecord) {

				if(Validator::verifyUlnFormat($animalRecord['uln_origin'] ,true)) {
					$validatedUlnOrigin = $animalRecord['uln_origin']; }
				if(Validator::verifyPedigreeCountryCodeAndNumberFormat($animalRecord['stn_origin'],true)) {
					$validatedStnOrigin = $animalRecord['stn_origin']; }

				if($animalRecord['animal_id'] != null) 	{ $animalId = $animalRecord['animal_id']; }
				if($animalRecord['uln_origin'] != null) { $ulnOrigin = $animalRecord['uln_origin'];	}
				if($animalRecord['stn_origin'] != null) { $stnOrigin = $animalRecord['stn_origin']; }
				if($animalRecord['father_vsm_id'] != 0) { $fatherVsmId = $animalRecord['father_vsm_id']; }
				if($animalRecord['mother_vsm_id'] != 0) { $motherVsmId = $animalRecord['mother_vsm_id']; }
				if($animalRecord['gender_in_file'] != GenderType::NEUTER) 		{ $genderInFile = $animalRecord['gender_in_file']; }
				if($animalRecord['gender_in_database'] != GenderType::NEUTER) 	{ $genderInDatabase = $animalRecord['gender_in_database']; }
				if($animalRecord['ubn_of_birth'] != null) { $ubnOfBirth = $animalRecord['ubn_of_birth']; }
				if($animalRecord['breed_type'] != null) { $breedType = $animalRecord['breed_type']; }
				if($animalRecord['breed_code'] != null) { $breedCode = $animalRecord['breed_code']; }
				if($animalRecord['scrapie_genotype'] != null) { $scrapieGenotype = $animalRecord['scrapie_genotype']; }
				if($animalRecord['location_of_birth_id'] != null) { $locationOfBirthId = $animalRecord['location_of_birth_id']; }
				if($animalRecord['pedigree_register_id'] != null) { $pedigreeRegisterId = $animalRecord['pedigree_register_id']; }
				if($animalRecord['nickname'] != null) { $nickname = $animalRecord['nickname']; }

			}

			if($validatedUlnOrigin != null) {
				$newestFormatValidatedUlnOrigin = $this->declareTagReplaceRepository->getNewReplacementUln($validatedUlnOrigin);
				if($newestFormatValidatedUlnOrigin != null) {
					$ulnOrigin = $validatedUlnOrigin;
				}
			}

			$ulnParts = AnimalTableImporter::parseUln($ulnOrigin);
			$ulnCountryCode = $ulnParts[JsonInputConstant::ULN_COUNTRY_CODE];
			$ulnNumber = $ulnParts[JsonInputConstant::ULN_NUMBER];
			$animalOrderNumber = $ulnNumber != null ? StringUtil::getLast5CharactersFromString($ulnNumber) : null;

			if($validatedStnOrigin != null) { $stnOrigin = $validatedStnOrigin;	}

			$stnParts = AnimalTableImporter::parseStn($stnOrigin);
			$pedigreeCountryCode = $stnParts[JsonInputConstant::PEDIGREE_COUNTRY_CODE];
			$pedigreeNumber = $stnParts[JsonInputConstant::PEDIGREE_NUMBER];

			$fatherId = null;
			if($this->animalIdsByVsmId->containsKey($fatherVsmId)) { $fatherId = intval($this->animalIdsByVsmId->get($fatherVsmId)); }
			$motherId = null;
			if($this->animalIdsByVsmId->containsKey($motherVsmId)) { $motherId = intval($this->animalIdsByVsmId->get($motherVsmId)); }

			$fatherVsmIdSql = SqlUtil::getNullCheckedValueForSqlQuery($fatherVsmId, true);
			$fatherIdSql = SqlUtil::getNullCheckedValueForSqlQuery($fatherId, true);
			$motherVsmIdSql = SqlUtil::getNullCheckedValueForSqlQuery($motherVsmId, true);
			$motherIdSql = SqlUtil::getNullCheckedValueForSqlQuery($motherId, true);

			//Unify father and mother ids
			foreach($stnOriginGroup as $animalRecord) {
				$id = $animalRecord['id'];
				if($animalRecord['father_vsm_id'] != $fatherVsmId) {
					$sql = "UPDATE animal_migration_table SET deleted_father_vsm_id = father_vsm_id, father_vsm_id = ".$fatherVsmIdSql.", father_id = ".$fatherIdSql." WHERE id = ".$id;
					$this->conn->exec($sql);
				}
				if($animalRecord['mother_vsm_id'] != $motherVsmId) {
					$sql = "UPDATE animal_migration_table SET deleted_mother_vsm_id = mother_vsm_id, mother_vsm_id = ".$motherVsmIdSql.", mother_id = ".$motherIdSql." WHERE id = ".$id;
					$this->conn->exec($sql);
				}
			}


			//Update the first record with merged values. Delete duplicates and save the secondary vsmIds.
			$firstId = $stnOriginGroup[0]['id'];
			$sql = "UPDATE animal_migration_table SET
 						animal_id = ".SqlUtil::getNullCheckedValueForSqlQuery($animalId, false).", 
 						uln_origin = ".SqlUtil::getNullCheckedValueForSqlQuery($ulnOrigin, true).", 
 						uln_country_code = ".SqlUtil::getNullCheckedValueForSqlQuery($ulnCountryCode, true).", 
 						uln_number = ".SqlUtil::getNullCheckedValueForSqlQuery($ulnNumber, true).", 
 						animal_order_number = ".SqlUtil::getNullCheckedValueForSqlQuery($animalOrderNumber, true).", 
 						stn_origin = ".SqlUtil::getNullCheckedValueForSqlQuery($stnOrigin, true).", 
 						pedigree_country_code = ".SqlUtil::getNullCheckedValueForSqlQuery($pedigreeCountryCode, true).", 
 						pedigree_number = ".SqlUtil::getNullCheckedValueForSqlQuery($pedigreeNumber, true).", 
 						father_vsm_id = ".$fatherVsmIdSql.", 
 						father_id = ".$fatherIdSql.", 
 						mother_vsm_id = ".$motherVsmIdSql.", 
 						mother_id = ".$motherIdSql.",
 						gender_in_file = ".SqlUtil::getNullCheckedValueForSqlQuery($genderInFile, true).", 
 						gender_in_database = ".SqlUtil::getNullCheckedValueForSqlQuery($genderInDatabase, true).", 
 						ubn_of_birth = ".SqlUtil::getNullCheckedValueForSqlQuery($ubnOfBirth, true).", 
 						breed_type = ".SqlUtil::getNullCheckedValueForSqlQuery($breedType, true).", 
 						breed_code = ".SqlUtil::getNullCheckedValueForSqlQuery($breedCode, true).", 
 						scrapie_genotype = ".SqlUtil::getNullCheckedValueForSqlQuery($scrapieGenotype, true).", 
 						location_of_birth_id = ".SqlUtil::getNullCheckedValueForSqlQuery($locationOfBirthId, false).", 
 						pedigree_register_id = ".SqlUtil::getNullCheckedValueForSqlQuery($pedigreeRegisterId, false).", 
 						nickname = ".SqlUtil::getNullCheckedValueForSqlQuery($nickname, true)."
 					WHERE id = ".$firstId;
			$this->conn->exec($sql);

			$primaryVsmId = $stnOriginGroup[0]['vsm_id'];

			for($i=1; $i<count($stnOriginGroup); $i++) {
				$animalRecord = $stnOriginGroup[$i];
				$id = $animalRecord['id'];
				$vsmId = $animalRecord['vsm_id'];

				$vsmIdGroups = $this->saveVsmIdGroup($primaryVsmId, $vsmId, $vsmIdGroups);

				//Update children
				$this->updateChildrenVsmIds($primaryVsmId, $vsmId);

				//DELETE DUPLICATE RECORDS
				$sql = "DELETE FROM animal_migration_table WHERE id = ".$id;
				$this->conn->exec($sql);
			}
			$countDuplicates++;
			$this->cmdUtil->advanceProgressBar(1);
		}
		$this->cmdUtil->setProgressBarMessage('Duplicate stns are processed!');
		$this->cmdUtil->setEndTimeAndPrintFinalOverview();
	}


	/**
	 * @param array $usedPedigreeNumbers
	 * @param array $breederNumberByUbnsOfBirth
	 * @return array
	 * @throws \Doctrine\DBAL\DBALException
	 */
	private function deleteIncorrectUlnsAndStns($usedPedigreeNumbers, $breederNumberByUbnsOfBirth)
	{
		//Delete stns in uln_origin
		$sql = "SELECT COUNT(*) FROM animal_migration_table
				WHERE uln_origin = stn_origin AND pedigree_number NOTNULL";
		$count = $this->conn->query($sql)->fetch()['count'];
		if($count == 0) {
			$this->output->writeln('All stns in uln_origin have been deleted');
		} else {
			$sql = "UPDATE animal_migration_table SET deleted_uln_origin = uln_origin, uln_origin = NULL
				    WHERE uln_origin = stn_origin AND pedigree_number NOTNULL";
			$this->conn->exec($sql);
			$this->output->writeln($count.' stns in uln_origin deleted');
		}

		//Delete ulns in stn_origin. The logic is based on Reinard's instructions in point 8
		$sql = "SELECT COUNT(*) FROM animal_migration_table
				WHERE uln_origin = stn_origin AND uln_number NOTNULL";
		$count = $this->conn->query($sql)->fetch()['count'];
		if($count == 0) {
			$this->output->writeln('All ulns in stn_origin have been deleted/updated');
		} else {
			$sql = "SELECT COUNT(*) FROM animal_migration_table 
					WHERE uln_origin = stn_origin AND uln_number NOTNULL
				  	AND ((pedigree_register_id ISNULL OR pedigree_register_id = 15 OR pedigree_register_id = 16)
				  	OR animal_migration_table.ubn_of_birth ISNULL)";
			$count = $this->conn->query($sql)->fetch()['count'];
			
			$sql = "UPDATE animal_migration_table SET deleted_stn_origin = stn_origin, stn_origin = NULL 
					WHERE uln_origin = stn_origin AND uln_number NOTNULL
					  AND ((pedigree_register_id ISNULL OR pedigree_register_id = 15 OR pedigree_register_id = 16) 
					  OR animal_migration_table.ubn_of_birth ISNULL)";
			$this->conn->exec($sql);
			$this->output->writeln($count.'non pedigree ulns and those missing ubnOfBirth in stn_origin deleted');

			/*
			 * Note this logic may seem similar to the one in fixMissingUlnsByPedigreeNumberOnly,
			 * but this one is regardless of dateOfBirth
			 */
			$sql = "SELECT id, ubn_of_birth, uln_origin, stn_origin, uln_number, uln_country_code, animal_order_number
					FROM animal_migration_table
					WHERE uln_origin = stn_origin AND uln_number NOTNULL
					  AND pedigree_register_id NOTNULL AND pedigree_register_id <> 15 AND pedigree_register_id <> 16
					  AND ubn_of_birth NOTNULL";
			$results = $this->conn->query($sql)->fetchAll();
			$count = count($results);

			$animalOrderNumbersUpdated = 0;
			$stnsUpdated = 0;
			$ulnsInStnOriginDeleted = 0;
			foreach($results as $result) {
				$id = $result['id'];
				$ubnOfBirth = $result['ubn_of_birth'];
				$countryCode = $result['uln_country_code'];
				$ulnNumber = $result['uln_number'];
				$animalOrderNumberInDb = $result['animal_order_number'];

				$animalOrderNumber = StringUtil::getLast5CharactersFromString($ulnNumber);

				$deleteUlnInStn = true;
				if($animalOrderNumber != null) {
					if(array_key_exists($ubnOfBirth, $breederNumberByUbnsOfBirth)) {
						$breederNumber = $breederNumberByUbnsOfBirth[$ubnOfBirth];
						$pedigreeNumber = StringUtil::padUlnNumberWithZeroes($breederNumber.'-'.$animalOrderNumber);

						if(!array_key_exists($pedigreeNumber, $usedPedigreeNumbers)) {
							$sql = "UPDATE animal_migration_table SET is_stn_updated = TRUE, pedigree_country_code = '".$countryCode."', pedigree_number = '".$pedigreeNumber."', deleted_stn_origin = stn_origin, stn_origin = NULL WHERE id = ".$id;
							$this->conn->exec($sql);
							$stnsUpdated++;
							$usedPedigreeNumbers[$pedigreeNumber] = $pedigreeNumber;
							$deleteUlnInStn = false;
						}
					}

					if($animalOrderNumberInDb != $animalOrderNumber) {
						$sql = "UPDATE animal_migration_table SET is_animal_order_number_updated = TRUE
 								, animal_order_number = '".$animalOrderNumber."' WHERE id = ".$id;
						$this->conn->exec($sql);
						$animalOrderNumbersUpdated++;
					}
				}

				if($deleteUlnInStn) {
					$sql = "UPDATE animal_migration_table SET deleted_stn_origin = stn_origin, stn_origin = NULL WHERE id = ".$id;
					$this->conn->exec($sql);
					$ulnsInStnOriginDeleted++;
				}
			}

			$this->output->writeln('StnsUpdated|DuplicateUlnInStnOriginDeleted|aOrderNrsUpdated: '.$stnsUpdated.'|'.$ulnsInStnOriginDeleted.'|'.$animalOrderNumbersUpdated);
		}
		
		return $usedPedigreeNumbers;
	}
	
	
	/**
	 * @param array $usedUlnNumbers
	 * @return array
	 * @throws \Doctrine\DBAL\DBALException
	 */
	private function fixMissingUlnsByPedigreeNumberAndUbnOfBirth($usedUlnNumbers)
	{
		$countryCode = 'NL';

		//Animals with valid PedigreeNumber but missing uln and ubnOfBirth
		$sql = "SELECT id, pedigree_number, ubn_of_birth, animal_order_number FROM animal_migration_table t
				WHERE SUBSTR(stn_origin,0,3) = 'NL' AND t.ubn_of_birth NOTNULL
					  AND t.uln_country_code ISNULL AND date_of_birth < '2010-01-01'
					  AND t.pedigree_number NOTNULL AND is_uln_updated = FALSE";
		$results = $this->conn->query($sql)->fetchAll();

		$count = count($results);
		if($count == 0) { $this->output->writeln('All blank ulns with ubnOfBirth have already been processed'); return $usedUlnNumbers; }
		$this->cmdUtil->setStartTimeAndPrintIt($count+1, 1);

		$ulnsUpdated = 0;
		$animalOrderNumbersUpdated = 0;
		$occupiedUlns = 0;
		$incorrectOrderNumbers = 0;
		foreach ($results as $result) {
			$id = $result['id'];
			$pedigreeNumber = $result['pedigree_number'];
			$animalOrderNumberInDb = $result['animal_order_number'];
			$isAnimalOrderNumberInDb = $animalOrderNumberInDb != null && $animalOrderNumberInDb != '' && ctype_digit($animalOrderNumberInDb);
			$ubnOfBirth = $result['ubn_of_birth'];

			$animalOrderNumberFromPedigreeNumber = StringUtil::getAnimalOrderNumberFromPedigreeNumber($pedigreeNumber);
			$animalOrderNumber = $isAnimalOrderNumberInDb ? $animalOrderNumberInDb : $animalOrderNumberFromPedigreeNumber;

			if($animalOrderNumber != null) {
				if(!$isAnimalOrderNumberInDb || $isAnimalOrderNumberInDb != $animalOrderNumber) {
					$sql = "UPDATE animal_migration_table SET is_animal_order_number_updated = TRUE
 								, animal_order_number = '".$animalOrderNumber."' WHERE id = ".$id;
					$this->conn->exec($sql);
					$animalOrderNumbersUpdated++;
				}

				$ulnNumber = StringUtil::padUlnNumberWithZeroes($ubnOfBirth.$animalOrderNumber);

				if(!array_key_exists($ulnNumber, $usedUlnNumbers)) {
					$sql = "UPDATE animal_migration_table SET is_uln_updated = TRUE, uln_country_code = '".$countryCode."', uln_number = '".$ulnNumber."' WHERE id = ".$id;
					$this->conn->exec($sql);
					$ulnsUpdated++;
					$usedUlnNumbers[$ulnNumber] = $ulnNumber;
				} else { $occupiedUlns++; }
			} else { $incorrectOrderNumbers++; }
			
			$this->cmdUtil->advanceProgressBar(1,'updated ulns|animalOrderNumbers: '.$ulnsUpdated.'|'.$animalOrderNumbersUpdated.' | ulns occupied|incorrect aOrderNrs: '.$occupiedUlns.'|'.$incorrectOrderNumbers);
		}
		$this->cmdUtil->setEndTimeAndPrintFinalOverview();
		return $usedUlnNumbers;
	}
	
	
	/**
	 * @param array $ubnsOfBirthByBreederNumber
	 * @param array $usedUlnNumbers
	 * @return array
	 * @throws \Doctrine\DBAL\DBALException
	 */
	private function fixMissingUlnsByPedigreeNumberOnly($ubnsOfBirthByBreederNumber, $usedUlnNumbers)
	{
		$countryCode = 'NL';

		//Animals with valid PedigreeNumber but missing uln and ubnOfBirth
		$sql = "SELECT id, pedigree_number, animal_order_number FROM animal_migration_table t
				WHERE SUBSTR(stn_origin,0,3) = '".$countryCode."' AND t.ubn_of_birth ISNULL
					  AND t.uln_country_code ISNULL AND date_of_birth < '2010-01-01'
					  AND t.pedigree_country_code NOTNULL AND is_uln_updated = FALSE";
		$results = $this->conn->query($sql)->fetchAll();

		$count = count($results);
		if($count == 0) { $this->output->writeln('All blank ulns without ubnOfBirth have already been processed'); return $usedUlnNumbers; }
		$this->cmdUtil->setStartTimeAndPrintIt($count+1, 1);

		$ulnsUpdated = 0;
		$animalOrderNumbersUpdated = 0;
		$ubnsOfBirthUpdated = 0;
		$occupiedUlns = 0;
		$missingBreederNumbers = 0;
		$incorrectOrderNumbers = 0;
		foreach ($results as $result) {
			$id = $result['id'];
			$pedigreeNumber = $result['pedigree_number'];
			$animalOrderNumberInDb = $result['animal_order_number'];
			$breederNumber = StringUtil::getBreederNumberFromPedigreeNumber($pedigreeNumber);

			if(array_key_exists($breederNumber, $ubnsOfBirthByBreederNumber)) {
				$ubnOfBirth = $ubnsOfBirthByBreederNumber[$breederNumber];
				$animalOrderNumber = StringUtil::getAnimalOrderNumberFromPedigreeNumber($pedigreeNumber);

				$sql = "UPDATE animal_migration_table SET is_ubn_updated = TRUE, ubn_of_birth = '".$ubnOfBirth."' WHERE id = ".$id;
				$this->conn->exec($sql);
				$ubnsOfBirthUpdated++;

				if($animalOrderNumber != null) {
					$ulnNumber = StringUtil::padUlnNumberWithZeroes($ubnOfBirth.$animalOrderNumber);

					if(!array_key_exists($ulnNumber, $usedUlnNumbers)) {
						$sql = "UPDATE animal_migration_table SET is_uln_updated = TRUE, uln_country_code = '".$countryCode."', uln_number = '".$ulnNumber."' WHERE id = ".$id;
						$this->conn->exec($sql);
						$ulnsUpdated++;
						$usedUlnNumbers[$ulnNumber] = $ulnNumber;
					} else {
						$occupiedUlns++;
					}

					if($animalOrderNumberInDb == null || $animalOrderNumberInDb == '') {
						$sql = "UPDATE animal_migration_table SET is_animal_order_number_updated = TRUE
 								, animal_order_number = '".$animalOrderNumber."' WHERE id = ".$id;
						$this->conn->exec($sql);
						$animalOrderNumbersUpdated++;
					}
				} else { $incorrectOrderNumbers++; }
			} else { $missingBreederNumbers++; }
			$this->cmdUtil->advanceProgressBar(1,'updated ubnsOfBirth|ulns|animalOrderNumbers: '.$ubnsOfBirthUpdated.'|'.$ulnsUpdated.'|'.$animalOrderNumbersUpdated.' | ulns occupied|incorrect aOrderNrs|missingBreederNrs: '.$occupiedUlns.'|'.$incorrectOrderNumbers.'|'.$missingBreederNumbers);
		}
		$this->cmdUtil->setEndTimeAndPrintFinalOverview();
		return $usedUlnNumbers;
	}
	

	/**
	 * @param int $vsmId
	 * @param array $animalIdByVsmId
	 * @throws \Doctrine\DBAL\DBALException
	 * @return string
	 */
	private function getChildrenCountByParentTypeAsString($vsmId, $animalIdByVsmId)
	{
		$sql = "SELECT COUNT(*), 'father' as type FROM animal_migration_table WHERE father_vsm_id = ".$vsmId."
				UNION
				SELECT COUNT(*), 'mother' as type FROM animal_migration_table WHERE mother_vsm_id = ".$vsmId;
		$results = $this->conn->query($sql)->fetchAll();

		$message = $this->parseChildrenCountArray($results);

		if(array_key_exists($vsmId, $animalIdByVsmId)) {
			$animalId = $animalIdByVsmId[$vsmId];
			$sql = "SELECT COUNT(*), 'father' as type FROM animal WHERE parent_father_id = ".$animalId."
					UNION
					SELECT COUNT(*), 'mother' as type FROM animal WHERE parent_mother_id = ".$animalId;
			$results = $this->conn->query($sql)->fetchAll();
			$message = $this->parseChildrenCountArray($results, $message);
		} else {
			$message = $message.'0;0;';
		}
		
		$sql = "SELECT CONCAT(uln_country_code,' ',uln_number) as uln, CONCAT(pedigree_country_code,' ',pedigree_number) as stn, vsm_id FROM animal_migration_table
				WHERE vsm_id = ".$vsmId;
		$result = $this->conn->query($sql)->fetch();
		$idString = $result['uln'].';'.$result['stn'].';';

		return $message.$idString;
	}


	/**
	 * @param string $prefix
	 * @param array $results
	 * @return string
	 */
	private function parseChildrenCountArray(array $results, $prefix = '')
	{
		$fatherCount = 0;
		$motherCount = 0;
		foreach ($results as $result) {
			if($result['type'] == 'father') { $fatherCount = $result['count']; }
			elseif($result['type'] == 'mother') { $motherCount = $result['count']; }
		}

		return $prefix.$motherCount.';'.$fatherCount.';';
	}


	public function verifyData()
	{
		$neutersByVsmId = $this->getNeutersByVsmId();

		$vsmIdCollection = [];
		$stnImportCollection = [];
		$animalOrderNumberImportCollection = [];
		$ulnImportCollection = [];
		$nicknameCollection = [];
		$vsmIdFatherCollection = [];
		$vsmIdMotherCollection = [];
		$genderCollection = [];
		$dateOfBirthStringCollection = [];
		$breedCodeCollection = [];
		$ubnOfBirthCollection = []; //ubnOfBreeder
		$pedigreeRegisterCollection = [];
		$breedTypeCollection = [];
		$scrapieGenotypeCollection = [];

		$newCount = 0;
		foreach ($this->data as $record) {

			$vsmId = $record[0];
			$stnImport = $record[1];
			$animalOrderNumberImport = $record[2];
			$ulnImport = $record[3];
			$nickname = $record[4];
			$vsmIdFather = $record[5];
			$vsmIdMother = $record[6];
			$gender = $record[7];
			$dateOfBirthString = $record[8];
			$breedCode = $record[9];
			$ubnOfBirth = $record[10]; //ubnOfBreeder
			$pedigreeRegister = trim($record[11]);
			$breedType = $record[12];
			$scrapieGenotype = $record[13];

			$vsmIdCollection[] = $vsmId;
			$stnImportCollection[] = $stnImport;
			$animalOrderNumberImportCollection[] = $animalOrderNumberImport;
			$ulnImportCollection[] = $ulnImport;
			$nicknameCollection[] = $nickname;
			$vsmIdFatherCollection[] = $vsmIdFather;
			$vsmIdMotherCollection[] = $vsmIdMother;
			$genderCollection[$gender] = $gender;
			$dateOfBirthStringCollection[] = $dateOfBirthString;
			$breedCodeCollection[] = $breedCode;
			$ubnOfBirthCollection[] = $ubnOfBirth; //ubnOfBreeder
			$pedigreeRegisterCollection[$pedigreeRegister] = $pedigreeRegister;
			$breedTypeCollection[$breedType] = $breedType;
			$scrapieGenotypeCollection[$scrapieGenotype] = $scrapieGenotype;
		}


		$sql = "SELECT DISTINCT(ubn) as ubn FROM location";
		$results = $this->conn->query($sql)->fetchAll();
		$ubnsOfLocationsInDatabase = [];
		foreach ($results as $result) {
			$ubn = $result['ubn'];
			$ubnsOfLocationsInDatabase[$ubn] = $ubn;
		}

		$vsmIdsMissing = 0;
		$invalidVsmId = [];
		$neuterVsmIdsCount = 0;
		foreach ($vsmIdCollection as $vsmId) {
			//Check if vsmId only contains digits.
			if($vsmId == null || $vsmId == '') { $vsmIdsMissing++; }
			else if(!ctype_digit($vsmId)) { $invalidVsmId[] = $vsmId; }
			else if(array_key_exists($vsmId, $neutersByVsmId)) { $neuterVsmIdsCount++; }
		}
		$this->output->writeln('vsmIds missing: '.$vsmIdsMissing.' | '.'invalidVsmIds: '.count($invalidVsmId).' | VsmIds without gender: '.$neuterVsmIdsCount);

		$stnMissing = 0;
		$invalidStn = [];
		foreach ($stnImportCollection as $stn) {
			if($stn == null || $stn == '') { $stnMissing++; }
			else if(!Validator::verifyPedigreeCountryCodeAndNumberFormat($stn, true)) { $invalidStn[] = $stn; }
		}
		$this->output->writeln('stns missing: '.$stnMissing.' | '.'invalidStns: '.count($invalidStn));

//        foreach ($animalOrderNumberImportCollection as $animalOrderNumber) {
//
//        }

		$ulnsMissing = 0;
		$invalidUlns = [];
		foreach ($ulnImportCollection as $uln) {
			if($uln == null || $uln == '') { $ulnsMissing++; }
			else if(!Validator::verifyUlnFormat($uln, true)) { $invalidUlns[] = $uln; }
		}
		$this->output->writeln('ulns missing: '.$ulnsMissing.' | '.'invalidUlns: '.count($invalidUlns));


		if(self::PRINT_OUT_INVALID_UBNS_OF_BIRTH) {
			$invalidUlnCount = 0;
			foreach ($invalidUlns as $uln) {
				file_put_contents($this->outputFolder.'/'.self::FILENAME_INCORRECT_ULNS,
					$uln
					."\n", FILE_APPEND);
			}
		}

		$nicknames = [];
		foreach ($nicknameCollection as $nickname) {
			if($nickname != null && $nickname != '') { $nicknames[] = $nickname; }
		}
		$this->output->writeln('nickNames: '.count($nicknames));

		$vsmIdFathersIncorrect = [];
		foreach ($vsmIdFatherCollection as $vsmIdFather) {
			if($vsmIdFather != null && $vsmIdFather != '') {
				if(!ctype_digit($vsmIdFather)) { $vsmIdFathersIncorrect[] = $vsmIdFather; }
			}
		}
		$this->output->writeln('Incorrect FatherIds: '.count($vsmIdFathersIncorrect));

		$vsmIdMothersIncorrect = [];
		foreach ($vsmIdMotherCollection as $vsmIdMother) {
			if($vsmIdMother != null && $vsmIdMother != '') {
				if(!ctype_digit($vsmIdMother)) { $vsmIdMothersIncorrect[] = $vsmIdMother; }
			}
		}
		$this->output->writeln('Incorrect MotherIds: '.count($vsmIdMothersIncorrect));

		$genders = '';
		foreach ($genderCollection as $gender) {
			$genders = $genders.'|'.$gender;
		}
		$this->output->writeln('Genders: '.$genders);

		$dateOfBirthMissing = 0;
		$incorrectDateOfBirths = [];
		foreach ($dateOfBirthStringCollection as $dateOfBirthString) {
			if($dateOfBirthString == null || $dateOfBirthString == '') { $dateOfBirthMissing++; }
			else if(!TimeUtil::isFormatYYYYMMDD($dateOfBirthString)) {  }
		}
		$this->output->writeln('dateOfBirths missing: '.$dateOfBirthMissing.' | '.'invalid dateOfBirths: '.count($incorrectDateOfBirths));

//        foreach ($breedCodeCollection as $breedCode) {
//
//        }

		$ubnsOfBirthMissing = 0;
		$invalidUbnOfBirths = [];
		$ubnsOfBirthNotInDatabase = [];
		foreach ($ubnOfBirthCollection as $ubnOfBirth) {
			if($ubnOfBirth == null || $ubnOfBirth == '') { $ubnsOfBirthMissing++; }
			else if(!ctype_digit($ubnOfBirth)) { $invalidUbnOfBirths[] = $ubnOfBirth; }
			else if(array_key_exists($ubnOfBirth, $ubnsOfLocationsInDatabase)) { $ubnsOfBirthNotInDatabase[] = $ubnOfBirth; }
		}
		$this->output->writeln('ubnsOfBirthMissing missing: '.$ubnsOfBirthMissing.' | '.'invalid ubnsOfBirth: '.count($invalidUbnOfBirths).' | ubnOfBirths not in Database: '.count($ubnsOfBirthNotInDatabase));


		$pedigreeRegisters = '';
		foreach ($pedigreeRegisterCollection as $pedigreeRegister) {
			$pedigreeRegisters = $pedigreeRegisters.'|'.$pedigreeRegister;
		}
		$this->output->writeln('PedigreeRegisters: '.$pedigreeRegisters);

		$breedTypes = '';
		foreach ($breedTypeCollection as $breedType) {
			$breedTypes = $breedTypes.'|'.$breedType;
		}
		$this->output->writeln('BreedTypes: '.$breedTypes);

		$scrapieGenotypes = '';
		foreach ($scrapieGenotypeCollection as $scrapieGenotype) {
			$scrapieGenotypes = $scrapieGenotypes.'|'.$scrapieGenotype;
		}
		$this->output->writeln('ScrapieGenotypes: '.$scrapieGenotypes);
	}


	/**
	 * @param string $pedigreeRegister
	 * @return array
	 */
	public static function parsePedigreeRegister($pedigreeRegister)
	{
		$pedigreeRegisterFullName = null;
		$pedigreeRegisterAbbreviation = null;

		switch ($pedigreeRegister) {
			case self::PR_UNKNOWN: break;
			case ' ':              break;
			case '';               break;

			case self::PR_EN_BASIS:
				$pedigreeRegisterFullName = Translation::getEnglish(trim(strtoupper($pedigreeRegister)));
				$pedigreeRegisterAbbreviation = null;
				break;

			case self::PR_EN_MANAGEMENT:
				$pedigreeRegisterFullName = Translation::getEnglish(trim(strtoupper($pedigreeRegister)));
				$pedigreeRegisterAbbreviation = null;
				break;

			default:
				$parts = explode(' : ', $pedigreeRegister);
				if(count($parts) == 2) {
					$pedigreeRegisterAbbreviation = $parts[0];
					$pedigreeRegisterFullName = Translation::getEnglish(trim(strtoupper($parts[1])));
				}
				break;
		}

		return [
		  self::VALUE => $pedigreeRegisterFullName,
		  self::ABBREVIATION => $pedigreeRegisterAbbreviation,
		];
	}


	/**
	 * These values are based on the pedigreeRegister data in the import file
	 * compared to the values in the database on 2016
	 */
	public function updatePedigreeRegister()
	{
		$clunForestNewFullName = 'Clun Forest Schapenvereniging';
		$clunForestAbbr = 'CF';
		$nfsFullName = 'Nederlands Flevolander Schapenstamboek';
		$nfsAbbr = 'NFS';
		$tsnhFullName = 'Texels Schapenstamboek in Noord Holland';
		$tsnhAbbr = 'TSNH';
		$enManagementFullName = 'EN-Management';
		$enManagementAbbr = 'ENM';
		$enBasisFullName = 'EN-Basis';
		$enBasisAbbr = 'ENB';

		$sql = "SELECT * FROM pedigree_register";
		$results = $this->conn->query($sql)->fetchAll();

		$allSpeciesAreSheep = true;
		$nfsExists = false;
		$tsnhExists = false;
		$enManagementExists = false;
		$enBasisExists = false;
		foreach ($results as $result) {
			$id = $result['id'];
			$abbreviation = $result['abbreviation'];
			$fullName = $result['full_name'];
			$specie = $result['specie'];

			//Update full_name
			if($abbreviation == $clunForestAbbr && $fullName != $clunForestNewFullName) {
				$sql = "UPDATE pedigree_register SET full_name = '".$clunForestNewFullName."' WHERE id = ".$id;
				$this->conn->exec($sql);
			}

			if($specie != Specie::SHEEP) { $allSpeciesAreSheep = false; }
			if($abbreviation == $nfsAbbr) { $nfsExists = true; }
			if($abbreviation == $tsnhAbbr) { $tsnhExists = true; }
			if($fullName == self::PR_EN_MANAGEMENT) { $enManagementExists = true; }
			if($fullName == self::PR_EN_BASIS) { $enBasisExists = true; }
		}

		if(!$allSpeciesAreSheep) {
			$sql = "UPDATE pedigree_register SET specie = '".Specie::SHEEP."'";
			$this->conn->exec($sql);
		}

		if(!$nfsExists) {
			$nfs = new PedigreeRegister();
			$nfs->setAbbreviation($nfsAbbr);
			$nfs->setFullName($nfsFullName);
			$nfs->setSpecie(Specie::SHEEP);
			$nfs->setCreationDate(new \DateTime());
			$this->em->persist($nfs);
		}

		if(!$tsnhExists) {
			$tsnh = new PedigreeRegister();
			$tsnh->setAbbreviation($tsnhAbbr);
			$tsnh->setFullName($tsnhFullName);
			$tsnh->setSpecie(Specie::SHEEP);
			$tsnh->setCreationDate(new \DateTime());
			$this->em->persist($tsnh);
		}

		if(!$enManagementExists) {
			$enManagement = new PedigreeRegister();
			$enManagement->setAbbreviation($enManagementAbbr);
			$enManagement->setFullName($enManagementFullName);
			$enManagement->setSpecie(Specie::SHEEP);
			$enManagement->setCreationDate(new \DateTime());
			$this->em->persist($enManagement);
		}

		if(!$enBasisExists) {
			$enBasis = new PedigreeRegister();
			$enBasis->setAbbreviation($enBasisAbbr);
			$enBasis->setFullName($enBasisFullName);
			$enBasis->setSpecie(Specie::SHEEP);
			$enBasis->setCreationDate(new \DateTime());
			$this->em->persist($enBasis);
		}

		$this->em->flush();
	}


	/**
	 * @return array
	 */
	private function getNeutersByVsmId()
	{
		$sql = "SELECT id, name, gender FROM animal WHERE gender <> 'FEMALE' AND gender <> 'MALE'";
		$results = $this->conn->query($sql)->fetchAll();
		$neutersByVsmId = [];
		foreach ($results as $result) {
			$neutersByVsmId[$result['name']] = $result['name'];
		}
		return $neutersByVsmId;
	}


	public function fixGendersInDatabase()
	{
		$neutersByVsmId = $this->getNeutersByVsmId();
		$animalIdByVsmIds = $this->animalRepository->getAnimalPrimaryKeysByVsmId();

		$animalIdsOfNeutersToFemale = [];
		$animalIdsOfNeutersToMale = [];


		foreach ($this->data as $record) {

			$vsmId = $record[0];
			$gender = AnimalTableImporter::parseGender($record[7]);

			if (array_key_exists($vsmId, $neutersByVsmId)) {
				//Neuter with this vsmId currently exists in the database
				$animalId = $animalIdByVsmIds[$vsmId];
				if (is_int($animalId)) {
					if($gender == GenderType::MALE) { $animalIdsOfNeutersToMale[$animalId] = $animalId; }
					else if($gender == GenderType::FEMALE) { $animalIdsOfNeutersToFemale[$animalId] = $animalId; }
				}
			}

			$vsmIdFather = $record[5];
			if (array_key_exists($vsmIdFather, $neutersByVsmId)) {
				$animalId = $animalIdByVsmIds[$vsmIdFather];
				if (is_int($animalId)) {
					$animalIdsOfNeutersToMale[$animalId] = $animalId;
				}
			}

			$vsmIdMother = $record[6];
			if (array_key_exists($vsmIdMother, $neutersByVsmId)) {
				$animalId = $animalIdByVsmIds[$vsmIdMother];
				if (is_int($animalId)) {
					$animalIdsOfNeutersToFemale[$animalId] = $animalId;
				}
			}
		}

		$count = 0;
		$totalCount = count($animalIdsOfNeutersToFemale) + count($animalIdsOfNeutersToMale);

		if($totalCount > 0) {
			$this->cmdUtil->setStartTimeAndPrintIt($totalCount + 1, 1, 'Fixing gender of neuters...');

			$animalIdsFemale = array_keys($animalIdsOfNeutersToFemale);
			foreach ($animalIdsFemale as $animalId) {
				GenderChangerForMigrationOnly::changeNeuterToFemaleBySql($this->em, $animalId);
				$this->cmdUtil->advanceProgressBar(1, 'id|gender : ' . $animalId . '|' . GenderType::FEMALE);
				$count++;
			}

			$animalIdsMale = array_keys($animalIdsOfNeutersToMale);
			foreach ($animalIdsMale as $animalId) {
				GenderChangerForMigrationOnly::changeNeuterToMaleBySql($this->em, $animalId);
				$this->cmdUtil->advanceProgressBar(1, 'id|gender : ' . $animalId . '|' . GenderType::MALE);
				$count++;
			}

			$this->cmdUtil->setProgressBarMessage('Genders Fixed: ' . $count);
			$this->cmdUtil->setEndTimeAndPrintFinalOverview();
		} else {
			$this->output->writeln('No genders to fix');
		}

	}


	private function writeColumnHeadersOfCsv()
	{
		$columnHeaders = 'vsmId;animalId;ulnOrigin;stnOrigin;ulnCountryCode;ulnNumber;animalOrderNumber;pedigreeCountryCode;pedigreeNumber;nickName;'.
			'fatherVsmId;fatherId;motherVsmId;motherId;genderInFile;genderInDatabase;dateOfBirth;breedCode;ubnOfBirth;locationOfBirth;'.
			'pedigreeRegisterId;BreedType;scrapieGenotype;'
		;
		$this->writeCorrectedCsvRecord($columnHeaders);
	}

	/**
	 * @throws \Doctrine\DBAL\DBALException
	 */
	private function findMissingFathers()
	{
		//SearchArrays
		$sql = "SELECT CONCAT(mother_vsm_id,'--',DATE(date_of_birth)) as key, father_vsm_id
				FROM animal_migration_table t
				WHERE mother_vsm_id <> 0 AND father_vsm_id <> 0
				GROUP BY mother_vsm_id, DATE(date_of_birth), father_vsm_id";
		$results = $this->conn->query($sql)->fetchAll();

		$fatherVsmIdsByMotherVsmIdAndDateOfBirth = [];
		foreach ($results as $result) {
			$fatherVsmIdsByMotherVsmIdAndDateOfBirth[$result['key']] = $result['father_vsm_id'];
		}

		$animalIdByVsmId = $this->animalRepository->getAnimalPrimaryKeysByVsmIdArray();

		//Animals without father, but with mother
		$sql = "SELECT CONCAT(mother_vsm_id,'--',DATE(date_of_birth)) as key, id
				FROM animal_migration_table t
				WHERE mother_vsm_id <> 0 AND father_vsm_id = 0 AND is_father_updated = FALSE";
		$results = $this->conn->query($sql)->fetchAll();

		$count = count($results);
		if($count == 0) {
			$this->output->writeln('All possible missing fathers have already been found and set');
			return;
		}
		$this->cmdUtil->setStartTimeAndPrintIt($count, 1);
		
		$newFatherVsmIdsSet = 0;
		$newFatherIdsSet = 0;
		$fathersMissing = 0;
		foreach ($results as $result) {
			$searchKey = $result['key'];
			$migrationTableId = $result['id'];

			if(array_key_exists($searchKey, $fatherVsmIdsByMotherVsmIdAndDateOfBirth)) {
				$fatherVsmId = $fatherVsmIdsByMotherVsmIdAndDateOfBirth[$searchKey];

				$setFatherId = '';
				if(array_key_exists($fatherVsmId, $animalIdByVsmId)) {
					$fatherId = $animalIdByVsmId[$fatherVsmId];
					$setFatherId = ", father_id = ".$fatherId;
					$newFatherIdsSet++;
				}

				$sql = "UPDATE animal_migration_table SET is_father_updated = TRUE, father_vsm_id = ".$fatherVsmId.$setFatherId.
					   " WHERE id = ".$migrationTableId;
				$this->conn->exec($sql);
				$newFatherVsmIdsSet++;
			} else {
				$fathersMissing++;
			}
			$this->cmdUtil->advanceProgressBar(1, 'NewFatherVsmIds|NewFatherIds|FathersMissing: '.$newFatherVsmIdsSet.'|'.$newFatherIdsSet.'|'.$fathersMissing);
		}
		$this->cmdUtil->setEndTimeAndPrintFinalOverview();
	}


	/**
	 * @param bool $alsoCheckAnimalIdsOfParents
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function checkAnimalIds($alsoCheckAnimalIdsOfParents = true)
	{
		//SearchArrays

		$genderByAnimalIds = [];
		$sql = "SELECT id, gender
				FROM animal";
		$results = $this->conn->query($sql)->fetchAll();
		foreach ($results as $result) {
			$gender = $result['gender'];
			$id = $result['id'];
			$genderByAnimalIds[$id] = $gender;
		}

		$animalIdByVsmIds = $this->animalRepository->getAnimalPrimaryKeysByVsmIdArray();
		$animalIdByUlnString = $this->animalRepository->getAnimalPrimaryKeysByUlnString(true);

		$sql = "SELECT id, vsm_id, animal_id, father_vsm_id, mother_vsm_id, father_id, mother_id,
					  uln_country_code, uln_number
				FROM animal_migration_table";
		$results = $this->conn->query($sql)->fetchAll();


		$this->cmdUtil->setStartTimeAndPrintIt(count($results),1);

		$recordsUpdated = 0;
		$recordsSkipped = 0;
		foreach ($results as $result) {
			$id = $result['id'];
			$vsmId = $result['vsm_id'];
			$animalIdInDb = $result['animal_id'];
			$fatherIdInDb = $result['father_id'];
			$fatherVsmId = $result['father_vsm_id'];
			$motherIdInDb = $result['mother_id'];
			$motherVsmId = $result['mother_vsm_id'];


			$animalId = null;
			if(array_key_exists($vsmId, $animalIdByVsmIds)) {
				$animalId = $animalIdByVsmIds[$vsmId];

			} else {
				$ulnCountryCode = $result['uln_country_code'];
				$ulnNumber = $result['uln_number'];
				$uln = null;
				if(is_string($ulnCountryCode) && is_string($ulnNumber)) {
					$uln = $ulnCountryCode.' '.$ulnNumber;
				}

				if($animalIdByUlnString->contains($uln)) {
					$animalId = $animalIdByUlnString->get($uln);
				}
			}

			if($alsoCheckAnimalIdsOfParents) {

				$fatherId  = null;
				if(array_key_exists($fatherVsmId, $animalIdByVsmIds)) {
					$fatherIdRetrieved = $animalIdByVsmIds[$fatherVsmId];
					if(array_key_exists($fatherIdRetrieved, $genderByAnimalIds)) {
						if($genderByAnimalIds[$fatherIdRetrieved] == GenderType::MALE) {
							$fatherId = $fatherIdRetrieved;
						}
					}
				}

				$motherId = null;
				if(array_key_exists($motherVsmId, $animalIdByVsmIds)) {
					$motherIdRetrieved = $animalIdByVsmIds[$motherVsmId];
					if(array_key_exists($motherIdRetrieved, $genderByAnimalIds)) {
						if($genderByAnimalIds[$motherIdRetrieved] == GenderType::FEMALE) {
							$motherId = $motherIdRetrieved;
						}
					}
				}

				if($animalIdInDb != $animalId || $fatherIdInDb != $fatherId || $motherIdInDb != $motherId) {
					$animalId = SqlUtil::getNullCheckedValueForSqlQuery($animalId, false);
					$fatherId = SqlUtil::getNullCheckedValueForSqlQuery($fatherId, false);
					$motherId = SqlUtil::getNullCheckedValueForSqlQuery($motherId, false);

					$sql = "UPDATE animal_migration_table SET
						  		animal_id = ".$animalId.
						", mother_id = ".$motherId.
						", father_id = ".$fatherId.
						" WHERE id = ".$id;
					$this->conn->exec($sql);

					$recordsUpdated++;
				} else { $recordsSkipped++; }

			} else {
				if($animalIdInDb != $animalId) {
					$animalId = SqlUtil::getNullCheckedValueForSqlQuery($animalId, false);

					$sql = "UPDATE animal_migration_table SET animal_id = ".$animalId." WHERE id = ".$id;
					$this->conn->exec($sql);

					$recordsUpdated++;
				} else { $recordsSkipped++; }
			}

			$this->cmdUtil->advanceProgressBar(1,'AnimalIds updated|skipped: '.$recordsUpdated.'|'.$recordsSkipped);
		}
		$this->cmdUtil->setEndTimeAndPrintFinalOverview();

		$this->fixParentAnimalIdsInMigrationTable();
	}


	/**
	 * Fix parent animalIds in AnimalMigrationTable
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 */
	private function fixParentAnimalIdsInMigrationTable()
	{
		$sql = "SELECT a.id, mother.gender FROM animal_migration_table a
					INNER JOIN animal mother ON mother.id = a.mother_id
					WHERE gender <> 'FEMALE'";
		$motherResults = $this->conn->query($sql)->fetchAll();

		$sql = "SELECT a.id, mother.gender FROM animal_migration_table a
					INNER JOIN animal mother ON mother.id = a.mother_id
					WHERE gender <> 'FEMALE'";
		$fatherResults = $this->conn->query($sql)->fetchAll();

		$totalCount = count($motherResults) + count($fatherResults);
		if($totalCount > 0) {
			$this->cmdUtil->setStartTimeAndPrintIt($totalCount, 1);

			
			foreach ($motherResults as $result) {
				$migrationTableId = $result['id'];
				$sql = "UPDATE animal_migration_table SET mother_id = NULL WHERE id = ".$migrationTableId;
				$this->conn->exec($sql);
				$this->cmdUtil->advanceProgressBar(1, 'Parent animalIds in AnimalMigrationTable cleared (due to mismatched gender)');
			}

			foreach ($fatherResults as $result) {
				$migrationTableId = $result['id'];
				$sql = "UPDATE animal_migration_table SET mother_id = NULL WHERE id = ".$migrationTableId;
				$this->conn->exec($sql);
				$this->cmdUtil->advanceProgressBar(1, 'Parent animalIds in AnimalMigrationTable cleared (due to mismatched gender)');
			}
			$this->cmdUtil->setEndTimeAndPrintFinalOverview();
		}
	}
	
	
	public function checkGendersInDatabase()
	{
		$sql = "SELECT t.id, animal_id, gender_in_database, gender FROM animal_migration_table t
				INNER JOIN animal a ON a.id = t.animal_id
				WHERE gender <> t.gender_in_database";
		$results = $this->conn->query($sql)->fetchAll();
		$count = count($results);
		
		if($count == 0) { $this->output->writeln('All gender_in_databases in animal_migration_table are correct'); return; }

		$this->cmdUtil->setStartTimeAndPrintIt($count, 1);
		foreach ($results as $result) {
			$gender = $result['gender'];
			$id = $result['id'];
			$sql = "UPDATE animal_migration_table SET gender_in_database = '".$gender."' WHERE id = ".$id;
			$this->conn->exec($sql);
			$this->cmdUtil->advanceProgressBar(1, 'Fixing gender_in_databases in animal_migration_table');
		}
		$this->cmdUtil->setProgressBarMessage('Fixed '.$count.' gender_in_databases in animal_migration_table');
		$this->cmdUtil->setEndTimeAndPrintFinalOverview();
	}
	

	/**
     * Example
    $this->writeCorrectedCsvRecord($vsmId . ';' . $animalId  . ';' .$uln. ';' .$stnImport. ';' . $ulnCountryCode . ';' . $ulnNumber . ';' . $animalOrderNumber . ';' . $pedigreeCountryCode.';'.$pedigreeNumber.';'.$nickname.';'.$fatherVsmId.';'.$fatherId.';'.$motherVsmId.';'.$motherId
    .';'.$genderInFile.';'.$genderInDatabase.';'.$dateOfBirth.';'.$breedCode.';'.$ubnOfBirth.';'.$locationOfBirth
    .';'.$pedigreeRegisterId.';'.$breedType.';'.$scrapieGenotype);
     *
	 * @param string $row
	 */
	private function writeCorrectedCsvRecord($row)
	{
		file_put_contents($this->outputFolder.'/'.self::FILENAME_CORRECTED_CSV,
			$row
			."\n", FILE_APPEND);
	}


    /**
     * @return array
     */
    private function generateLatestLocationSearchArray()
    {
        //First find latest active locations
        $sql = "SELECT l.id, l.ubn, c.company_name FROM location l
                  INNER JOIN company c ON c.id = l.company_id
                WHERE l.is_active = TRUE AND c.is_active = TRUE";
        $results1 = $this->conn->query($sql)->fetchAll();
        
        //Then find (incorrectly) deactivated locations
        $sql = "SELECT l.id, l.ubn, c.company_name FROM location l
                  INNER JOIN company c ON c.id = l.company_id
                WHERE (l.is_active = FALSE AND c.is_active = TRUE) OR (l.is_active = TRUE AND c.is_active = FALSE)";
        $results2 = $this->conn->query($sql)->fetchAll();

        //Then get all locations
        $sql = "SELECT l.id, l.ubn, c.company_name FROM location l
                  INNER JOIN company c ON c.id = l.company_id
                WHERE l.is_active = FALSE AND c.is_active = FALSE";
        $results3 = $this->conn->query($sql)->fetchAll();

        $results = [];
        foreach ($results1 as $result) {
            $results[$result['ubn']] = $result['id'];
        }

        foreach ($results2 as $result) {
            $ubn = $result['ubn'];
            if(!array_key_exists($ubn, $results)) {
                $results[$ubn] = $result['id'];
            }
        }

        foreach ($results3 as $result) {
            $ubn = $result['ubn'];
            if(!array_key_exists($ubn, $results)) {
                $results[$ubn] = $result['id'];
            }
        }

        return $results;
    }


	/**
	 * @param string|int $primaryVsmId
	 * @param string|int $vsmId
	 * @throws \Doctrine\DBAL\DBALException
	 */
	private function updateChildrenVsmIds($primaryVsmId, $vsmId)
	{
		$sql = "UPDATE animal_migration_table SET mother_vsm_id = ".$primaryVsmId." WHERE mother_vsm_id = ".$vsmId;
		$this->conn->exec($sql);
		$sql = "UPDATE animal_migration_table SET father_vsm_id = ".$primaryVsmId." WHERE father_vsm_id = ".$vsmId;
		$this->conn->exec($sql);
	}


	private function saveVsmIdGroup($primaryVsmId, $vsmId, $vsmIdGroups)
	{
		if(!array_key_exists($vsmId, $vsmIdGroups)) {
			$sql = "INSERT INTO vsm_id_group (id, primary_vsm_id, secondary_vsm_id
						)VALUES(nextval('vsm_id_group_id_seq'),".$primaryVsmId.",".$vsmId.")";
			$this->conn->exec($sql);
			$vsmIdGroups[$vsmId] = $primaryVsmId;
		}
		return $vsmIdGroups;
	}


	/**
	 * @return array
	 * @throws \Doctrine\DBAL\DBALException
	 */
	private function getVsmIdGroups()
	{
		$sql = "SELECT id, primary_vsm_id, secondary_vsm_id FROM vsm_id_group";
		$results = $this->conn->query($sql)->fetchAll();

		$vsmIdGroups = [];
		foreach ($results as $result) {
			$vsmIdGroups[$result['secondary_vsm_id']] = $result['primary_vsm_id'];
		}
		return $vsmIdGroups;
	}


	private function resetAnimalIdVsmLocationAndGenderSearchArrays()
	{
		$this->cmdUtil->writeln(['','Retrieving animal data from database to create the searchArrays - '.TimeUtil::getTimeStampNow()]);

		$sql = "SELECT id, name, CONCAT(uln_country_code, uln_number) as uln, gender, location_id
				FROM animal";
		$results = $this->conn->query($sql)->fetchAll();

		$this->cmdUtil->writeln(['',count($results).' Animals retrieved. Now creating the searchArrays - '.TimeUtil::getTimeStampNow()]);

		$this->animalsByAnimalId = [];
		$this->animalIdsOnLocation = [];
		$this->animalIdByVsmId = [];
		$this->genderByAnimalId = [];
		$this->currentlyUsedUlns = [];
		foreach($results as $result) {
			$animalId = $result['id'];
			$vsmId = $result['name'];
			$gender = $result['gender'];
			$this->animalsByAnimalId[$animalId] = $result;

			if($result['location_id'] != null) {
				$this->animalIdsOnLocation[$animalId] = $animalId;
			}

			if(is_string($vsmId)) {
				if(ctype_digit($vsmId)) {
					$this->animalIdByVsmId[$vsmId] = $animalId;
				}
			}

			if($animalId != null) {
				$this->genderByAnimalId[$animalId] = $gender;
			}

			$uln = trim($result['uln']);
			if($uln != '') {
				$this->currentlyUsedUlns[$uln] = $uln;
			}
		}
		ksort($this->currentlyUsedUlns);

		$this->cmdUtil->writeln(['','Creating searchArray for historicUlns - '.TimeUtil::getTimeStampNow(), '']);
		
		$sql = "SELECT CONCAT(uln_country_code_to_replace, uln_number_to_replace) as old_uln,
 					CONCAT(uln_country_code_replacement, uln_number_replacement) as new_uln
 				FROM declare_tag_replace r
					INNER JOIN declare_base b ON r.id = b.id
				WHERE (request_state = 'FINISHED_WITH_WARNING' OR request_state = 'FINISHED' OR request_state = 'IMPORTED')";
		$results = $this->conn->query($sql)->fetchAll();

		$this->historicUsedUlns = [];
		foreach ($results as $result) {
			$oldUln = $result['old_uln'];
			$newUln = $result['new_uln'];
			if(!array_key_exists($oldUln, $this->currentlyUsedUlns)) {
				$this->historicUsedUlns[$oldUln] = $oldUln;
			}

			if(!array_key_exists($newUln, $this->currentlyUsedUlns)) {
				$this->historicUsedUlns[$newUln] = $newUln;
			}
		}
		ksort($this->historicUsedUlns);

		$this->cmdUtil->writeln(['','Class searchArrays reset! - '.TimeUtil::getTimeStampNow(), '']);

		$sql = null;
		$results = null;
	}


	public function test()
	{
		$this->migrate();
	}


	public function exportToCsv()
	{
		SqlUtil::exportToCsv($this->em, self::TABLE_NAME_IN_SNAKE_CASE, $this->outputFolder, self::FILENAME_CSV_EXPORT, $this->output, $this->cmdUtil);
	}


	public function importFromCsv()
	{
		$columnTypes = [];

		foreach ($this->columnHeaders as $columnHeader) {
			$columnTypes[] = $this->getColumnType($columnHeader);
		}

		SqlUtil::importFromCsv($this->em, self::TABLE_NAME_IN_SNAKE_CASE, $this->columnHeaders, $columnTypes, $this->data, $this->output, $this->cmdUtil);
	}


	/**
	 * @param string $columnHeader
	 * @return string
	 */
	private function getColumnType($columnHeader)
	{
		switch ($columnHeader) {
			case "id": return ColumnType::INTEGER;
			case "vsm_id": return ColumnType::INTEGER;
			case "animal_id": return ColumnType::INTEGER;
			case "uln_origin": return ColumnType::STRING;
			case "stn_origin": return ColumnType::STRING;
			case "uln_country_code": return ColumnType::STRING;
			case "uln_number": return ColumnType::STRING;
			case "animal_order_number": return ColumnType::STRING;
			case "pedigree_country_code": return ColumnType::STRING;
			case "pedigree_number": return ColumnType::STRING;
			case "nickname": return ColumnType::STRING;
			case "father_vsm_id": return ColumnType::INTEGER;
			case "father_id": return ColumnType::INTEGER;
			case "mother_vsm_id": return ColumnType::INTEGER;
			case "mother_id": return ColumnType::INTEGER;
			case "gender_in_file": return ColumnType::STRING;
			case "gender_in_database": return ColumnType::STRING;
			case "date_of_birth": return ColumnType::DATETIME;
			case "breed_code": return ColumnType::STRING;
			case "ubn_of_birth": return ColumnType::STRING;
			case "location_of_birth_id": return ColumnType::INTEGER;
			case "pedigree_register_id": return ColumnType::INTEGER;
			case "breed_type": return ColumnType::STRING;
			case "scrapie_genotype": return ColumnType::STRING;
			case "is_breed_code_updated": return ColumnType::BOOLEAN;
			case "old_breed_code": return ColumnType::STRING;
			case "corrected_gender": return ColumnType::STRING;
			case "is_ubn_updated": return ColumnType::BOOLEAN;
			case "is_uln_updated": return ColumnType::BOOLEAN;
			case "is_stn_updated": return ColumnType::BOOLEAN;
			case "is_animal_order_number_updated": return ColumnType::BOOLEAN;
			case "is_father_updated": return ColumnType::BOOLEAN;
			case "deleted_stn_origin": return ColumnType::STRING;
			case "deleted_uln_origin": return ColumnType::STRING;
			case "is_correct_record": return ColumnType::BOOLEAN;
			case "is_record_migrated": return ColumnType::BOOLEAN;
			case "deleted_father_vsm_id": return ColumnType::INTEGER;
			case "deleted_mother_vsm_id": return ColumnType::INTEGER;
			default: return ColumnType::STRING;
		}
	}


	public function fixAnimalTableAfterImport()
	{
		$this->removeIdenticalAnimals();
	}

	private function removeIdenticalAnimals()
	{
		//SearchArrays
		$sql = "SELECT primary_vsm_id, secondary_vsm_id FROM vsm_id_group";
		$vsmIdGroupResults = $this->conn->query($sql)->fetchAll();
		
		$primaryVsmIds = [];
		$primaryVsmIdBySecondaryVsmIds = [];
		foreach($vsmIdGroupResults as $result) {
			$primaryVsmId = $result['primary_vsm_id'];
			$secondaryVsmId = $result['secondary_vsm_id'];
			$primaryVsmIds[$primaryVsmId] = $primaryVsmId;
			$primaryVsmIdBySecondaryVsmIds[$secondaryVsmId] = $primaryVsmId;
		}

		$sql = "SELECT DISTINCT(parent_father_id) as parent_id FROM animal WHERE parent_father_id NOTNULL
				UNION
				SELECT DISTINCT(parent_mother_id) as parent_id FROM animal WHERE parent_mother_id NOTNULL
				ORDER BY parent_id";
		$results = $this->conn->query($sql)->fetchAll();

		$parentIds = [];
		foreach ($results as $result) {
			$parentId = $result['parent_id'];
			$parentIds[$parentId] = $parentId;
		}


		$sql = "SELECT a.id, a.name, a.type FROM animal a
				INNER JOIN (
					SELECT uln_country_code, uln_number, date_of_birth, type FROM animal
					GROUP BY uln_country_code, uln_number, date_of_birth, parent_father_id, parent_mother_id, location_id,
					  pedigree_country_code, pedigree_number, date_of_birth, date_of_death, gender, transfer_state, is_alive,
					  animal_order_number, type, breed_type, breed_code, scrapie_genotype, litter_id, note, breed_codes_id, ubn_of_birth,
					  pedigree_register_id, predicate_score, predicate, blindness_factor, myo_max, nickname, name
					HAVING COUNT(*) > 1
					)u ON u.uln_number = a.uln_number AND u.uln_country_code = a.uln_country_code";
		$results = $this->conn->query($sql)->fetchAll();

		$groupedAnimalIdsByName = [];
		$typeByName = [];
		foreach($results as $result) {
			$animalId = $result['id'];
			$name = $result['name'];
			$type = $result['type'];

			if(array_key_exists($name, $groupedAnimalIdsByName)) {
				$group = $groupedAnimalIdsByName[$name];
			} else {
				$group = [];
			}
			$group[] = $animalId;
			$groupedAnimalIdsByName[$name] = $group;

			$typeByName[$name] = $type;
		}

		if(count($groupedAnimalIdsByName) == 0) {
			$this->output->writeln('There are no identical animals in the database');
			return;
		}

		$this->cmdUtil->setStartTimeAndPrintIt(count($groupedAnimalIdsByName),1);

		$names = array_keys($groupedAnimalIdsByName);
		foreach ($names as $name) {
			$group = $groupedAnimalIdsByName[$name];
			$animalId0 = $group[0];
			$animalId1 = $group[1];
			$type = $typeByName[$name];

			$animalId0IsParent = array_key_exists($animalId0, $parentIds);
			$animalId1IsParent = array_key_exists($animalId1, $parentIds);

			if(array_key_exists($name, $primaryVsmIdBySecondaryVsmIds)) {
				dump('name is a secondaryVsmId '.$name); //Does not occur on production so skip this logic
			}


			if($animalId0IsParent && $animalId1IsParent) {
				//Keep ids
			} elseif($animalId0IsParent && !$animalId1IsParent) {
				//Keep ids
			} else {
				/*
				 * !$animalId0IsParent && $animalId1IsParent
				 * OR
				 * !$animalId0IsParent && !$animalId1IsParent
				 *
				 * Switch ids
				 */
				$animalId1 = $group[0];
				$animalId0 = $group[1];
			}

			if($type == 'Ewe') {
				$sql = "UPDATE litter SET animal_mother_id = ".$animalId0." WHERE animal_mother_id = ".$animalId1;
				$this->conn->exec($sql);
				$sql = "UPDATE animal SET parent_mother_id = ".$animalId0." WHERE parent_mother_id = ".$animalId1;
				$this->conn->exec($sql);
			} elseif($type == 'Ram') {
				$sql = "UPDATE litter SET animal_father_id = ".$animalId0." WHERE animal_father_id = ".$animalId1;
				$this->conn->exec($sql);
				$sql = "UPDATE animal SET parent_father_id = ".$animalId0." WHERE parent_father_id = ".$animalId1;
				$this->conn->exec($sql);
			}
			foreach (['weight','exterior','body_fat'] as $tableName) {
				$sql = "UPDATE ".$tableName." SET animal_id = ".$animalId0." WHERE animal_id = ".$animalId1;
				$this->conn->exec($sql);
			}

			$this->animalRepository->deleteAnimalBySql($type, $animalId1);

			$this->cmdUtil->advanceProgressBar(1,'Deleting identical animals');
		}
		$this->cmdUtil->setEndTimeAndPrintFinalOverview();
	}


	public function importMissingPedigreeRegisters20161219()
	{
		$this->cmdUtil->printTitle('Import missing pedigreeRegisters as found by Marjo 2016-12-19');

		$includeNonNsfoPedigreeRegisters = $this->cmdUtil->generateConfirmationQuestion('Include pedigreeRegisters not managed by NSFO (y/n, default = no)');

		if($includeNonNsfoPedigreeRegisters) {
			$includeNonNsfoPedigreeRegisters = $this->cmdUtil->generateConfirmationQuestion('Are you sure you wish to include them? (y/n, default = no)');
		}

		$this->cmdUtil->writeln('Choice: '.($includeNonNsfoPedigreeRegisters ? 'INCLUDE' : 'EXCLUDE').' non-NSFO PedigreeRegisters');


		$sql = "SELECT pedigree_register_id, id, name, DATE(date_of_birth) as date_of_birth,
						CONCAT(uln_country_code,uln_number) as uln
				FROM animal WHERE name NOTNULL ";
		$results = $this->conn->query($sql)->fetchAll();
		$pedigreeRegisterIdByAnimalId = SqlUtil::groupSqlResultsOfKey1ByKey2('pedigree_register_id', 'id', $results);
		$dateOfBirthStringByAnimalId = SqlUtil::groupSqlResultsOfKey1ByKey2('date_of_birth', 'id', $results);
		$animalIdByUln = SqlUtil::groupSqlResultsOfKey1ByKey2('id', 'uln', $results);

		$sql = "SELECT abbreviation, id FROM pedigree_register";
		$results = $this->conn->query($sql)->fetchAll();
		$pedigreeRegisterIdByAbbreviation = SqlUtil::groupSqlResultsOfKey1ByKey2('id', 'abbreviation', $results);

        $animalPrimaryKeysByVsmId = $this->animalRepository->getAnimalPrimaryKeysByVsmIdArray();
		$this->animalRepository;

		$newestUlnByOldUln = $this->declareTagReplaceRepository->getNewReplacementUlnSearchArray();

		$sql = "SELECT primary_vsm_id, secondary_vsm_id FROM vsm_id_group ";
		$results = $this->conn->query($sql)->fetchAll();
		$primaryVsmIdBySecondaryVsmId = SqlUtil::groupSqlResultsOfKey1ByKey2('primary_vsm_id', 'secondary_vsm_id', $results);
		$secondaryVsmIdByPrimaryVsmId = SqlUtil::groupSqlResultsOfKey1ByKey2('secondary_vsm_id', 'primary_vsm_id', $results);


		$loopCount = 0;
		$skippedCount = 0;
		$notFoundCount = 0;
		$updatedCount = 0;

		$this->cmdUtil->setStartTimeAndPrintIt(count($this->data), 1);
		foreach ($this->data as $record) {
			$loopCount++;

			$vsmId = intval($record[0]);
			$animalId = ArrayUtil::get($vsmId, $animalPrimaryKeysByVsmId);

			$pedigreeRegisterIdInDb = ArrayUtil::get($animalId, $pedigreeRegisterIdByAnimalId);

			$pedigreeRegisterAbbreviation = $this->getPedigreeRegisterAbbreviation($record[13], $includeNonNsfoPedigreeRegisters, false);
			$pedigreeRegisterId = ArrayUtil::get($pedigreeRegisterAbbreviation,$pedigreeRegisterIdByAbbreviation);

			if($pedigreeRegisterId == $pedigreeRegisterIdInDb) {
				$skippedCount++;
				$this->cmdUtil->advanceProgressBar(1, 'Skipped|Updated|Missing: '.$skippedCount.'|'.$updatedCount.'|'.$notFoundCount);
				continue;
			}

			$dateOfBirthStringInCsv = $record[8]; //All dateOfBirths in the csv already have leading zeroes
			if($dateOfBirthStringInCsv == '') { $dateOfBirthStringInCsv = null; }

			//Get animalId by primary- of secondaryVsmIds in vsmGroup table
			if($animalId == null) {

				$primaryVsmId = ArrayUtil::get($vsmId, $primaryVsmIdBySecondaryVsmId);

				if($primaryVsmId != null) {

					$animalId = ArrayUtil::get($primaryVsmId, $animalPrimaryKeysByVsmId);

				} else {
					$secondaryVsmId = ArrayUtil::get($vsmId, $secondaryVsmIdByPrimaryVsmId);

					if($secondaryVsmId != null) {
						$animalId = ArrayUtil::get($secondaryVsmId, $animalPrimaryKeysByVsmId);
					}
				}
			}

			$ulnCountryCode = null;
			$ulnNumber = null;
			$newestUln = null;

			//In animalId is still null, find by uln
			if($animalId == null) {
				$ulnParts = AnimalTableImporter::parseUln($record[3]);
				$ulnCountryCode = $ulnParts[JsonInputConstant::ULN_COUNTRY_CODE];
				$ulnNumber = $ulnParts[JsonInputConstant::ULN_NUMBER];

				//Get newest uln
				if(is_string($ulnCountryCode) && is_string($ulnNumber) ) {
					$newestUlnParts = ArrayUtil::get($ulnCountryCode.$ulnNumber, $newestUlnByOldUln);
					if(is_array($ulnParts)) {
						$newestUln = Utils::getNullCheckedArrayValue(Constant::ULN_COUNTRY_CODE_NAMESPACE, $ulnParts).
									 Utils::getNullCheckedArrayValue(Constant::ULN_NUMBER_NAMESPACE, $ulnParts);

						$animalId = ArrayUtil::get($newestUln, $animalIdByUln);
					}
				}
			}


			if($animalId == null) {
				file_put_contents($this->outputFolder.'/'.self::FILENAME_ANIMALS_NOT_FOUND_FILLING_PEDIGREE_REGISTERS,
					implode(';',$record).';MISSING;'."\n", FILE_APPEND);
				$notFoundCount++;

			} else {

				$dateOfBirthStringInDb = ArrayUtil::get($animalId, $dateOfBirthStringByAnimalId);
				if($dateOfBirthStringInDb != $dateOfBirthStringInCsv) {

					$notFoundCount++;
					file_put_contents($this->outputFolder.'/'.self::FILENAME_ANIMALS_NOT_FOUND_FILLING_PEDIGREE_REGISTERS,
						implode(';',$record).';GebDatumAnders;'.$animalId."\n", FILE_APPEND);
				} else {
					$sql = "UPDATE animal SET pedigree_register_id = ".$pedigreeRegisterId." WHERE id = ".$animalId;
					$this->conn->exec($sql);
					$updatedCount++;
				}
			}


			$this->cmdUtil->advanceProgressBar(1, 'Skipped|Updated|Missing: '.$skippedCount.'|'.$updatedCount.'|'.$notFoundCount);



			/* Unused columns */

//			$animalOrderNumber = 'NULL';
//			if($ulnParts[JsonInputConstant::ULN_NUMBER] != null) {
//				$newAnimalOrderNumber = StringUtil::getNullAsStringOrWrapInQuotes(StringUtil::getLast5CharactersFromString($ulnNumber));
//			}
//
//			$nickName = StringUtil::getNullAsStringOrWrapInQuotes(utf8_encode(StringUtil::escapeSingleApostrophes($record[4])));
//			$fatherVsmId = SqlUtil::getNullCheckedValueForSqlQuery($record[5], false);
//			$motherVsmId = SqlUtil::getNullCheckedValueForSqlQuery($record[6], false);
//			$genderInFile = StringUtil::getNullAsStringOrWrapInQuotes(AnimalTableImporter::parseGender($record[7]));
//
//			$breedCode = StringUtil::getNullAsStringOrWrapInQuotes($record[9]);
//			$ubnOfBirth = StringUtil::getNullAsStringOrWrapInQuotes($record[10]); //ubnOfBreeder
//
//			$breedType = SqlUtil::getNullCheckedValueForSqlQuery(Translation::getEnglish(strtoupper($record[11])), true);
//			$scrapieGenotype = SqlUtil::getNullCheckedValueForSqlQuery($record[12], true);
//			$note = $record[14];

            /*  Note!
                There are some genders in the the import file that are NEUTER but are FEMALE or MALE in the Database.
                Those are the only mismatched genders. So take the gender in the database as leading.

                In the csv there are some animals which have a stn and are missing a pedigreeRegister.
                And some animals without a valid pedigreeNumber but with a pedigreeRegister.
            */


		}
		$this->cmdUtil->setEndTimeAndPrintFinalOverview();
	}


	/**
	 * @param $pedigreeInCsv
	 * @param bool $includeNonNsfoPedigreeRegisters
	 * @param bool $wrapInQuotesForSql
	 * @return string
	 */
	public function getPedigreeRegisterAbbreviation($pedigreeInCsv, $includeNonNsfoPedigreeRegisters, $wrapInQuotesForSql = true)
	{
		if($pedigreeInCsv == '') { return 'NULL'; }

		$pedigreeRegisterTranslations = $this->getPedigreeRegisterTranslations($includeNonNsfoPedigreeRegisters);

		if(!array_key_exists($pedigreeInCsv, $pedigreeRegisterTranslations)) { return 'NULL'; }

		$abbreviation = strtr($pedigreeInCsv, $pedigreeRegisterTranslations);

		return $wrapInQuotesForSql ? SqlUtil::getNullCheckedValueForSqlQuery($abbreviation, true) : $abbreviation;
	}


	/**
	 * @param boolean $includeNonNsfoPedigreeRegisters
	 * @return array
	 */
	private function getPedigreeRegisterTranslations($includeNonNsfoPedigreeRegisters)
	{
		if($includeNonNsfoPedigreeRegisters) {

			return [
				"Bleu du Maine" => "BdM",
				"Clun Forest" => "CF",
				"NFS" => "NFS",
				"NH" => "NH",
				"Noord Hollander" => "NH",
				"NTS" => "NTS",
				"NTS?" => "NTS", //These three animals are NTS
				"Ruischaap" => "RUI",
				"Soay" => "Soay",
				"TSNH" => "TSNH",
				//The following pedigreeRegisters are not registered with NSFO and should not be displayed on the pedigreeCertificates
				"Blauwe Texelaar" => "BT",
				"Blessum" => "BL",
				"Dassenkop" => "DK",
				"Hampshire Down" => "HD",
				"Kerry Hill" => "KE",
				"Reyland" => "RY",
				"TES" => "TES",
			];

		} else {

			return [
				"Bleu du Maine" => "BdM",
				"Clun Forest" => "CF",
				"NFS" => "NFS",
				"NH" => "NH",
				"Noord Hollander" => "NH",
				"NTS" => "NTS",
				"NTS?" => "NTS", //These three animals are NTS
				"Ruischaap" => "RUI",
				"Soay" => "Soay",
				"TSNH" => "TSNH",
			];

		}
	}
	
	
	public function migrateV2()
	{
		$this->cmdUtil->printTitle('Migrating AnimalTable data into animal table *V2*');

		$this->cmdUtil->writeln(['Fixing tables before migration...', '']);
		
		$clearPedigreeCountryCodesAndNumbersWithoutPedigreeRegisters = true;

		//AnimalIds might have been changed due to duplicateFixes
		AnimalMigrationTableFixer::updateAnimalIdsInMigrationTable($this->cmdUtil, $this->conn);
		AnimalMigrationTableFixer::updateGenderInDatabaseInMigrationTable($this->cmdUtil, $this->conn);
		AnimalMigrationTableFixer::updateParentIdsInMigrationTable($this->cmdUtil, $this->conn);

		//VsmIds might have been altered due to duplicateFixes
		/** @var VsmIdGroupRepository $vsmIdGroupRepository */
		$vsmIdGroupRepository = $this->em->getRepository(VsmIdGroup::class);
		$vsmIdGroupRepository->fixSwappedPrimaryAndSecondaryVsmId($this->cmdUtil);

		/*
		 * NOTE!!!!
		 * If EWE don't import children as father
		 * If RAM don't import children as mother
		 *
		 * VsmIds for duplicate animals are found in the vsm_id_group table
		 */

		$this->cmdUtil->writeln(['','Creating searchArrays...', '']);

		$this->cmdUtil->setStartTimeAndPrintIt(9,1,'Retrieving data ...');

		//SearchArrays
		$this->cmdUtil->advanceProgressBar(1, 'Retrieving data and generating newestUlnByOldUln searchArray ...');
		$newestUlnByOldUln = $this->declareTagReplaceRepository->getNewReplacementUlnSearchArray();

		$this->cmdUtil->advanceProgressBar(1, 'Retrieving data and generating primaryVsmIdsBySecondaryVsmId searchArrays ...');
		$this->primaryVsmIdsForSecondaryIds = $this->getVsmIdGroupRepository()->getPrimaryVsmIdsBySecondaryVsmId();

		$this->cmdUtil->advanceProgressBar(1, 'Retrieving data and generating animalData searchArrays ...');
		$this->resetAnimalIdVsmLocationAndGenderSearchArrays();

		$this->cmdUtil->advanceProgressBar(1, 'Retrieving animalMigration data ...');

		/* Only process animals which:
            - are not imported yet, meaning those that have no animalId in the animalMigrationTable, and
            - who do not have
        */

		//Only process animals where genders match will those in the database
		$sql = "SELECT a.id, a.vsm_id, a.animal_id, a.uln_country_code, a.uln_number, a.animal_order_number, a.pedigree_country_code,
				  a.pedigree_number, a.nick_name, a.father_vsm_id, a.father_id, a.mother_vsm_id, a.mother_id, a.gender_in_file,
				  a.date_of_birth, a.breed_code, a.ubn_of_birth, a.location_of_birth_id, a.pedigree_register_id, a.breed_type, a.scrapie_genotype
				FROM animal_migration_table a
				WHERE a.animal_id ISNULL AND a.uln_number NOTNULL AND a.uln_country_code NOTNULL AND is_record_migrated = FALSE
				ORDER BY date_of_birth";
		$results = $this->conn->query($sql)->fetchAll();

		$this->cmdUtil->setProgressBarMessage('Fixing possible missing ewe/ram/neuter table records');
		$missingTableExtentions = AnimalRepository::fixMissingAnimalTableExtentions($this->conn);
		$this->cmdUtil->setProgressBarMessage($missingTableExtentions.' missing ewe/ram/neuter table records added');

		$this->cmdUtil->setEndTimeAndPrintFinalOverview();

		$totalAnimalsToImportCount = count($results);
		$this->output->writeln(['Retrieved data and created searchArrays!', '']);


		if($totalAnimalsToImportCount == 0) {
			$this->output->writeln(['All animals have already been imported!']);
			return;
		}
		$this->output->writeln(['Importing '.$totalAnimalsToImportCount.' animals...','']);

		//First import animals

		$newAnimals = 0;
		$skippedAnimals = 0;

		$this->parentVsmIdsUpdated = [];

		$this->cmdUtil->setStartTimeAndPrintIt($totalAnimalsToImportCount, 1);

		$insertString = '';
		$migrationTableCheckListIds = [];
		$insertBatchCount = 0;
		$isRecordMigratedMigrationTableIds = [];

		$maxAnimalId = SqlUtil::getMaxId($this->conn, 'animal');

		$this->cmdUtil->setStartTimeAndPrintIt($totalAnimalsToImportCount, 1);
		foreach ($results as $result) {
			$migrationTableId = $result['id'];
			$vsmId = $result['vsm_id'];
			$gender = $result['gender_in_file'];

			$animalId = null;
			$currentGenderInDatabase = null;
			if(array_key_exists($vsmId, $this->animalIdByVsmId)) {
				$animalId = $this->animalIdByVsmId[$vsmId];

				if(array_key_exists($animalId, $this->genderByAnimalId)) {
					$currentGenderInDatabase = $this->genderByAnimalId[$animalId];
				}
			}

			//Skip duplicateVsmIds!
			$isDuplicateVsmId = array_key_exists($vsmId, $this->primaryVsmIdsForSecondaryIds) || in_array($vsmId, $this->primaryVsmIdsForSecondaryIds);
			$isGenderMismatched = false;
			if($currentGenderInDatabase != null && $currentGenderInDatabase != GenderType::NEUTER) {
				//NOTE! Neuters must be given the new gender, if the gender will be updated!
				$isGenderMismatched = $currentGenderInDatabase != $gender;
			}


			$ulnCountryCode = $result['uln_country_code'];
			$ulnNumber = $result['uln_number'];
			$uln = $ulnCountryCode.$ulnNumber;
			$ulnAlreadyExists = array_key_exists($uln, $this->currentlyUsedUlns) || array_key_exists($uln, $this->historicUsedUlns);

			$animalOrderNumber = $result['animal_order_number'];
			$pedigreeCountryCode = $result['pedigree_country_code'];
			$pedigreeNumber = $result['pedigree_number'];
			$pedigreeRegisterId = $result['pedigree_register_id'];
			if($pedigreeRegisterId == null) {
				$pedigreeCountryCode = null;
				$pedigreeNumber = null;
			}
			$nickName = $result['nick_name'];
			$type = GenderChangerForMigrationOnly::getClassNameByGender($gender);

			/*
             * Get animalId from vsmId to make sure the gender is correct
             */
			$fatherVsmId = $this->getPrimaryVsmId($result['father_vsm_id']);
			$fatherId = $this->getGenderCheckedAnimalId($fatherVsmId, $this->animalIdByVsmId, $this->genderByAnimalId, GenderType::MALE);
			$motherVsmId = $this->getPrimaryVsmId($result['mother_vsm_id']);
			$motherId = $this->getGenderCheckedAnimalId($motherVsmId, $this->animalIdByVsmId, $this->genderByAnimalId, GenderType::FEMALE);

			$dateOfBirth = $result['date_of_birth'];
			$breedCode = $result['breed_code'];
			$ubnOfBirth = $result['ubn_of_birth'];
			$locationOfBirthId = $result['location_of_birth_id'];
			$breedType = $result['breed_type'];
			$scrapieGenotype = $result['scrapie_genotype'];


			//Check if animal is already in the database

			if($isDuplicateVsmId || $ulnAlreadyExists) {
				$sql = "UPDATE animal_migration_table SET is_record_migrated = TRUE WHERE id = ".$migrationTableId;
				$this->conn->exec($sql);
				$skippedAnimals++;
				$this->cmdUtil->advanceProgressBar(1, 'Migrating animalData new|skipped: '.$newAnimals.'|'.$skippedAnimals.'  insertBatch: '.$insertBatchCount);
				continue;
			}


			//Synced Animals are skipped


			$vsmIdSql = SqlUtil::getNullCheckedValueForSqlQuery($vsmId, true);
			$ulnCountryCodeSql = SqlUtil::getNullCheckedValueForSqlQuery($ulnCountryCode, true);
			$ulnNumberSql = SqlUtil::getNullCheckedValueForSqlQuery($ulnNumber, true);
			$animalOrderNumberSql = SqlUtil::getNullCheckedValueForSqlQuery($animalOrderNumber, true);
			$pedigreeCountryCodeSql = SqlUtil::getNullCheckedValueForSqlQuery($pedigreeCountryCode, true);
			$pedigreeNumberSql =  SqlUtil::getNullCheckedValueForSqlQuery($pedigreeNumber, true);
			$nickNameSql = SqlUtil::getNullCheckedValueForSqlQuery(utf8_encode(StringUtil::escapeSingleApostrophes($nickName)), true);
			$fatherIdSql = SqlUtil::getNullCheckedValueForSqlQuery($fatherId, false);
			$motherIdSql = SqlUtil::getNullCheckedValueForSqlQuery($motherId, false);
			$genderSql = SqlUtil::getNullCheckedValueForSqlQuery($gender, true);
			$dateOfBirthSql = SqlUtil::getNullCheckedValueForSqlQuery($dateOfBirth, true);
			$breedCodeSql = SqlUtil::getNullCheckedValueForSqlQuery($breedCode, true);
			$ubnOfBirthSql = SqlUtil::getNullCheckedValueForSqlQuery($ubnOfBirth, true);
			$locationOfBirthIdSql = SqlUtil::getNullCheckedValueForSqlQuery($locationOfBirthId, false);
			$pedigreeRegisterIdSql = SqlUtil::getNullCheckedValueForSqlQuery($pedigreeRegisterId, true);
			$breedTypeSql = SqlUtil::getNullCheckedValueForSqlQuery($breedType, true);
			$scrapieGenotypeSql = SqlUtil::getNullCheckedValueForSqlQuery($scrapieGenotype, true);


			//Insert new animal, process it as a batch

			$insertBatchCount++;
			$migrationTableCheckListIds[$migrationTableId] = $migrationTableId;

			$maxAnimalId++;
			$insertString = $insertString."(".$maxAnimalId.",".$vsmIdSql.",".$ulnCountryCodeSql.",".$ulnNumberSql.",".$animalOrderNumberSql
				.",".$pedigreeCountryCodeSql.",".$pedigreeNumberSql.",".$nickNameSql.",".$fatherIdSql
				.",".$motherIdSql.",".$genderSql.",".$dateOfBirthSql.",".$breedCodeSql.",".$ubnOfBirthSql
				.",".$locationOfBirthIdSql.",".$pedigreeRegisterIdSql.",".$breedTypeSql.",".$scrapieGenotypeSql
				.",3,3,TRUE,FALSE,FALSE,FALSE,'".$type."'),";
			$isRecordMigratedMigrationTableIds[] = $migrationTableId;


			//Inserting by Batch
			if($insertBatchCount%self::INSERT_BATCH_SIZE == 0 && $insertBatchCount != 0) {
				$this->insertByBatch($migrationTableCheckListIds, $insertString);

				//Reset batch values AFTER insert
				$insertString = '';
				$migrationTableCheckListIds = [];
				$insertBatchCount = 0;
				$newAnimals += self::INSERT_BATCH_SIZE;


				$filterString = SqlUtil::getFilterStringByIdsArray($isRecordMigratedMigrationTableIds);
				$sql = "UPDATE animal_migration_table SET is_record_migrated = TRUE WHERE ".$filterString;
				$this->conn->exec($sql);
			}

			$this->cmdUtil->advanceProgressBar(1, 'Migrating animalData new|updated|skipped: '.$newAnimals.'|'.$skippedAnimals.'  insertBatch: '.$insertBatchCount);
		}

		if($insertString != '') {
			//Final batch insert
			$this->insertByBatch($migrationTableCheckListIds, $insertString);
			$newAnimals += $insertBatchCount;
			$insertBatchCount = 0;

			$filterString = SqlUtil::getFilterStringByIdsArray($isRecordMigratedMigrationTableIds);
			$sql = "UPDATE animal_migration_table SET is_record_migrated = TRUE WHERE ".$filterString;
			$this->conn->exec($sql);
		}
		$this->cmdUtil->advanceProgressBar(1, 'Migrating animalData new|updated|skipped: '.$newAnimals.'|'.$skippedAnimals.'  insertBatch: '.$insertBatchCount);

		$this->cmdUtil->setEndTimeAndPrintFinalOverview();

		$this->animalRepository->updateAllLocationOfBirths($this->cmdUtil);

		if($clearPedigreeCountryCodesAndNumbersWithoutPedigreeRegisters) {
			$this->animalRepository->removePedigreeCountryCodeAndNumberIfPedigreeRegisterIsMissing($this->output);
		}
	}


	/**
	 * @param Connection $conn
	 */
	public static function fillMissingPedigreeNumbers(Connection $conn)
	{
		$sql = "UPDATE animal SET pedigree_country_code = v.pedigree_country_code, pedigree_number = v.pedigree_number
				FROM (
					   SELECT a.id, am.pedigree_country_code, am.pedigree_number FROM animal_migration_table am
						 INNER JOIN animal a ON a.name = CAST(am.vsm_id AS TEXT)
					   WHERE ((am.pedigree_country_code NOTNULL AND am.pedigree_number NOTNULL
							   AND a.pedigree_country_code ISNULL AND a.pedigree_number ISNULL)
							  OR (am.pedigree_country_code <> a.pedigree_country_code AND am.pedigree_number <> a.pedigree_number))
							 AND (a.pedigree_register_id NOTNULL OR am.pedigree_register_id NOTNULL )
					 )
				  as v(id, pedigree_country_code, pedigree_number) WHERE animal.id = v.id";
		$conn->exec($sql);
	}


	public function updateSyncedAnimals()
	{
		$this->cmdUtil->printTitle('Updating AnimalTable data into synced Animals in animal table');

		$this->cmdUtil->writeln(['Fixing tables before migration...', '']);

		$clearPedigreeCountryCodesAndNumbersWithoutPedigreeRegisters = true;

		//AnimalIds might have been changed due to duplicateFixes
		AnimalMigrationTableFixer::updateAnimalIdsInMigrationTable($this->cmdUtil, $this->conn);
		AnimalMigrationTableFixer::updateGenderInDatabaseInMigrationTable($this->cmdUtil, $this->conn);
		AnimalMigrationTableFixer::updateParentIdsInMigrationTable($this->cmdUtil, $this->conn);

		//VsmIds might have been altered due to duplicateFixes
		/** @var VsmIdGroupRepository $vsmIdGroupRepository */
		$vsmIdGroupRepository = $this->em->getRepository(VsmIdGroup::class);
		$vsmIdGroupRepository->fixSwappedPrimaryAndSecondaryVsmId($this->cmdUtil);

		$this->cmdUtil->writeln(['','Creating searchArrays...', '']);

		$this->cmdUtil->setStartTimeAndPrintIt(9,1,'Retrieving data ...');

		//SearchArrays
		$this->cmdUtil->advanceProgressBar(1, 'Retrieving data and generating newestUlnByOldUln searchArray ...');
		$newestUlnByOldUln = $this->declareTagReplaceRepository->getNewReplacementUlnSearchArray();

		$this->cmdUtil->advanceProgressBar(1, 'Retrieving data and generating primaryVsmIdsBySecondaryVsmId searchArrays ...');
		$this->primaryVsmIdsForSecondaryIds = $this->getVsmIdGroupRepository()->getPrimaryVsmIdsBySecondaryVsmId();

		$this->cmdUtil->advanceProgressBar(1, 'Retrieving data and generating animalData searchArrays ...');
		$this->resetAnimalIdVsmLocationAndGenderSearchArrays();

		$this->cmdUtil->advanceProgressBar(1, 'Retrieving animalMigration data ...');

		/* Only process animals which:
            - are not imported yet, meaning those that have no animalId in the animalMigrationTable, and
            - who do not have
        */

		//Find synced animals
		$sql = "SELECT  a.id, a.vsm_id, m.id as animal_id, a.uln_country_code, a.uln_number, a.animal_order_number, a.pedigree_country_code,
				  a.pedigree_number, a.nick_name, a.father_vsm_id, a.father_id, a.mother_vsm_id, a.mother_id, a.gender_in_file,
				  a.date_of_birth, a.breed_code, a.ubn_of_birth, a.location_of_birth_id, a.pedigree_register_id, a.breed_type, a.scrapie_genotype FROM animal m
				INNER JOIN animal_migration_table a ON m.uln_number = a.uln_number AND DATE(m.date_of_birth) = DATE(a.date_of_birth)
				WHERE m.name ISNULL";
		$results = $this->conn->query($sql)->fetchAll();

		$this->cmdUtil->setProgressBarMessage('Fixing possible missing ewe/ram/neuter table records');
		$missingTableExtentions = AnimalRepository::fixMissingAnimalTableExtentions($this->conn);
		$this->cmdUtil->setProgressBarMessage($missingTableExtentions.' missing ewe/ram/neuter table records added');

		$this->cmdUtil->setEndTimeAndPrintFinalOverview();

		$totalAnimalsToImportCount = count($results);
		$this->output->writeln(['Retrieved data and created searchArrays!', '']);


		if($totalAnimalsToImportCount == 0) {
			$this->output->writeln(['All animals have already been imported!']);
			return;
		}
		$this->output->writeln(['Importing '.$totalAnimalsToImportCount.' animals...','']);

		//First import animals

		$newAnimals = 0;
		$skippedAnimals = 0;

		$this->parentVsmIdsUpdated = [];

		$this->cmdUtil->setStartTimeAndPrintIt($totalAnimalsToImportCount, 1);

		$updateString = '';
		$migrationTableCheckListIds = [];
		$insertBatchCount = 0;
		$isRecordMigratedMigrationTableIds = [];

		$this->cmdUtil->setStartTimeAndPrintIt($totalAnimalsToImportCount, 1);
		foreach ($results as $result) {
			$migrationTableId = $result['id'];
			$vsmId = $result['vsm_id'];
			$gender = $result['gender_in_file'];

			$animalId = $result['animal_id'];
			$currentGenderInDatabase = null;
			if(array_key_exists($animalId, $this->genderByAnimalId)) {
				$currentGenderInDatabase = $this->genderByAnimalId[$animalId];
			}

			if($currentGenderInDatabase == GenderType::MALE || $currentGenderInDatabase == GenderType::FEMALE) {
				//NOTE! Keep the current gender if MALE or FEMALE!
				$gender = $currentGenderInDatabase;
			}


			$ulnCountryCode = $result['uln_country_code'];
			$ulnNumber = $result['uln_number'];
			$uln = $ulnCountryCode.$ulnNumber;

			$animalOrderNumber = $result['animal_order_number'];
			$pedigreeCountryCode = $result['pedigree_country_code'];
			$pedigreeNumber = $result['pedigree_number'];
			$pedigreeRegisterId = $result['pedigree_register_id'];
			if($pedigreeRegisterId == null) {
				$pedigreeCountryCode = null;
				$pedigreeNumber = null;
			}
			$nickName = $result['nick_name'];
			$type = GenderChangerForMigrationOnly::getClassNameByGender($gender);

			/*
             * Get animalId from vsmId to make sure the gender is correct
             */
			$fatherVsmId = $this->getPrimaryVsmId($result['father_vsm_id']);
			$fatherId = $this->getGenderCheckedAnimalId($fatherVsmId, $this->animalIdByVsmId, $this->genderByAnimalId, GenderType::MALE);
			$motherVsmId = $this->getPrimaryVsmId($result['mother_vsm_id']);
			$motherId = $this->getGenderCheckedAnimalId($motherVsmId, $this->animalIdByVsmId, $this->genderByAnimalId, GenderType::FEMALE);

			$dateOfBirth = $result['date_of_birth'];
			$breedCode = $result['breed_code'];
			$ubnOfBirth = $result['ubn_of_birth'];
			$locationOfBirthId = $result['location_of_birth_id'];
			$breedType = $result['breed_type'];
			$scrapieGenotype = $result['scrapie_genotype'];


			//Synced Animals are skipped


			$vsmIdSql = SqlUtil::getNullCheckedValueForSqlQuery($vsmId, true);
			$ulnCountryCodeSql = SqlUtil::getNullCheckedValueForSqlQuery($ulnCountryCode, true);
			$ulnNumberSql = SqlUtil::getNullCheckedValueForSqlQuery($ulnNumber, true);
			$animalOrderNumberSql = SqlUtil::getNullCheckedValueForSqlQuery($animalOrderNumber, true);
			$pedigreeCountryCodeSql = SqlUtil::getNullCheckedValueForSqlQuery($pedigreeCountryCode, true);
			$pedigreeNumberSql =  SqlUtil::getNullCheckedValueForSqlQuery($pedigreeNumber, true);
			$nickNameSql = SqlUtil::getNullCheckedValueForSqlQuery(utf8_encode(StringUtil::escapeSingleApostrophes($nickName)), true);
			$fatherIdSql = SqlUtil::getNullCheckedValueForSqlQuery($fatherId, false);
			$motherIdSql = SqlUtil::getNullCheckedValueForSqlQuery($motherId, false);
			$genderSql = SqlUtil::getNullCheckedValueForSqlQuery($gender, true);
			$dateOfBirthSql = SqlUtil::getNullCheckedValueForSqlQuery($dateOfBirth, true);
			$breedCodeSql = SqlUtil::getNullCheckedValueForSqlQuery($breedCode, true);
			$ubnOfBirthSql = SqlUtil::getNullCheckedValueForSqlQuery($ubnOfBirth, true);
			$locationOfBirthIdSql = SqlUtil::getNullCheckedValueForSqlQuery($locationOfBirthId, false);
			$pedigreeRegisterIdSql = SqlUtil::getNullCheckedValueForSqlQuery($pedigreeRegisterId, false);
			$breedTypeSql = SqlUtil::getNullCheckedValueForSqlQuery($breedType, true);
			$scrapieGenotypeSql = SqlUtil::getNullCheckedValueForSqlQuery($scrapieGenotype, true);
			$typeSql = SqlUtil::getNullCheckedValueForSqlQuery($type, true);


			//Insert new animal, process it as a batch

			$insertBatchCount++;
			$migrationTableCheckListIds[$migrationTableId] = $migrationTableId;

			$updateString = $updateString."(".$animalId.",".$vsmIdSql
				.",".$pedigreeCountryCodeSql.",".$pedigreeNumberSql.",".$nickNameSql.",".$fatherIdSql
				.",".$motherIdSql.",".$genderSql.",".$typeSql.",".$breedCodeSql.",".$ubnOfBirthSql
				.",".$locationOfBirthIdSql.",".$pedigreeRegisterIdSql.",".$breedTypeSql.",".$scrapieGenotypeSql."),";
			$isRecordMigratedMigrationTableIds[] = $migrationTableId;


			//Inserting by Batch
			if($insertBatchCount%self::INSERT_BATCH_SIZE == 0 && $insertBatchCount != 0) {
				$this->updateByBatch($updateString);

				//Reset batch values AFTER insert
				$updateString = '';
				$migrationTableCheckListIds = [];
				$insertBatchCount = 0;
				$newAnimals += self::INSERT_BATCH_SIZE;
			}

			$this->cmdUtil->advanceProgressBar(1, 'Migrating animalData updated|skipped: '.$newAnimals.'|'.$skippedAnimals.'  insertBatch: '.$insertBatchCount);
		}

		if($updateString != '') {
			//Final batch insert
			$this->updateByBatch($updateString);
			$newAnimals += $insertBatchCount;
			$insertBatchCount = 0;
		}
		$this->cmdUtil->advanceProgressBar(1, 'Migrating animalData updated|skipped: '.$newAnimals.'|'.$skippedAnimals.'  insertBatch: '.$insertBatchCount);

		$this->cmdUtil->setEndTimeAndPrintFinalOverview();

		$this->animalRepository->updateAllLocationOfBirths($this->cmdUtil);

		if($clearPedigreeCountryCodesAndNumbersWithoutPedigreeRegisters) {
			$this->animalRepository->removePedigreeCountryCodeAndNumberIfPedigreeRegisterIsMissing($this->output);
		}
	}



	/**
	 * @param Connection $conn
	 * @param CommandUtil $cmdUtil
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public static function setParentsFromMigrationTableBySql(Connection $conn, CommandUtil $cmdUtil)
	{
		$setParentIndexesInAnimal = $cmdUtil->generateConfirmationQuestion('Set Indexes on parent_father_id and parent_mother_id in animal table? (y/n, default = no)');
		if($setParentIndexesInAnimal) {
			$conn->exec('CREATE INDEX animal_parent_father_id_index ON public.animal (parent_father_id)');
			$conn->exec('CREATE INDEX animal_parent_mother_id_index ON public.animal (parent_mother_id)');
			$cmdUtil->writeln(['AnimalIds and parentIds indexes created in AnimalMigrationTable...','']);
		} else {
			$cmdUtil->writeln('*skipped*');
		}


		$setParentIndexesInAnimalMigrationTable = $cmdUtil->generateConfirmationQuestion('Set Indexes on animal_id, father_id and mother_id in animal_migration_table? (y/n, default = no)');
		if($setParentIndexesInAnimalMigrationTable) {
			$conn->exec('CREATE INDEX animal_migration_table_animal_id_index ON public.animal_migration_table (animal_id)');
			$conn->exec('CREATE INDEX animal_migration_table_father_id_index ON public.animal_migration_table (father_id)');
			$conn->exec('CREATE INDEX animal_migration_table_mother_id_index ON public.animal_migration_table (mother_id)');
			$cmdUtil->writeln(['Indexes set on animal_id, father_id and mother_id in animal_migration_table','']);
		} else {
			$cmdUtil->writeln('*skipped*');
		}

		$cmdUtil->writeln(['Updating animalIds and parentIds in AnimalMigrationTable...','']);
		//AnimalIds might have been changed due to duplicateFixes
		AnimalMigrationTableFixer::updateAnimalIdsInMigrationTable($cmdUtil, $conn);
		AnimalMigrationTableFixer::updateGenderInDatabaseInMigrationTable($cmdUtil, $conn);
		AnimalMigrationTableFixer::updateParentIdsInMigrationTable($cmdUtil, $conn);

		AnimalRepository::fixMissingAnimalTableExtentions($conn);
		
		foreach (['father' => 'ram', 'mother' => 'ewe'] as $parent => $type) {
			$cmdUtil->writeln(['Update missing parent_'.$parent.'_ids in animal','']);
			$updatedMothersCount = $conn->query('WITH rows AS (
					UPDATE animal
					SET parent_'.$parent.'_id = am.'.$parent.'_id
					FROM animal_migration_table AS am
					WHERE am.animal_id IS NOT NULL
						  AND (animal.parent_'.$parent.'_id IS NULL)
						  AND (am.'.$parent.'_id IS NOT NULL)
						  AND animal.id = am.animal_id
						  AND ( am.'.$parent.'_id IN (SELECT id FROM '.$type.') )
					RETURNING 1
					)
					SELECT COUNT(*) AS count FROM rows')->fetch()['count'];
			$resultMessage = $updatedMothersCount == 0 ? 'No parent_'.$parent.'_ids missing!': $updatedMothersCount.' parent_'.$parent.'_ids update!';
			$cmdUtil->writeln([$resultMessage]);
		}
		
	}
}