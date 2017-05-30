<?php


namespace AppBundle\MixBlup;

use AppBundle\Enumerator\MixBlupType;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class ExteriorInputProcess
 * @package AppBundle\MixBlup
 */
class ExteriorInputProcess extends MixBlupInputProcessBase implements MixBlupInputProcessInterface
{

    /**
     * ExteriorInputProcess constructor.
     * @param ObjectManager $em
     * @param string $outputFolderPath
     */
    public function __construct(ObjectManager $em, $outputFolderPath)
    {
        parent::__construct($em, $outputFolderPath, MixBlupType::EXTERIOR);
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
        return MixblupPedigreeFileGenerator::generateExteriorOptimizedSet($this->conn);
    }
    
    
}