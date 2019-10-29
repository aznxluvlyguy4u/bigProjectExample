<?php


namespace AppBundle\Service;


use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DsvWriterUtil;
use AppBundle\Util\FilesystemUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class CsvFromSqlResultsWriterService
 */
class CsvFromSqlResultsWriterService
{
    const DEFAULT_SEPARATOR = ';';
    const DEFAULT_SUBDIR = 'csv';
    const NEW_LINE = "\n";
    const BOOLEAN_NULL_REPLACEMENT_VALUE = null;

    const DATA_IS_EMPTY_ERROR = "DATA IS EMPTY";

    /** @var ObjectManager|EntityManagerInterface */
    private $em;
    /** @var Connection */
    private $conn;
    /** @var Filesystem */
    private $fs;

    /** @var Logger */
    private $logger;
    /** @var TranslatorInterface */
    private $translator;

    /** @var string */
    private $cacheDir;
    /** @var string */
    private $separator;

    /**
     * CsvFromSqlResultsWriterService constructor.
     * @param ObjectManager|EntityManagerInterface $em
     * @param Logger $logger
     * @param TranslatorInterface $translator
     * @param string $cacheDir
     */
    public function __construct(EntityManagerInterface $em, Logger $logger, TranslatorInterface $translator, $cacheDir)
    {
        $this->em = $em;
        $this->conn = $em->getConnection();

        $this->logger = $logger;
        $this->translator = $translator;

        $this->cacheDir = $cacheDir;
        $this->fs = new Filesystem();

        $this->separator = self::DEFAULT_SEPARATOR;
    }


    /**
     * @return Connection
     */
    protected function getConnection()
    {
        return $this->em->getConnection();
    }


    /**
     * @return string
     */
    public function getSeparator()
    {
        return $this->separator;
    }


    /**
     * @param string $separator
     * @return CsvFromSqlResultsWriterService
     * @throws \Exception
     */
    public function setSeparator($separator)
    {
        if (!is_string($separator)) {
            throw new \Exception('Separator must be a string');
        }

        $this->separator = $separator;
        return $this;
    }


    /**
     * @param $sql
     * @param $subDir
     * @param $filename
     * @param CommandUtil|null $cmdUtil
     * @return null|string
     */
    public function writeFromQuery($sql, $filename, CommandUtil $cmdUtil = null, $subDir = self::DEFAULT_SUBDIR)
    {
        $results = $this->conn->query($sql)->fetchAll();
        if (count($results) === 0) {
            return null;
        }

        return $this->write($results, $filename, $cmdUtil, $subDir);
    }


    /**
     * @param array $results
     * @param string $subDir
     * @param string $filename
     * @param CommandUtil|null $cmdUtil
     * @return null|string
     */
    public function write(array $results, $filename, CommandUtil $cmdUtil = null, $subDir = self::DEFAULT_SUBDIR)
    {
        $dir = self::csvCacheDir($this->cacheDir, $subDir);
        return self::writeCsv($results, $dir, $filename, $this->fs, $this->separator,$cmdUtil);
    }


    /**
     * @param string $cacheDir
     * @param string $csvSubDir
     * @return string
     */
    public static function csvCacheDir($cacheDir, $csvSubDir = self::DEFAULT_SUBDIR)
    {
        $dir = FilesystemUtil::concatDirAndFilename($cacheDir, $csvSubDir);
        FilesystemUtil::createFolderPathIfNull($dir);
        return $dir;
    }


    /**
     * @param array $results
     * @return array|null
     */
    public static function getHeaders(array $results)
    {
        $firstRecord = ArrayUtil::firstValue($results);
        if (is_array($firstRecord) && count($firstRecord) !== 0) {
            return array_keys($firstRecord);
        }
        return null;
    }


    /**
     * @param array $results
     * @param string $dir
     * @param string $filename
     * @param Filesystem|null $fs
     * @param string $separator
     * @param CommandUtil|null $cmdUtil
     * @return null|string
     * @throws \Exception
     */
    public static function writeCsv(array $results, $dir, $filename, Filesystem $fs = null,
                                 $separator = self::DEFAULT_SEPARATOR, CommandUtil $cmdUtil = null)
    {
        //Validation
        if(!is_string($filename) || $filename == '') { throw new \Exception('invalid or empty filename'); }
        if(!is_string($dir) || $dir == '') { throw new \Exception('invalid or empty pathname'); }

        $headers = self::getHeaders($results);
        if ($headers === null) { return null; }

        FilesystemUtil::createFolderPathIfNull($dir, $fs);
        $pathname = FilesystemUtil::concatDirAndFilename($dir, $filename);


        //purge current file content
        file_put_contents($pathname, "");

        $lastKey = ArrayUtil::lastKey($results);

        if ($cmdUtil !== null) {
            $cmdUtil->setStartTimeAndPrintIt(count($results), 1);
        }

        //Print header
        file_put_contents($pathname, implode($separator, $headers).self::NEW_LINE, FILE_APPEND);

        //Print records
        foreach ($results as $key => $result)
        {
            $newLine = self::NEW_LINE;
            if($key === $lastKey) { $newLine = ''; }

            file_put_contents($pathname, implode($separator, $result).$newLine, FILE_APPEND);

            if ($cmdUtil !== null) {
                $cmdUtil->advanceProgressBar(1, 'generating '.$pathname);
            }
        }

        if ($cmdUtil !== null) {
            $cmdUtil->setEndTimeAndPrintFinalOverview();
        }

        return $pathname;
    }


    /**
     * @param $filename
     */
    public function removeFile($filename)
    {
        $this->fs->remove($filename);
    }


    /**
     * @param string $selectQuery
     * @param string $filepath
     * @param array $booleanColumns
     * @throws \Exception
     */
    public function writeToFileFromSqlQuery($selectQuery, $filepath, $booleanColumns = [])
    {
        $isDataMissing = false;

        try {
            $stmt = $this->getConnection()->query($selectQuery);

            if ($firstRow = $stmt->fetch()) {
                ArrayUtil::validateIfKeysExist($booleanColumns, $firstRow, false);

                $firstRow = $this->translateSqlResultBooleanValue($firstRow, $booleanColumns);

                DsvWriterUtil::writeNestedRowToFile($filepath, array_keys($firstRow)); //write headers
                DsvWriterUtil::writeNestedRowToFile($filepath, $firstRow);
            } else {
                $isDataMissing = true;
            }

            while ($row = $stmt->fetch()) {
                $row = $this->translateSqlResultBooleanValue($row, $booleanColumns);
                DsvWriterUtil::writeNestedRowToFile($filepath, $row);
            }

        } catch (\Exception $exception) {

            FilesystemUtil::deleteFile($filepath);

            // Hide error details from user
            $this->logger->error($exception->getMessage());
            $this->logger->error($exception->getTraceAsString());
            throw new \Exception('FAILED WRITING THE CSV FILE', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($isDataMissing) {
            throw new \Exception(self::DATA_IS_EMPTY_ERROR, Response::HTTP_NOT_FOUND);
        }
    }


    /**
     * @param array $row
     * @param array $booleanColumns
     * @return array
     */
    private function translateSqlResultBooleanValue($row, $booleanColumns)
    {
        if (count($booleanColumns) === 0) {
            return $row;
        }

        foreach ($booleanColumns as $column)
        {
            $boolVal = ArrayUtil::get($column, $row);

            $printValue = self::BOOLEAN_NULL_REPLACEMENT_VALUE;
            if ($boolVal === true) {
                $printValue = strtoupper($this->translator->trans('TRUE'));
            } elseif ($boolVal === false) {
                $printValue = strtoupper($this->translator->trans('FALSE'));
            }

            $row[$column] = $printValue;
        }

        return $row;
    }

}
