<?php


namespace AppBundle\Component\MixBlup;

use AppBundle\Enumerator\MixBlupType;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bridge\Monolog\Logger;

/**
 * Class LambMeatIndexInputProcess
 * @package AppBundle\MixBlup
 */
class LambMeatIndexInputProcess extends MixBlupInputProcessBase implements MixBlupInputProcessInterface
{
    /**
     * LambMeatIndexInputProcess constructor.
     * @param ObjectManager $em
     * @param string $outputFolderPath
     * @param Logger $logger
     */
    public function __construct(ObjectManager $em, $outputFolderPath, Logger $logger)
    {
        parent::__construct($em, $outputFolderPath, $logger, MixBlupType::LAMB_MEAT_INDEX);
    }
    
    
    /**
     * @inheritDoc
     */
    function generateInstructionFiles()
    {
        return LambMeatIndexInstructionFiles::generateInstructionFiles();
    }

    /**
     * @inheritDoc
     */
    function generateDataFile()
    {
        return LambMeatIndexDataFile::generateDataFile($this->conn);
    }

    /**
     * @inheritDoc
     */
    function generatePedigreeFile()
    {
        return MixblupPedigreeFileGenerator::generateLambMeatIndexOptimizedSet($this->conn, $this->logger);
    }




}