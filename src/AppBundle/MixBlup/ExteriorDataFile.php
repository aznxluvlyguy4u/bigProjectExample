<?php


namespace AppBundle\MixBlup;

use Doctrine\DBAL\Connection;

/**
 * Class ExteriorDataFile
 * @package AppBundle\MixBlup
 */
class ExteriorDataFile extends MixBlupDataFileBase implements MixBlupDataFileInterface
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