<?php


namespace AppBundle\MixBlup;

use AppBundle\Enumerator\MixBlupType;
use Doctrine\Common\Persistence\ObjectManager;

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
     */
    public function __construct(ObjectManager $em, $outputFolderPath)
    {
        parent::__construct($em, $outputFolderPath, MixBlupType::FERTILITY);
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
        return MixblupPedigreeFileGenerator::generateFullSet($this->conn);
    }


}