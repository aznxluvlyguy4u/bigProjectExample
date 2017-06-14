<?php


namespace AppBundle\Component\MixBlup;

use AppBundle\Setting\MixBlupFolder;
use AppBundle\Util\NullChecker;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Bridge\Monolog\Logger;

/**
 * Class MixBlupInputProcessBase
 * @package AppBundle\MixBlup
 */
class MixBlupInputProcessBase
{
    /** @var Connection */
    protected $conn;
    /** @var ObjectManager */
    protected $em;
    /** @var Logger */
    protected $logger;

    /** @var string */
    protected $outputFolderPath;
    /** @var string */
    protected $type;
    /** @var string */
    protected $dataFileName;
    /** @var string */
    protected $pedigreeFileName;

    /**
     * MixBlupInputProcessBase constructor.
     * @param ObjectManager $em
     * @param string $outputFolderPath
     * @param Logger $logger
     * @param string $mixBlupType of MixBlupType enumerator
     */
    public function __construct(ObjectManager $em, $outputFolderPath, $logger, $mixBlupType)
    {
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->outputFolderPath = $outputFolderPath;
        $this->logger = $logger;
        NullChecker::createFolderPathIfNull($outputFolderPath);
        NullChecker::createFolderPathIfNull($outputFolderPath.'/'.MixBlupFolder::INSTRUCTIONS);
        NullChecker::createFolderPathIfNull($outputFolderPath.'/'.MixBlupFolder::DATA);
        NullChecker::createFolderPathIfNull($outputFolderPath.'/'.MixBlupFolder::PEDIGREE);
        
        $this->dataFileName = MixBlupFileName::getDataFileName($mixBlupType);
        $this->pedigreeFileName = MixBlupFileName::getPedigreeFileName($mixBlupType);
    }


    /**
     * @param array $records
     * @param string $filename
     * @return bool
     */
    protected function writeInstructionFile(array $records, $filename)
    {
        return $this->writeToFile($records, $filename, MixBlupFolder::INSTRUCTIONS);
    }


    /**
     * @param array $records
     * @param string $filename
     * @return bool
     */
    protected function writeDataFile(array $records, $filename)
    {
        return $this->writeToFile($records, $filename, MixBlupFolder::DATA);
    }


    /**
     * @param array $records
     * @param string $filename
     * @return bool
     */
    protected function writePedigreeFile(array $records, $filename)
    {
        return $this->writeToFile($records, $filename, MixBlupFolder::PEDIGREE);
    }


    /**
     * @param array $records
     * @param string $filename
     * @param string $subfolder
     * @return bool
     */
    private function writeToFile(array $records, $filename, $subfolder = null)
    {
        if(!is_string($filename) || $filename == '') { return false; }

        if($subfolder != null) {
            $filePath = $this->outputFolderPath.'/'.$subfolder.'/'.$filename;
        } else {
            $filePath = $this->outputFolderPath.'/'.$filename;
        }

        //purge current file content
        file_put_contents($filePath, "");

        end($records); //Move pointer to last element
        $lastKey = key($records);
        //reset($records); //Move pointer to first element;
        //$firstKey = key($records);

        $newLine = "\n";
        foreach ($records as $key => $record) {
            if($key === $lastKey) { $newLine = ''; }
            file_put_contents($filePath, $record.$newLine, FILE_APPEND);
        }

        return true;
    }


    /**
     * Overwrite this in the child classes!
     * @return array
     */
    function generateInstructionFiles() {
        return [];
    }


    /**
     * Overwrite this in the child classes!
     * @return array
     */
    function generateDataFile() {
        return [];
    }


    /**
     * Overwrite this in the child classes!
     * @return array
     */
    function generatePedigreeFile() {
        return [];
    }


    /**
     * @inheritDoc
     */
    function write()
    {
        $successfulProcess = true;

        $successfulWrite = $this->writeInstructionFiles();
        if(!$successfulWrite) { $successfulProcess = false; }

        $successfulWrite = $this->writeDataFile($this->generateDataFile(), $this->dataFileName);
        if(!$successfulWrite) { $successfulProcess = false; }

        $successfulWrite = $this->writePedigreeFile($this->generatePedigreeFile(), $this->pedigreeFileName);
        if(!$successfulWrite) { $successfulProcess = false; }

        return $successfulProcess;
    }


    /**
     * @inheritDoc
     */
    function writeInstructionFiles()
    {
        $successfulProcess = true;

        foreach ($this->generateInstructionFiles() as $filename => $records) {
            $successfulWrite = $this->writeInstructionFile($records, $filename);
            if(!$successfulWrite) { $successfulProcess = false; }
        }

        return $successfulProcess;
    }

}