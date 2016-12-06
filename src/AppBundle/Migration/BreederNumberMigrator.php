<?php


namespace AppBundle\Migration;


use AppBundle\Enumerator\ColumnType;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\SqlUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Output\OutputInterface;

class BreederNumberMigrator extends MigratorBase
{
	const FILENAME_CSV_EXPORT = 'breeder_number.csv';
	const TABLE_NAME_IN_SNAKE_CASE = 'breeder_number';


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

}