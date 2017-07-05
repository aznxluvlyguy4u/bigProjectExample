<?php


namespace AppBundle\Component\MixBlup;

use AppBundle\Enumerator\MixBlupType;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bridge\Monolog\Logger;

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
     * @param Logger $logger
     */
    public function __construct(ObjectManager $em, $outputFolderPath, Logger $logger)
    {
        parent::__construct($em, $outputFolderPath, $logger, MixBlupType::EXTERIOR);
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
        return MixblupPedigreeFileGenerator::generateExteriorOptimizedSet($this->conn, $this->logger);
    }
    
    
}