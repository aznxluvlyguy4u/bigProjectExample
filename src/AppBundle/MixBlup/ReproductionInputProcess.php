<?php


namespace AppBundle\MixBlup;

use AppBundle\Enumerator\MixBlupType;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bridge\Monolog\Logger;

/**
 * Class ReproductionInputProcess
 * @package AppBundle\MixBlup
 */
class ReproductionInputProcess extends MixBlupInputProcessBase implements MixBlupInputProcessInterface
{
    /**
     * ReproductionInputProcess constructor.
     * @param ObjectManager $em
     * @param string $outputFolderPath
     * @param Logger $logger
     */
    public function __construct(ObjectManager $em, $outputFolderPath, Logger $logger)
    {
        parent::__construct($em, $outputFolderPath, $logger, MixBlupType::FERTILITY);
    }


    /**
     * @inheritDoc
     */
    function generateInstructionFiles()
    {
        return ReproductionInstructionFiles::generateInstructionFiles();
    }

    /**
     * @inheritDoc
     */
    function generateDataFile()
    {
        return ReproductionDataFile::generateDataFile($this->conn);
    }

    /**
     * @inheritDoc
     */
    function generatePedigreeFile()
    {
        return MixblupPedigreeFileGenerator::generateFullSet($this->conn, $this->logger);
    }


}