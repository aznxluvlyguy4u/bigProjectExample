<?php


namespace AppBundle\Migration;


use AppBundle\Entity\BreederNumberRepository;
use AppBundle\Enumerator\BreederNumberSourceType;
use AppBundle\Enumerator\ColumnType;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\NullChecker;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\Validator;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Output\OutputInterface;

class BreederNumberMigrator extends MigratorBase
{
	const FILENAME_CSV_EXPORT = 'breeder_number.csv';
	const TABLE_NAME_IN_SNAKE_CASE = 'breeder_number';

	/** @var BreederNumberRepository */
	private $breederNumberRepository;

	/** @var string */
	private $columnHeaders;

	/**
	 * BreederNumberMigrator constructor.
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
			case "breeder_number": return ColumnType::STRING;
			case "ubn_of_birth": return ColumnType::STRING;
			case "source": return ColumnType::STRING;
			default: return ColumnType::STRING;
		}
	}

	public function migrate()
	{
		$searchArray = $this->breederNumberRepository->getCurrentRecordsSearchArray();
		$breederNumbersFromMigrationTable = $this->getUbnsOfBirthByBreederNumberFromAnimalMigrationTable();

		$this->cmdUtil->setStartTimeAndPrintIt(count($this->data)+count($breederNumbersFromMigrationTable)+1, 1);

		$newCount = 0;
		$skipped = 0;
		foreach ($this->data as $record) {

			$breederNumber = StringUtil::padBreederNumberWithZeroes($record[1]);
			$ubnOfBirth = $record[8];
			//Skip iteration, if ubnOfBirth has an invalid format
			if(!Validator::hasValidUbnFormat($ubnOfBirth) || NullChecker::isNull($record[1])) {
				$this->cmdUtil->advanceProgressBar(1, 'New breederNumber records: '.$newCount.' | csv records skipped: '.$skipped);
				continue;
			}

			$persistNewRecord = false;
			if(!array_key_exists($breederNumber, $searchArray)) {
				$persistNewRecord = true;
				//Migrating data from CSV takes precedence over data generated from AnimalMigrationTable
			} elseif ($searchArray[$breederNumber] == BreederNumberSourceType::ANIMAL_MIGRATION_TABLE) {
				$persistNewRecord = true;
			}

			if($persistNewRecord) {
				$this->breederNumberRepository->insertNewRecordBySql($breederNumber, $ubnOfBirth, BreederNumberSourceType::CSV_IMPORT);
				$searchArray[$breederNumber] = BreederNumberSourceType::CSV_IMPORT;
				$newCount++;
			} else {
				$skipped++;
			}

			$this->cmdUtil->advanceProgressBar(1, 'New breederNumber csv records: '.$newCount.' | csv records skipped: '.$skipped);
		}

		$newCountFromMigrationTable = 0;
		$breederNumbers = array_keys($breederNumbersFromMigrationTable);
		foreach ($breederNumbers as $breederNumber) {
			if(!array_key_exists($breederNumber, $searchArray)) {
				$ubnOfBirth = $breederNumbersFromMigrationTable[$breederNumber];                $this->breederNumberRepository->insertNewRecordBySql($breederNumber, $ubnOfBirth, BreederNumberSourceType::ANIMAL_MIGRATION_TABLE);
				$searchArray[$breederNumber] = BreederNumberSourceType::ANIMAL_MIGRATION_TABLE;
				$newCountFromMigrationTable++;
			}
			$this->cmdUtil->advanceProgressBar(1, 'New breederNumber csv records: '.$newCount.' | csv records skipped: '.$skipped.' | new breederNumber migrationTable records: '.$newCountFromMigrationTable);
		}
		$this->cmdUtil->setEndTimeAndPrintFinalOverview();
	}


	/**
	 * @return array
	 * @throws \Doctrine\DBAL\DBALException
	 */
	private function getUbnsOfBirthByBreederNumberFromAnimalMigrationTable()
	{
		$sql = "SELECT pedigree_number, stn_origin, ubn_of_birth FROM animal_migration_table t
                WHERE pedigree_country_code = 'NL' AND t.ubn_of_birth NOTNULL AND t.stn_origin NOTNULL ";
		$results = $this->conn->query($sql)->fetchAll();

		$searchArray = [];
		foreach ($results as $result) {
			$pedigreeNumber = $result['pedigree_number'];
			if($pedigreeNumber != null && $pedigreeNumber != '') {
				$breederNumber = StringUtil::getBreederNumberFromPedigreeNumber($pedigreeNumber);
			} else {
				$stnOrigin = $result['stn_origin'];
				$breederNumber = StringUtil::getBreederNumberFromStnOrigin($stnOrigin);
			}
			$ubnOfBirth = $result['ubn_of_birth'];

			if(Validator::hasValidUbnFormat($ubnOfBirth)) {
				$searchArray[$breederNumber] = $ubnOfBirth;
			}
		}
		return $searchArray;
	}

}