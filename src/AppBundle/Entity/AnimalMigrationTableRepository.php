<?php

namespace AppBundle\Entity;
use AppBundle\Util\StringUtil;
use AppBundle\Util\Validator;

/**
 * Class AnimalMigrationTableRepository
 * @package AppBundle\Entity
 */
class AnimalMigrationTableRepository extends BaseRepository {
    

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getExistingUlnsInAnimalAndAnimalMigrationTables()
    {
        $searchArray = [];

        $sql = "SELECT DISTINCT(uln_number) FROM animal_migration_table t
                WHERE uln_number NOTNULL";
        $results = $this->getConnection()->query($sql)->fetchAll();
        foreach ($results as $result) {
            $ulnNumber = $result['uln_number'];
            $searchArray[$ulnNumber] = $ulnNumber;
        }

        $sql = "SELECT DISTINCT(uln_number) FROM animal a
                WHERE uln_number NOTNULL";
        $results = $this->getConnection()->query($sql)->fetchAll();
        foreach ($results as $result) {
            $ulnNumber = $result['uln_number'];
            $searchArray[$ulnNumber] = $ulnNumber;
        }

        return $searchArray;
    }


    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getExistingPedigreeNumbersInAnimalAndAnimalMigrationTables()
    {
        $searchArray = [];

        $sql = "SELECT DISTINCT(pedigree_number) FROM animal_migration_table t
                WHERE pedigree_number NOTNULL";
        $results = $this->getConnection()->query($sql)->fetchAll();
        foreach ($results as $result) {
            $pedigreeNumber = $result['pedigree_number'];
            $searchArray[$pedigreeNumber] = $pedigreeNumber;
        }

        $sql = "SELECT DISTINCT(pedigree_number) FROM animal a
                WHERE pedigree_number NOTNULL";
        $results = $this->getConnection()->query($sql)->fetchAll();
        foreach ($results as $result) {
            $pedigreeNumber = $result['pedigree_number'];
            $searchArray[$pedigreeNumber] = $pedigreeNumber;
        }

        return $searchArray;
    }
    
    
    public function fixAnimalOrderNumberToMatchUlnNumber()
    {
        $sql = "UPDATE animal_migration_table SET animal_order_number = SUBSTR(uln_number, 8, 5) WHERE uln_number NOTNULL AND LENGTH(uln_number) = 12 AND SUBSTR(uln_number, 8, 5) <> animal_order_number";
        $this->getConnection()->exec($sql);
    }
    
    
    public function countAnimalOrderNumbersNotMatchingUlnNumbers()
    {
        $sql = "SELECT uln_number, LENGTH(uln_number), SUBSTR(uln_number, 8, 5), animal_order_number 
                FROM animal_migration_table
                WHERE uln_number NOTNULL AND LENGTH(uln_number) = 12 AND SUBSTR(uln_number, 8, 5) <> animal_order_number";
        $results = $this->getConnection()->query($sql)->fetchAll();
        return count($results);
    }

}