<?php


namespace AppBundle\Service;


use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\FilesystemUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class CsvFromSqlResultsWriterService
 */
class CsvFromSqlResultsWriterService
{
    const DEFAULT_SEPARATOR = ';';
    const DEFAULT_SUBDIR = 'csv';
    const NEW_LINE = "\n";

    /** @var ObjectManager|EntityManagerInterface */
    private $em;
    /** @var Connection */
    private $conn;
    /** @var Filesystem */
    private $fs;

    /** @var string */
    private $cacheDir;
    /** @var string */
    private $separator;

    /**
     * CsvFromSqlResultsWriterService constructor.
     * @param ObjectManager|EntityManagerInterface $em
     * @param string $cacheDir
     */
    public function __construct(EntityManagerInterface $em, $cacheDir)
    {
        $this->em = $em;
        $this->conn = $em->getConnection();

        $this->cacheDir = $cacheDir;
        $this->fs = new Filesystem();

        $this->separator = self::DEFAULT_SEPARATOR;
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
        $dir = FilesystemUtil::concatDirAndFilename($this->cacheDir, $subDir);
        return self::writeCsv($results, $dir, $filename, $this->fs, $this->separator,$cmdUtil);
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
}