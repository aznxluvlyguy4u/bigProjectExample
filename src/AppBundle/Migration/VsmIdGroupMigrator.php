<?php


namespace AppBundle\Migration;


use AppBundle\Enumerator\ColumnType;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\SqlUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Output\OutputInterface;

class VsmIdGroupMigrator extends MigratorBase
{
	const FILENAME_CSV_EXPORT = 'vsm_id_group.csv';
	const TABLE_NAME_IN_SNAKE_CASE = 'vsm_id_group';


	/** @var string */
	private $columnHeaders;

	/**
	 * VsmIdGroupMigrator constructor.
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
			case "primary_vsm_id": return ColumnType::STRING;
			case "secondary_vsm_id": return ColumnType::STRING;
			default: return ColumnType::STRING;
		}
	}

}