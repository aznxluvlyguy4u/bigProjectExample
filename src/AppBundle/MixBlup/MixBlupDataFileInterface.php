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

    /**
     * Return the string of the sqlQuery with the animal id as 'animal_id'
     * and 'type' of the animal records included in the datafile.
     * 
     * @return string
     */
    static function getSqlQueryRelatedAnimals();
    
}