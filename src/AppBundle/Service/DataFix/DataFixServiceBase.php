<?php


namespace AppBundle\Service\DataFix;


use AppBundle\Component\Builder\CsvOptions;
use AppBundle\Util\CsvParser;
use AppBundle\Util\FilesystemUtil;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Monolog\Logger;

abstract class DataFixServiceBase
{
    const FILENAME = 'data_fix.csv';
    const INPUT_FOLDER = 'app/Resources/imports/corrections/';
    const OUTPUT_FOLDER = 'app/Resources/output/corrections/';

    /** @var EntityManagerInterface */
    private $em;
    /** @var Logger */
    private $logger;
    /** @var string */
    private $rootDir;
    /** @var array */
    private $data;


    public function __construct(EntityManagerInterface $em, Logger $logger, $rootDir)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->rootDir = $rootDir;
    }


    /**
     * @throws \Exception
     */
    protected function parse()
    {
        $csvOptions = (new CsvOptions())
            ->includeFirstLine()
            ->setInputFolder(static::INPUT_FOLDER)
            ->setOutputFolder(static::OUTPUT_FOLDER)
            ->setFileName(static::FILENAME)
            ->setPipeSeparator()
        ;

        FilesystemUtil::createFolderPathsFromCsvOptionsIfNull($this->rootDir, $csvOptions);

        if(!FilesystemUtil::csvFileExists($this->rootDir, $csvOptions)) {
            throw new \Exception($csvOptions->getFileName().' is missing. No '.$csvOptions->getFileName().' data is imported!');
        }

        $csv = CsvParser::parse($csvOptions);
        if(!is_array($csv)) {
            throw new \Exception('Import file failed or import file is empty');
        }

        $this->data = $csv;
    }

    /**
     * @return EntityManagerInterface
     */
    public function getManager()
    {
        return $this->em;
    }


    /**
     * @return \Doctrine\DBAL\Connection
     */
    public function getConnection()
    {
        return $this->em->getConnection();
    }


    /**
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return string
     */
    public function getRootDir()
    {
        return $this->rootDir;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }



}