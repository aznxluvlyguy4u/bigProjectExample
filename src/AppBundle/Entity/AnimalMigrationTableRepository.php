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

        $sql = "SELECT uln_number FROM animal_migration_table t";
        $results = $this->getConnection()->query($sql)->fetchAll();
        foreach ($results as $result) {
            $ulnNumber = $result['uln_number'];
            $searchArray[$ulnNumber] = $ulnNumber;
        }

        $sql = "SELECT uln_number FROM animal a";
        $results = $this->getConnection()->query($sql)->fetchAll();
        foreach ($results as $result) {
            $ulnNumber = $result['uln_number'];
            $searchArray[$ulnNumber] = $ulnNumber;
        }

        return $searchArray;
    }

}