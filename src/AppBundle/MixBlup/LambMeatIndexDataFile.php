<?php


namespace AppBundle\MixBlup;

use Doctrine\DBAL\Connection;

/**
 * Class LambMeatIndexDataFile
 * @package AppBundle\MixBlup
 */
class LambMeatIndexDataFile extends MixBlupDataFileBase implements MixBlupDataFileInterface
{

    /**
     * @inheritDoc
     */
    static function generateDataFile(Connection $conn)
    {
        // TODO: Implement generateDataFile() method.
        return [];
    }


    /**
     * @inheritDoc
     */
    static function getSqlQueryRelatedAnimals()
    {
        // TODO: Implement getSqlQueryRelatedAnimals() method.
    }


}