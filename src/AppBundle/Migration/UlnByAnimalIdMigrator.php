<?php


namespace AppBundle\Migration;


use AppBundle\Enumerator\ColumnType;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\NullChecker;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\StringUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Output\OutputInterface;

class UlnByAnimalIdMigrator extends MigratorBase
{
	const FILENAME_CSV_EXPORT = 'uln_by_animal_id.csv';
	const TABLE_NAME_IN_SNAKE_CASE = 'animal';


	/** @var string */
	private $columnHeaders;

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
		parent::__construct($this->cmdUtil, $em, $outputInterface, $data, $rootDir);
		$this->columnHeaders = $columnHeaders;
	}


	public function exportToCsv()
	{
		if(!is_string($this->outputFolder) || !is_string(self::FILENAME_CSV_EXPORT)) { return; }

		$columnSeparator = ';';
		$rowSeparator = "\n"; //newLine
		$outputFilePath = $this->outputFolder.'/'.self::FILENAME_CSV_EXPORT;

		if($this->output != null) { $this->output->writeln('Retrieving data from animal table'); }

		$sql = "SELECT id, uln_number, uln_country_code FROM animal";
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
		$columnTypes = [];

		foreach ($this->columnHeaders as $columnHeader) {
			$columnTypes[] = $this->getColumnType($columnHeader);
		}

//		SqlUtil::importFromCsv($this->em, 'animal', $this->columnHeaders, $columnTypes, $this->data, $this->output, $this->cmdUtil);
	}


	/**
	 * @param string $columnHeader
	 * @return string
	 */
	private function getColumnType($columnHeader)
	{
		switch ($columnHeader) {
			case "id": return ColumnType::INTEGER;
			case "primary_vsm_id": return ColumnType::STRING;
			case "secondary_vsm_id": return ColumnType::STRING;
			default: return ColumnType::STRING;
		}
	}

}