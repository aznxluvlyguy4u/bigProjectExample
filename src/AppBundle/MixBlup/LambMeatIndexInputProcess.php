<?php


namespace AppBundle\MixBlup;

use AppBundle\Enumerator\MixBlupType;
use Doctrine\Common\Persistence\ObjectManager;

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
        return MixblupPedigreeFileGenerator::generateLambMeatIndexOptimizedSet($this->conn);
    }




}