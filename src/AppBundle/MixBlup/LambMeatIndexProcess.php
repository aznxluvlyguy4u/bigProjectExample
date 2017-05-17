<?php


namespace AppBundle\MixBlup;

use AppBundle\Enumerator\MixBlupType;
use AppBundle\Setting\MixBlupSetting;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class LambMeatIndexDataFile
 * @package AppBundle\MixBlup
 */
class LambMeatIndexProcess extends MixBlupProcessBase implements MixBlupProcessInterface
{
    /**
     * LambMeatIndexDataFile constructor.
     * @param ObjectManager $em
     * @param string $outputFolderPath
     */
    public function __construct(ObjectManager $em, $outputFolderPath)
    {
        parent::__construct($em, $outputFolderPath, MixBlupType::LAMB_MEAT_INDEX);
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
        return MixblupPedigreeFileGenerator::generateFullSet($this->conn);
    }




}