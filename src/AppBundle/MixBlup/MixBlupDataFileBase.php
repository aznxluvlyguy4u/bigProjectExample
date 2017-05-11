<?php


namespace AppBundle\MixBlup;

use AppBundle\Setting\MixBlupFolder;
use AppBundle\Util\NullChecker;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;

/**
 * Class MixBlupDataFileBase
 * @package AppBundle\MixBlup
 */
class MixBlupDataFileBase
{
    /** @var Connection */
    protected $conn;

    /** @var ObjectManager */
    protected $em;

    /** @var string */
    protected $outputFolderPath;

    /**
     * MixBlupDataFileBase constructor.
     * @param ObjectManager $em
     * @param string $outputFolderPath
     */
    public function __construct(ObjectManager $em, $outputFolderPath)
    {
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->outputFolderPath = $outputFolderPath;
        NullChecker::createFolderPathIfNull($outputFolderPath);
        NullChecker::createFolderPathIfNull($outputFolderPath.'/'.MixBlupFolder::INSTRUCTIONS);
        NullChecker::createFolderPathIfNull($outputFolderPath.'/'.MixBlupFolder::DATA);
        NullChecker::createFolderPathIfNull($outputFolderPath.'/'.MixBlupFolder::PEDIGREE);
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

        foreach ($records as $record) {
            file_put_contents($filePath, $record."\n", FILE_APPEND);
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
     * @inheritDoc
     */
    function write()
    {
        // TODO: Implement write() method.

        $successful = true;

        foreach ($this->generateInstructionFiles() as $filename => $records) {
            $successful = $this->writeInstructionFile($records, $filename);
            if(!$successful) { $successful = false; }
        }

        return $successful;
    }

}