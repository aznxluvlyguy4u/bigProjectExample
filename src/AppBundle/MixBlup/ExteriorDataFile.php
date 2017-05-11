<?php


namespace AppBundle\MixBlup;

use AppBundle\Enumerator\MixBlupNullFiller;
use AppBundle\Setting\MixBlupSetting;
use AppBundle\Util\ArrayUtil;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class ExteriorDataFile
 * @package AppBundle\MixBlup
 */
class ExteriorDataFile extends MixBlupDataFileBase implements MixBlupDataFileInterface
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