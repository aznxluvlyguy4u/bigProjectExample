<?php


namespace AppBundle\Component\MixBlup;


use AppBundle\Enumerator\MixBlupType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Monolog\Logger;

class WormResistanceInputProcess extends MixBlupInputProcessBase implements MixBlupInputProcessInterface
{
    /**
     * LambMeatIndexInputProcess constructor.
     * @param EntityManagerInterface $em
     * @param string $outputFolderPath
     * @param Logger $logger
     */
    public function __construct(EntityManagerInterface $em, $outputFolderPath, Logger $logger)
    {
        parent::__construct($em, $outputFolderPath, $logger, MixBlupType::WORM);
    }


    /**
     * @inheritDoc
     */
    function generateInstructionFiles()
    {
        return WormResistanceInstructionFiles::generateInstructionFiles();
    }

    /**
     * @inheritDoc
     */
    function generateDataFile()
    {
        return WormResistanceDataFile::generateDataFile($this->conn);
    }

    /**
     * @inheritDoc
     */
    function generatePedigreeFile()
    {
        return MixblupPedigreeFileGenerator::generateWormResistanceOptimizedSet($this->conn, $this->logger);
    }
}