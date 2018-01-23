<?php


namespace AppBundle\Component\MixBlup;


use Doctrine\DBAL\Connection;

class WormResistanceDataFile extends MixBlupDataFileBase implements MixBlupDataFileInterface
{
    /**
     * @inheritDoc
     */
    static function generateDataFile(Connection $conn)
    {
        // TODO
        return [];
    }

    /**
     * @inheritDoc
     */
    static function getSqlQueryRelatedAnimals()
    {
        // TODO: Implement getSqlQueryRelatedAnimals() method.
        return '';
    }


}