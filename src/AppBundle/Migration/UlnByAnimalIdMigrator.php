<?php


namespace AppBundle\Migration;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Enumerator\ColumnType;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\NullChecker;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\StringUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Output\OutputInterface;

class UlnByAnimalIdMigrator extends MigratorBase
{
	const FILENAME_CSV_EXPORT = 'uln_by_animal_id.csv';
	const TABLE_NAME_IN_SNAKE_CASE = 'animal';

	const UPDATE_ANIMAL_MIGRATION_TABLE = true;
	const UPDATE_BATCH_SIZE = 10000;

	/** @var string */
	private $columnHeaders;

	/** @var array */
	private $newestUlnByOldUln;

	/**
	 * UlnByAnimalIdMigrator constructor.
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
		$this->columnHeaders = $columnHeaders;
	}


	public function exportToCsv()
	{
		if(!is_string($this->outputFolder) || !is_string(self::FILENAME_CSV_EXPORT)) { return; }

		$columnSeparator = ';';
		$rowSeparator = "\n"; //newLine
		$outputFilePath = $this->outputFolder.'/'.self::FILENAME_CSV_EXPORT;

		if($this->output != null) { $this->output->writeln('Retrieving data from animal table'); }

		$sql = "SELECT id, uln_country_code, uln_number FROM animal";
		$results = $this->conn->query($sql)->fetchAll();

		if($this->output != null) { $this->output->writeln('Data retrieved!'); }

		if(count($results) == 0) { return; }
		NullChecker::createFolderPathIfNull($this->outputFolder);

		$columnHeaders = array_keys($results[0]);
		$row = '';
		foreach ($columnHeaders as $key) {
			$row = $row.$key.$columnSeparator;
		}
		file_put_contents($outputFilePath, $row.$rowSeparator, FILE_APPEND);
		$this->output->writeln('Headers printed: '.$row);
		$this->output->writeln('Printing '.count($results).' rows');

		$this->cmdUtil->setStartTimeAndPrintIt(count($results),1);
		foreach ($results as $result) {
			$row = '';
			foreach ($columnHeaders as $key) {
				$value = $result[$key];
				if(is_bool($result[$key])) { $value = StringUtil::getBooleanAsString($value); }
				$row = $row.$value.$columnSeparator;
			}
			file_put_contents($outputFilePath, $row.$rowSeparator, FILE_APPEND);
			if($this->cmdUtil != null) { $this->cmdUtil->advanceProgressBar(1); }
		}
		$this->output->writeln('Csv exported!');
		$this->cmdUtil->setEndTimeAndPrintFinalOverview();
	}


	public function updateUlnsFromCsv()
	{
		//Create searchArrays
		$ulnCountryCodeByAnimalId = [];
		$ulnNumberByAnimalId = [];
		foreach($this->data as $result) {
			if(count($result) < 2) { continue; }

			$animalId = $result[0];
			$ulnCountryCode = $result[1];
			$ulnNumber = $result[2];

			$ulnCountryCodeByAnimalId[$animalId] = $ulnCountryCode;
			$ulnNumberByAnimalId[$animalId] = $ulnNumber;
		}

		$sql = "SELECT id, name FROM animal WHERE (uln_number ISNULL OR uln_country_code ISNULL)";
		$results = $this->conn->query($sql)->fetchAll();

		$totalCount = count($results);

		if($totalCount == 0) {
			$this->output->writeln('All animals have a ulnCountryCode and ulnNumber!');
			return;
		}

		$this->cmdUtil->setStartTimeAndPrintIt($totalCount,1);
		$this->newestUlnByOldUln = $this->declareTagReplaceRepository->getNewReplacementUlnSearchArray();

		$ulnNumbersUpdated = 0;
		$ulnNumbersMissing = 0;
		$ulnReplaced = 0;
		$ulnUpdateString = '';
		$migrationTableUpdateString = '';
		$loopCounter = 0;
		$ulnsToUpdateCount = 0;
		foreach ($results as $result) {
			$animalId = $result['id'];
			$vsmId = $result['name'];

			if(array_key_exists($animalId, $ulnCountryCodeByAnimalId) && array_key_exists($animalId, $ulnNumberByAnimalId)) {
				$ulnCountryCode = $ulnCountryCodeByAnimalId[$animalId];
				$ulnNumber = $ulnNumberByAnimalId[$animalId];

				//Get newest uln
				if(is_string($ulnCountryCode) && is_string($ulnNumber) ) {
					if (array_key_exists($ulnCountryCode . $ulnNumber, $this->newestUlnByOldUln)) {
						$ulnParts = $this->newestUlnByOldUln[$ulnCountryCode . $ulnNumber];
						if (is_array($ulnParts)) {
							$ulnCountryCode = Utils::getNullCheckedArrayValue(Constant::ULN_COUNTRY_CODE_NAMESPACE, $ulnParts);
							$ulnNumber = Utils::getNullCheckedArrayValue(Constant::ULN_NUMBER_NAMESPACE, $ulnParts);
						}
					}
				}

				$ulnUpdateString = $ulnUpdateString."('".$ulnCountryCode."','".$ulnNumber."',".$animalId."),";

				if(self::UPDATE_ANIMAL_MIGRATION_TABLE && $vsmId != null){
					$migrationTableUpdateString = $migrationTableUpdateString." vsm_id = '".$vsmId."' OR";
				}

				$ulnsToUpdateCount++;

				$loopCounter++;

				//Update fathers
				if(($totalCount == $loopCounter || ($ulnsToUpdateCount%self::UPDATE_BATCH_SIZE == 0 && $ulnsToUpdateCount != 0))
					&& $ulnUpdateString != '') {
					$ulnUpdateString = rtrim($ulnUpdateString, ',');
					$sql = "UPDATE animal as a SET uln_country_code = c.found_uln_country_code, uln_number = c.found_uln_number
							FROM (VALUES ".$ulnUpdateString.") as c(found_uln_country_code, found_uln_number, id) WHERE c.id = a.id ";
					$this->conn->exec($sql);
					//Reset batch values
					$ulnUpdateString = '';
					$ulnNumbersUpdated += $ulnsToUpdateCount;
					$ulnsToUpdateCount = 0;

					if($migrationTableUpdateString != '') {
						$migrationTableUpdateString = rtrim($migrationTableUpdateString, 'OR');
						"UPDATE animal_migration_table SET is_record_migrated = TRUE WHERE ".$migrationTableUpdateString;
						$this->conn->exec($sql);
						$migrationTableUpdateString = '';
					}
				}
			} else {
				$ulnNumbersMissing++;
			}
			$this->cmdUtil->advanceProgressBar(1, 'missingUlnNumbers updated|batch|missing: '.$ulnNumbersUpdated.'|'.$ulnsToUpdateCount.'|'.$ulnNumbersMissing.' - ulnReplaced: '.$ulnReplaced);
		}
		$this->cmdUtil->setEndTimeAndPrintFinalOverview();
	}





	/**
	 * @param string $columnHeader
	 * @return string
	 */
	private function getColumnType($columnHeader)
	{
		switch ($columnHeader) {
			case "id": return ColumnType::INTEGER;
			case "uln_country_code": return ColumnType::STRING;
			case "uln_number": return ColumnType::STRING;
			default: return ColumnType::STRING;
		}
	}


	public function fixUlnsByDeclares()
	{

		$sql = "SELECT a.id, d.uln_number, d.uln_country_code, a.name
				FROM animal a
				  INNER JOIN declare_arrival d ON d.animal_id = a.id
				WHERE (a.uln_number ISNULL OR a.uln_country_code ISNULL)
				UNION
				SELECT a.id, d.uln_number, d.uln_country_code, a.name
				FROM animal a
				  INNER JOIN declare_export d ON d.animal_id = a.id
				WHERE (a.uln_number ISNULL OR a.uln_country_code ISNULL)
				UNION
				SELECT a.id, d.uln_number, d.uln_country_code, a.name
				FROM animal a
				  INNER JOIN declare_import d ON d.animal_id = a.id
				WHERE (a.uln_number ISNULL OR a.uln_country_code ISNULL)
				UNION
				SELECT a.id, d.uln_number, d.uln_country_code, a.name
				FROM animal a
				  INNER JOIN declare_depart d ON d.animal_id = a.id
				WHERE (a.uln_number ISNULL OR a.uln_country_code ISNULL)
				UNION
				SELECT a.id, d.uln_number, d.uln_country_code, a.name
				FROM animal a
				  INNER JOIN declare_loss d ON d.animal_id = a.id
				WHERE (a.uln_number ISNULL OR a.uln_country_code ISNULL)
				GROUP BY a.id, d.uln_country_code, d.uln_number, a.name";
		$results = $this->conn->query($sql)->fetchAll();

		$nullcheckString = 'All animals related to declares have a ulnCountryCode and ulnNumber';
		$this->processUlnFix($results, $nullcheckString);
	}


	public function fixMissingUlnsByVsmIdInMigrationTable()
	{

		$sql = "SELECT a.id, name, t.uln_country_code, t.uln_number FROM animal a
				  INNER JOIN animal_migration_table t ON a.name = cast(t.vsm_id as varchar(255))
				WHERE (a.uln_country_code ISNULL OR a.uln_number ISNULL) AND a.name NOTNULL
				AND t.uln_country_code NOTNULL AND t.uln_number NOTNULL";
		$results = $this->conn->query($sql)->fetchAll();

		$nullcheckString = 'All animals with ulnCountryCode and ulnNumber data from the migrationTable already have them';
		$this->processUlnFix($results, $nullcheckString);
	}


	/**
	 * @param array $results
	 * @param string $nullcheckString
	 * @throws \Doctrine\DBAL\DBALException
	 */
	private function processUlnFix(array $results, $nullcheckString)
	{
		$totalCount = count($results);

		if($totalCount == 0) {
			$this->output->writeln($nullcheckString);
			return;
		}

		$this->cmdUtil->setStartTimeAndPrintIt($totalCount, 1);
		$this->newestUlnByOldUln = $this->declareTagReplaceRepository->getNewReplacementUlnSearchArray();

		$ulnNumbersUpdated = 0;
		$ulnNumbersMissing = 0;
		$ulnReplaced = 0;
		$ulnUpdateString = '';
		$migrationTableUpdateString = '';
		$loopCounter = 0;
		$ulnsToUpdateCount = 0;
		foreach ($results as $result) {
			$animalId = $result['id'];
			$vsmId = $result['name'];
			$ulnCountryCode = $result['uln_country_code'];
			$ulnNumber = $result['uln_number'];

			//Get newest uln
			if(is_string($ulnCountryCode) && is_string($ulnNumber) ) {
				if (array_key_exists($ulnCountryCode . $ulnNumber, $this->newestUlnByOldUln)) {
					$ulnParts = $this->newestUlnByOldUln[$ulnCountryCode . $ulnNumber];
					if (is_array($ulnParts)) {
						$ulnCountryCode = Utils::getNullCheckedArrayValue(Constant::ULN_COUNTRY_CODE_NAMESPACE, $ulnParts);
						$ulnNumber = Utils::getNullCheckedArrayValue(Constant::ULN_NUMBER_NAMESPACE, $ulnParts);
					}
				}
			}

			$ulnUpdateString = $ulnUpdateString."('".$ulnCountryCode."','".$ulnNumber."',".$animalId."),";

			if(self::UPDATE_ANIMAL_MIGRATION_TABLE && $vsmId != null){
				$migrationTableUpdateString = $migrationTableUpdateString." vsm_id = '".$vsmId."' OR";
			}

			$ulnsToUpdateCount++;

			$loopCounter++;

			//Update fathers
			if(($totalCount == $loopCounter || ($ulnsToUpdateCount%self::UPDATE_BATCH_SIZE == 0 && $ulnsToUpdateCount != 0))
				&& $ulnUpdateString != '') {
				$ulnUpdateString = rtrim($ulnUpdateString, ',');
				$sql = "UPDATE animal as a SET uln_country_code = c.found_uln_country_code, uln_number = c.found_uln_number
							FROM (VALUES ".$ulnUpdateString.") as c(found_uln_country_code, found_uln_number, id) WHERE c.id = a.id ";
				$this->conn->exec($sql);
				//Reset batch values
				$ulnUpdateString = '';
				$ulnNumbersUpdated += $ulnsToUpdateCount;
				$ulnsToUpdateCount = 0;

				if($migrationTableUpdateString != '') {
					$migrationTableUpdateString = rtrim($migrationTableUpdateString, 'OR');
					"UPDATE animal_migration_table SET is_record_migrated = TRUE WHERE ".$migrationTableUpdateString;
					$this->conn->exec($sql);
					$migrationTableUpdateString = '';
				}
			}

			$this->cmdUtil->advanceProgressBar(1, 'missingUlnNumbers updated|batch|missing: '.$ulnNumbersUpdated.'|'.$ulnsToUpdateCount.'|'.$ulnNumbersMissing.' - ulnReplaced: '.$ulnReplaced);
		}
		$this->cmdUtil->setEndTimeAndPrintFinalOverview();
	}
}