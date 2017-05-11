<?php


namespace AppBundle\MixBlup;

use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class ReproductionDataFile
 * @package AppBundle\MixBlup
 */
class ReproductionDataFile extends MixBlupDataFileBase implements MixBlupDataFileInterface
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
        // TODO: Implement generateDataFile() method.
    }

    /**
     * @inheritDoc
     */
    function generatePedigreeFile()
    {
        // TODO: Implement generatePedigreeFile() method.
    }


}