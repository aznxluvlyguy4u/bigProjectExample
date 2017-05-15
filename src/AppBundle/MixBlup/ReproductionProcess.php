<?php


namespace AppBundle\MixBlup;

use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class ReproductionDataFile
 * @package AppBundle\MixBlup
 */
class ReproductionProcess extends MixBlupProcessBase implements MixBlupProcessInterface
{
    /**
     * ReproductionDataFile constructor.
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
        // TODO: Implement generatePedigreeFile() method.
    }


}