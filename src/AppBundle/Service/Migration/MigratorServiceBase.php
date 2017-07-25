<?php


namespace AppBundle\Service\Migration;


use AppBundle\Component\Builder\CsvOptions;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\CsvParser;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\FilesystemUtil;
use AppBundle\Util\SqlBatchProcessorWithProgressBar;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class MigratorServiceBase
 */
abstract class MigratorServiceBase
{
    const DEFAULT_OPTION = 0;
    const DEVELOPER_PRIMARY_KEY = 2151; //Used as the person that creates and edits imported data
    const BATCH_SIZE = 10000;
    const IMPORT_SUB_FOLDER = '';
    const BLANK_DATE_FILLER = '1899-01-01';

    /** @var EntityManagerInterface|ObjectManager */
    protected $em;
    /** @var Connection */
    protected $conn;
    /** @var CommandUtil */
    protected $cmdUtil;
    /** @var string */
    protected $rootDir;
    /** @var string */
    protected $batchSize;
    /** @var SqlBatchProcessorWithProgressBar */
    protected $sqlBatchProcessor;

    /** @var array */
    protected $filenames;
    /** @var CsvOptions */
    protected $csvOptions;
    /** @var string */
    protected $importSubFolder;

    /** @var array */
    protected $data;


    /**
     * MigratorServiceBase constructor.
     * @param ObjectManager $em
     * @param int $batchSize
     * @param string $importSubFolder
     * @param string $rootDir
     */
    public function __construct(ObjectManager $em, $batchSize = self::BATCH_SIZE, $importSubFolder = self::IMPORT_SUB_FOLDER, $rootDir)
    {
        $this->em = $em;
        $this->conn = $this->em->getConnection();
        $this->batchSize = $batchSize;

        $this->data = [];
        $this->rootDir = $rootDir;
        $this->importSubFolder = $importSubFolder;

        $this->setCsvOptions();
    }


    private function setCsvOptions()
    {
        $this->csvOptions = (new CsvOptions())
            ->appendDefaultInputFolder($this->importSubFolder)
            ->appendDefaultOutputFolder($this->importSubFolder)
            ->ignoreFirstLine()
            ->setSemicolonSeparator()
        ;
    }


    /**
     * @return CsvOptions
     */
    protected function getCsvOptions()
    {
        if ($this->csvOptions === null) {
            $this->setCsvOptions();
        }
        return $this->csvOptions;
    }


    /**
     * @param CommandUtil $cmdUtil
     */
    protected function run(CommandUtil $cmdUtil)
    {
        if($this->cmdUtil === null) { $this->cmdUtil = $cmdUtil; }
        $this->cmdUtil->writeln(DoctrineUtil::getDatabaseHostAndNameString($this->em));
        $this->cmdUtil->writeln('');

        $this->sqlBatchProcessor = new SqlBatchProcessorWithProgressBar($this->conn, $this->cmdUtil, $this->batchSize);

        if(is_string($this->rootDir) && $this->rootDir !== '') {
            //Setup folders if missing
            FilesystemUtil::createFolderPathsFromCsvOptionsIfNull($this->rootDir, $this->csvOptions);
        }
    }


    /**
     * @param $line
     */
    protected function writeLn($line)
    {
        $line = is_string($line) ? TimeUtil::getTimeStampNow() . ': ' .$line : $line;
        $this->cmdUtil->writeln($line);
    }


    /**
     * @param string $filename
     * @return array
     */
    protected function parseCSV($filename) {

        $this->csvOptions->setFileName($this->filenames[$filename]);
        return CsvParser::parse($this->csvOptions);
    }


    /**
     * @param string $title
     * @param string $sql
     * @return int count
     */
    protected function updateBySql($title, $sql)
    {
        $this->writeLn($title);
        $count = SqlUtil::updateWithCount($this->conn, $sql);
        $prefix = $count === 0 ? 'No' : $count;
        $this->writeLn($prefix . ' records updated');
        return $count;
    }


    /**
     * @param string $dateString
     * @return null|string
     */
    protected function parseDateString($dateString)
    {
        $dateString = TimeUtil::getTimeStampForSqlFromAnyDateString($dateString);
        return StringUtil::getNullAsStringOrWrapInQuotes($dateString);
    }
}