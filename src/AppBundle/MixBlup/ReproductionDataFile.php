<?php


namespace AppBundle\MixBlup;

use Doctrine\DBAL\Connection;

/**
 * Class ReproductionDataFile
 * @package AppBundle\MixBlup
 */
class ReproductionDataFile extends MixBlupDataFileBase implements MixBlupDataFileInterface
{

    /**
     * @inheritDoc
     */
    static function generateDataFile(Connection $conn)
    {
        // TODO: Implement generateDataFile() method.
        return [];
    }
    
    
    
}