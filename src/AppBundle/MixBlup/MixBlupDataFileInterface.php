<?php


namespace AppBundle\MixBlup;


use Doctrine\DBAL\Connection;

interface MixBlupDataFileInterface
{
    /**
     * Generate the data for the datafile.
     *
     * @param Connection $conn
     * @return array
     */
    static function generateDataFile(Connection $conn);
}