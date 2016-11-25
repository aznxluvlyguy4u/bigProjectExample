<?php


namespace AppBundle\Migration;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\AnimalMigrationTable;
use AppBundle\Entity\AnimalMigrationTableRepository;
use AppBundle\Entity\BreederNumber;
use AppBundle\Entity\BreederNumberRepository;
use AppBundle\Entity\DeclareTagReplace;
use AppBundle\Entity\DeclareTagReplaceRepository;
use AppBundle\Entity\PedigreeRegister;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\Specie;
use AppBundle\Util\BreedCodeUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\GenderChanger;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Util\Translation;
use AppBundle\Util\Validator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Output\OutputInterface;

class AnimalTableMigrator extends MigratorBase
{
	const PRINT_OUT_INVALID_UBNS_OF_BIRTH = true;
	const UBNS_PER_ROW = 8;
	const FILENAME_CORRECTED_CSV = '2016nov_gecorrigeerde_diertabel.csv';
	const FILENAME_INCORRECT_ULNS = 'incorrect_ulns.csv';
	const FILENAME_INCORRECT_GENDERS = 'incorrect_genders.csv';

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


	/** @var AnimalMigrationTableRepository */
	private $animalMigrationTableRepository;

	/** @var BreederNumberRepository */
	private $breederNumberRepository;

	/**
	 * MyoMaxMigrator constructor.
	 * @param CommandUtil $cmdUtil
	 * @param ObjectManager $em
	 * @param OutputInterface $outputInterface
	 * @param array $data
	 * @param string $rootDir
	 */
	public function __construct(CommandUtil $cmdUtil, ObjectManager $em, OutputInterface $outputInterface, array $data, $rootDir)
	{
		parent::__construct($cmdUtil, $em, $outputInterface, $data, $rootDir);
		$this->animalMigrationTableRepository = $this->em->getRepository(AnimalMigrationTable::class);
		$this->breederNumberRepository = $this->em->getRepository(BreederNumber::class);
	}


	public function fixValuesInAnimalMigrationTable()
	{
		//NOTE! The order of operations here is important!
		$this->deleteTestAnimals();
		$this->fixScientificNotationInStnAndUlnOrigin();
		$this->fixGenders();
		$this->getUbnOfBirthFromUln();
		$this->fixAnimalOrderNumbers();
		$this->fixMissingAnimalOrderNumbers();
		$this->fixMissingUlns();
		$this->findMissingFathers();
		$this->fixAnimalOrderNumbers();

		//Fix breedCodes last, because it might take a while
		$breedCodeUtil = new BreedCodeUtil($this->em, $this->cmdUtil);
		$breedCodeUtil->fixBreedCodes();
	}
	

	public function importAnimalTableCsvFileIntoDatabase()
	{

		//TODO Regender animals to Ewe/Ram before setting parents-children

        //Search Arrays
		$animalIdsByVsmId = $this->animalRepository->getAnimalPrimaryKeysByVsmId();

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
			$ulnParts = $this->parseUln($record[3]);
			$ulnCountryCode = StringUtil::getNullAsStringOrWrapInQuotes($ulnParts[JsonInputConstant::ULN_COUNTRY_CODE]);
			$ulnNumber = StringUtil::getNullAsStringOrWrapInQuotes($ulnParts[JsonInputConstant::ULN_NUMBER]);

			if($ulnCountryCode == "'XD'") { $animalsSkipped++; $this->cmdUtil->advanceProgressBar(1); continue; } // These are testAnimals and should be skipped

            $vsmId = intval($record[0]);

            if(array_key_exists($vsmId, $processedAnimals)) { $animalsAlreadyInDatabase++; $this->cmdUtil->advanceProgressBar(1); continue; }

            $stnImport = StringUtil::getNullAsStringOrWrapInQuotes($record[1]);
            $stnParts = $this->parseStn($record[1]);
            $pedigreeCountryCode = StringUtil::getNullAsStringOrWrapInQuotes($stnParts[JsonInputConstant::PEDIGREE_COUNTRY_CODE]);
            $pedigreeNumber = StringUtil::getNullAsStringOrWrapInQuotes($stnParts[JsonInputConstant::PEDIGREE_NUMBER]);

            $animalOrderNumber = 'NULL';
            if($record[2] != null && $record[2] != '') {
                $animalOrderNumber = StringUtil::getNullAsStringOrWrapInQuotes(StringUtil::padAnimalOrderNumberWithZeroes($record[2]));
            }

			$nickName = StringUtil::getNullAsStringOrWrapInQuotes(utf8_encode(StringUtil::escapeSingleApostrophes($record[4])));
            $fatherVsmId = SqlUtil::getNullCheckedValueForSqlQuery($record[5], false);
            $motherVsmId = SqlUtil::getNullCheckedValueForSqlQuery($record[6], false);
            $genderInFile = StringUtil::getNullAsStringOrWrapInQuotes($this->parseGender($record[7]));
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

			$animalId = StringUtil::getNullAsString($this->findAnimalIdOfVsmId($vsmId, $ulnCountryCode, $ulnNumber, $animalIdsByVsmId));
			$fatherId = StringUtil::getNullAsString($this->findAnimalIdOfVsmId(intval($record[5]), null, null, $animalIdsByVsmId));
			$motherId = StringUtil::getNullAsString($this->findAnimalIdOfVsmId(intval($record[6]), null, null, $animalIdsByVsmId));

            $genderInDatabase = SqlUtil::getSearchArrayCheckedValueForSqlQuery($vsmId, $genderInDatabaseByVsmIdSearchArray, true);


			$sql = "INSERT INTO animal_migration_table (id, vsm_id, animal_id, uln_origin, stn_origin, uln_country_code, uln_number, animal_order_number,
						pedigree_country_code, pedigree_number, nick_name, father_vsm_id, father_id, mother_vsm_id, mother_id, gender_in_file,
						gender_in_database,date_of_birth,breed_code,ubn_of_birth,location_of_birth_id,pedigree_register_id,breed_type,scrapie_genotype
						)VALUES(nextval('measurement_id_seq'),".$vsmId.",".$animalId.",".$uln.",".$stnImport.",".$ulnCountryCode.",".$ulnNumber.",".$animalOrderNumber.",".$pedigreeCountryCode.",".$pedigreeNumber.",".$nickName.",".$fatherVsmId.",".$fatherId.",".$motherVsmId.",".$motherId.",".$genderInFile.",".$genderInDatabase.",".$dateOfBirthString.",".$breedCode.",".$ubnOfBirth.",".$locationOfBirth.",".$pedigreeRegisterId.",".$breedType.",".$scrapieGenotype.")";
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
	 * @param ArrayCollection $animalIdsByVsmId
	 * @return int
	 */
	private function findAnimalIdOfVsmId($vsmId, $ulnCountryCode = null, $ulnNumber = null, $animalIdsByVsmId = null)
	{
        if($animalIdsByVsmId != null) {
            if($animalIdsByVsmId->containsKey($vsmId)) {
                return intval($animalIdsByVsmId->get($vsmId));
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

		$sql = "UPDATE animal_migration_table a SET deleted_uln_origin = uln_origin, deleted_stn_origin = stn_origin,
				  uln_origin = NULL, stn_origin = NULL, is_uln_updated = TRUE, is_stn_updated = TRUE
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
			$sql = "UPDATE animal_migration_table SET deleted_stn_origin = stn_origin, stn_origin = '".$stnOrigin."'
					WHERE id = ".$stnResult['id'];
			$this->conn->exec($sql);
		}
		$this->output->writeln($stnCount.' scientificNotations in stn_origin fixed');

		foreach ($ulnResults as $ulnResult) {
			$ulnOrigin = strval(intval(floatval(strtr($ulnResult['uln_origin'],[',' =>'.']))));
			$sql = "UPDATE animal_migration_table SET deleted_uln_origin = uln_origin, uln_origin = '".$ulnOrigin."'
					WHERE id = ".$ulnResult['id'];
			$this->conn->exec($sql);
		}
		$this->output->writeln($ulnCount.' scientificNotations in uln_origin fixed');
	}


	public function migrate()
	{
		//TODO
		/*
		 * NOTE!!!! FIRST UPDATE THE ANIMALS IN THE DATABASE TO THE CORRECT GENDER !!!!
		 * The correct gender is in the gender_in_file. Read that one to the database
		 * If EWE don't import children as father
		 * If RAM don't import children as mother
		 */
		
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
		 * 4. Fix database genders based on the genders in the CSV file
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
		 * M2F = (after check in step 2), fix
		 * F2M = (after check in step 3), fix
		 * For M2F and F2M, use the gender in the CSV file,
		 * which will hopefully not cause conflicts because those animals don't have any children
		 * as the gender opposite from the on in the CSV file.
		 *
		 * TODO fix animals that are simultaneously a mother and father
		 */

		//Create searchArrays
		$animalIdByVsmId = $this->animalRepository->getAnimalPrimaryKeysByVsmIdArray();

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
		
		$animalIdsOfSimultaneousMotherAndFather = [];

		$this->output->writeln(['===== VSM in CSV =====' ,
			'NEUTERS: '.count($vsmNeuters).' MALES: '.count($vsmMales).' FEMALES: '.count($vsmFemales).' MOTHERS: '.count($vsmMothers).' FATHERS: '.count($vsmFathers)]);

		//Check NEUTERs in csv
		$areAllNeutersGenderless = true;
		$allNeuterHaveOnlyOneGender = true;
		foreach ($vsmNeuters as $vsmId) {
			$isVsmMother = array_key_exists($vsmId, $vsmMothers);
			$isVsmFather = array_key_exists($vsmId, $vsmFathers);

			$isAnimalIdMother = false;
			$isAnimalIdFather = false;
			if(array_key_exists($vsmId, $animalIdByVsmId)) {
				$animalId = $animalIdByVsmId[$vsmId];
				if($animalId != null) {
					$isAnimalIdMother = array_key_exists($animalId, $animalIdMothers);
					$isAnimalIdFather = array_key_exists($animalId, $animalIdFathers);
				}
			}

			if($isVsmMother && $isVsmFather) {
				$errorMessage = $vsmId.'; NEUTER is both mother & father in csv file'.$this->getChildrenCountByParentTypeAsString($vsmId, $animalIdByVsmId);
					file_put_contents($this->outputFolder.'/'.self::FILENAME_INCORRECT_GENDERS, $errorMessage."\n", FILE_APPEND);
				$this->output->writeln($errorMessage);
				$areAllNeutersGenderless = false;
				$allNeuterHaveOnlyOneGender = false;
				$animalIdsOfSimultaneousMotherAndFather[$vsmId] = $vsmId;

			} else if($isVsmMother) {
				if(!$isAnimalIdFather) {
					/*
					 * DON'T UPDATE GENDER. ASSUME GENDER IS CORRECT AND DON'T IMPORT THE CHILDREN
					 * SEE EMAIL REINARD 2016-11-24
					 */
				} else {
					$errorMessage = $vsmId.'; NEUTER is a mother in csv and father in db';
					file_put_contents($this->outputFolder.'/'.self::FILENAME_INCORRECT_GENDERS, $errorMessage."\n", FILE_APPEND);
					$this->output->writeln($errorMessage);
				}

				$areAllNeutersGenderless = false;
			} else if($isVsmFather) {
				if(!$isAnimalIdMother) {
					/*
					 * DON'T UPDATE GENDER. ASSUME GENDER IS CORRECT AND DON'T IMPORT THE CHILDREN
					 * SEE EMAIL REINARD 2016-11-24
					 */
				} else {
					$errorMessage = $vsmId.'; NEUTER is a father in csv and mother in db';
					file_put_contents($this->outputFolder.'/'.self::FILENAME_INCORRECT_GENDERS, $errorMessage."\n", FILE_APPEND);
					$this->output->writeln($errorMessage);
				}

				$areAllNeutersGenderless = false;
			}
		}


		//Check FEMALEs in csv
		$areAllFemalesOnlyMothers = true;
		foreach ($vsmFemales as $vsmId) {
			$isVsmMother = array_key_exists($vsmId, $vsmMothers);
			$isVsmFather = array_key_exists($vsmId, $vsmFathers);

			$isAnimalIdMother = false;
			$isAnimalIdFather = false;
			if(array_key_exists($vsmId, $animalIdByVsmId)) {
				$animalId = $animalIdByVsmId[$vsmId];
				if($animalId != null) {
					$isAnimalIdMother = array_key_exists($animalId, $animalIdMothers);
					$isAnimalIdFather = array_key_exists($animalId, $animalIdFathers);
				}
			}

			if($isVsmMother && $isVsmFather) {
				$errorMessage = $vsmId.'; FEMALE is both mother & father in csv file'.$this->getChildrenCountByParentTypeAsString($vsmId, $animalIdByVsmId);
				file_put_contents($this->outputFolder.'/'.self::FILENAME_INCORRECT_GENDERS, $errorMessage."\n", FILE_APPEND);
				$this->output->writeln($errorMessage);
				$areAllFemalesOnlyMothers = false;
				$animalIdsOfSimultaneousMotherAndFather[$vsmId] = $vsmId;

			} else if($isVsmFather) {
				if(!$isAnimalIdMother) {
					$sql = "UPDATE animal_migration_table SET corrected_gender = gender_in_file, gender_in_file = '".GenderType::MALE."' WHERE vsm_id = ".$vsmId;
					$this->conn->exec($sql);
				} else {
					$errorMessage = $vsmId.'; FEMALE is a father in csv and mother in db';
					file_put_contents($this->outputFolder.'/'.self::FILENAME_INCORRECT_GENDERS, $errorMessage."\n", FILE_APPEND);
					$this->output->writeln($errorMessage);
				}
				$areAllFemalesOnlyMothers = false;
			}
		}


		//Check MALEs in csv
		$areAllMalesOnlyFathers = true;
		foreach ($vsmMales as $vsmId) {
			$isVsmMother = array_key_exists($vsmId, $vsmMothers);
			$isVsmFather = array_key_exists($vsmId, $vsmFathers);

			$isAnimalIdMother = false;
			$isAnimalIdFather = false;
			if(array_key_exists($vsmId, $animalIdByVsmId)) {
				$animalId = $animalIdByVsmId[$vsmId];
				if($animalId != null) {
					$isAnimalIdMother = array_key_exists($animalId, $animalIdMothers);
					$isAnimalIdFather = array_key_exists($animalId, $animalIdFathers);
				}
			}

			if($isVsmMother && $isVsmFather) {
				$errorMessage = $vsmId.'; MALE is both mother & father in csv file'.$this->getChildrenCountByParentTypeAsString($vsmId, $animalIdByVsmId);
				file_put_contents($this->outputFolder.'/'.self::FILENAME_INCORRECT_GENDERS, $errorMessage."\n", FILE_APPEND);
				$this->output->writeln($errorMessage);
				$areAllMalesOnlyFathers = false;
				$animalIdsOfSimultaneousMotherAndFather[$vsmId] = $vsmId;

			} else if($isVsmMother) {
				if(!$isAnimalIdFather) {
					$sql = "UPDATE animal_migration_table SET corrected_gender = gender_in_file, gender_in_file = '" . GenderType::FEMALE . "' WHERE vsm_id = ".$vsmId;
					$this->conn->exec($sql);
				} else {
					$errorMessage = $vsmId.'; MALE is a mother in csv and father in db';
					file_put_contents($this->outputFolder.'/'.self::FILENAME_INCORRECT_GENDERS, $errorMessage."\n", FILE_APPEND);
					$this->output->writeln($errorMessage);
				}
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


	private function fixMissingUlns()
	{
		//SearchArrays
		$ubnsOfBirthByBreederNumber = $this->breederNumberRepository->getUbnOfBirthByBreederNumberSearchArray();
		$breederNumberByUbnsOfBirth = array_flip($ubnsOfBirthByBreederNumber);
		$usedUlnNumbers = $this->animalMigrationTableRepository->getExistingUlnsInAnimalAndAnimalMigrationTables();
		$usedPedigreeNumbers = $this->animalMigrationTableRepository->getExistingPedigreeNumbersInAnimalAndAnimalMigrationTables();
		
		$this->output->writeln('=== Delete incorrect ulns and stns ===');
		$usedPedigreeNumbers = $this->deleteIncorrectUlnsAndStns($usedPedigreeNumbers, $breederNumberByUbnsOfBirth);	
		
		$this->output->writeln('=== Fix missing ulns by pedigreeNumber and ubnOfBirth ===');
		$usedUlnNumbers = $this->fixMissingUlnsByPedigreeNumberAndUbnOfBirth($usedUlnNumbers);
		$this->output->writeln('=== Fix missing ulns by pedigreeNumber only ===');
		$usedUlnNumbers = $this->fixMissingUlnsByPedigreeNumberOnly($ubnsOfBirthByBreederNumber, $usedUlnNumbers);

		$count = $this->animalMigrationTableRepository->countAnimalOrderNumbersNotMatchingUlnNumbers();
		$this->output->writeln("=== Fix ".$count." animalOrderNumbers to match updated ulnNumbers ===");
		$this->animalMigrationTableRepository->fixAnimalOrderNumberToMatchUlnNumber();
	}


	/**
	 * @param array $usedPedigreeNumbers
	 * @param array $breederNumberByUbnsOfBirth
	 * @return array
	 * @throws \Doctrine\DBAL\DBALException
	 */
	private function deleteIncorrectUlnsAndStns($usedPedigreeNumbers, $breederNumberByUbnsOfBirth)
	{
		//Delete XD animals. They are test animals.
		$sql = "SELECT COUNT(*) FROM animal_migration_table WHERE SUBSTR(uln_origin, 1, 2) = 'XD'";
		$count = $this->conn->query($sql)->fetch()['count'];
		if($count == 0) {
			$this->output->writeln('All XD testanimal have already been deleted');
		} else {
			$sql = "UPDATE animal_migration_table SET deleted_uln_origin = uln_origin, uln_origin = NULL
				    WHERE SUBSTR(uln_origin, 1, 2) = 'XD'";
			$this->conn->exec($sql);
			$this->output->writeln($count.'');
		}
		
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
			$sql = "SELECT * FROM animal_migration_table 
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

		$message = $this->parseChildrenCountArray('', $results, 'with');

		if(array_key_exists($vsmId, $animalIdByVsmId)) {
			$animalId = $animalIdByVsmId[$vsmId];
			$sql = "SELECT COUNT(*), 'father' as type FROM animal WHERE parent_father_id = ".$animalId."
					UNION
					SELECT COUNT(*), 'mother' as type FROM animal WHERE parent_mother_id = ".$animalId;
			$results = $this->conn->query($sql)->fetchAll();
			
			$message = $this->parseChildrenCountArray($message, $results, 'in database has');
		}
		
		$sql = "SELECT CONCAT(uln_country_code,' ',uln_number) as uln, CONCAT(pedigree_country_code,' ',pedigree_number) as stn, vsm_id FROM animal_migration_table
				WHERE vsm_id = ".$vsmId;
		$result = $this->conn->query($sql)->fetch();
		$id = '; uln: '.$result['uln'].'; stn: '.$result['stn'];

		return $message.$id;
	}


	/**
	 * @param string $message
	 * @param string $prefix
	 * @param array $results
	 * @return string
	 */
	private function parseChildrenCountArray($message, array $results, $prefix)
	{
		$count0 = $results[0]['count'];
		$count1 = $results[1]['count'];
		$type0 = $results[0]['type'];
		$type1 = $results[1]['type'];

		if(($count0 + $count1) > 0) {
			$message = $message.' '.$prefix.' ';
			if($count0 > 0) {
				$message = $message.$count0.' children as '.$type0;
			}
			if($count0 > 0 && $count1 > 0) {
				$message = $message.' and ';
			}
			if($count1 > 0) {
				$message = $message.$count1.' children as '.$type1;
			}
		}
		return $message;
	}


	public function verifyData()
	{
		$neutersByVsmId = $this->getNeutersByVsmId();

		$vsmIdCollection = [];
		$stnImportCollection = [];
		$animalOrderNumberImportCollection = [];
		$ulnImportCollection = [];
		$nickNameCollection = [];
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
			$nickName = $record[4];
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
			$nickNameCollection[] = $nickName;
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

		$nickNames = [];
		foreach ($nickNameCollection as $nickName) {
			if($nickName != null && $nickName != '') { $nickNames[] = $nickName; }
		}
		$this->output->writeln('nickNames: '.count($nickNames));

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
	 * @param string $gender
	 * @return string
	 */
	private function parseGender($gender)
	{
		//The only genders in the file are 'M' and 'V'
		switch ($gender) {
			case GenderType::M: return GenderType::MALE;
			case GenderType::V: return GenderType::FEMALE;
			default: return GenderType::NEUTER;
		}
	}


	/**
	 *
	 * @param string $ulnString
	 * @return array
	 */
	private function parseUln($ulnString)
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
     * @param string $stnString
     * @return array
     */
	private function parseStn($stnString)
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
			$gender = $this->parseGender($record[7]);

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
				GenderChanger::changeNeuterToFemaleBySql($this->em, $animalId);
				$this->cmdUtil->advanceProgressBar(1, 'id|gender : ' . $animalId . '|' . GenderType::FEMALE);
				$count++;
			}

			$animalIdsMale = array_keys($animalIdsOfNeutersToMale);
			foreach ($animalIdsMale as $animalId) {
				GenderChanger::changeNeuterToMaleBySql($this->em, $animalId);
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
     * Example
    $this->writeCorrectedCsvRecord($vsmId . ';' . $animalId  . ';' .$uln. ';' .$stnImport. ';' . $ulnCountryCode . ';' . $ulnNumber . ';' . $animalOrderNumber . ';' . $pedigreeCountryCode.';'.$pedigreeNumber.';'.$nickName.';'.$fatherVsmId.';'.$fatherId.';'.$motherVsmId.';'.$motherId
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


}