<?php


namespace AppBundle\MixBlup;

use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class ExteriorDataFile
 * @package AppBundle\MixBlup
 */
class ExteriorProcess extends MixBlupProcessBase implements MixBlupProcessInterface
{

    /**
     * ExteriorDataFile constructor.
     * @param ObjectManager $em
     * @param string $outputFolderPath
     */
    public function __construct(ObjectManager $em, $outputFolderPath)
    {
        parent::__construct($em, $outputFolderPath);
    }


    /**
     * @inheritDoc
     */
    function generateInstructionFiles()
    {
        return ExteriorInstructionFiles::generateInstructionFiles();
    }

    /**
     * @inheritDoc
     */
    function generateDataFile()
    {
        return ExteriorDataFile::generateDataFile($this->conn);
    }

    /**
     * @inheritDoc
     */
    function generatePedigreeFile()
    {
        return MixblupPedigreeFileGenerator::generateFullSet($this->conn);
    }
    
    
}