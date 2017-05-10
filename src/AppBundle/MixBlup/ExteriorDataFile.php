<?php


namespace AppBundle\MixBlup;

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
     */
    public function __construct(ObjectManager $em)
    {
        parent::__construct($em);
    }
    

    /**
     * @inheritDoc
     */
    function generateInstructionFiles()
    {
        // TODO: Implement generateInstructionFiles() method.
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

    /**
     * @inheritDoc
     */
    function write()
    {
        // TODO: Implement write() method.
    }
    
    
}