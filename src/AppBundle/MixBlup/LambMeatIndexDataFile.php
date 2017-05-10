<?php


namespace AppBundle\MixBlup;

use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class LambMeatIndexDataFile
 * @package AppBundle\MixBlup
 */
class LambMeatIndexDataFile extends MixBlupDataFileBase implements MixBlupDataFileInterface
{
    /**
     * LambMeatIndexDataFile constructor.
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