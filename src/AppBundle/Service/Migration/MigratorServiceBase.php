<?php


namespace AppBundle\Service\Migration;


use AppBundle\Component\Builder\CsvOptions;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\QueryType;
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
    const BATCH_SIZE = 5000;
    const IMPORT_SUB_FOLDER = '';
    const BLANK_DATE_FILLER = '1899-01-01';
    const DOUBLE_UNDERSCORE = '__';

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

    /** @var Employee */
    private $developer;

    /** @var AnimalRepository */
    protected $animalRepository;
    /** @var Person */
    protected $personRepository;

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

        $this->animalRepository = $this->em->getRepository(Animal::class);
        $this->personRepository = $this->em->getRepository(Person::class);

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
        $this->cmdUtil->writelnWithTimestamp($line);
    }


    /**
     * @param string $filename
     * @return array
     */
    protected function parseCSV($filename) {

        $this->csvOptions->setFileName($this->filenames[$filename]);
        $this->writeLn('Parse '.$filename.' csv ...');
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


    /**
     * @param string $sqlSelectWithKey
     * @param string $sqlUpdateBase
     * @param string $sqlUpdateEnd
     * @param string $title
     */
    protected function updateBySelectAndUpdateSql($sqlSelectWithKey, $sqlUpdateBase, $sqlUpdateEnd, $title = null)
    {
        if(is_string($title)) {
            $this->writeLn($title);
        }

        $updateBatchSet = $this->sqlBatchProcessor
            ->purgeAllSets()
            ->createBatchSet(QueryType::UPDATE)
            ->getSet(QueryType::UPDATE)
        ;

        $updateBatchSet
            ->setSqlQueryBase($sqlUpdateBase)
            ->setSqlQueryBaseEnd($sqlUpdateEnd);

        $results = $this->conn->query($sqlSelectWithKey)->fetchAll();

        $this->sqlBatchProcessor->start(count($results));

        foreach ($results as $result) {

            $updateBatchSet->appendValuesString($result['set']);

            $this->sqlBatchProcessor
                ->processAtBatchSize()
                ->advanceProgressBar()
            ;
        }
        $this->sqlBatchProcessor->end()->purgeAllSets();
    }


    /**
     * @return Employee|Person
     */
    protected function getDeveloper()
    {
        if ($this->developer == null) {
            $this->developer = $this->personRepository->find(self::DEVELOPER_PRIMARY_KEY);
        }
        return $this->developer;
    }
}