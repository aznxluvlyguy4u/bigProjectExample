<?php

namespace AppBundle\Entity;
use AppBundle\Util\StringUtil;

/**
 * Class AnimalMigrationTableRepository
 * @package AppBundle\Entity
 */
class AnimalMigrationTableRepository extends BaseRepository {

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getUbnOfBirthByBreederNumberSearchArray()
    {
        $sql = "SELECT pedigree_number, ubn_of_birth FROM animal_migration_table t
                WHERE pedigree_country_code = 'NL' AND t.ubn_of_birth NOTNULL AND t.pedigree_number NOTNULL";
        $results = $this->getConnection()->query($sql)->fetchAll();
        
        $searchArray = [];
        foreach ($results as $result) {
            $ubnOfBirth = $result['ubn_of_birth'];
            $breederNumber = StringUtil::getBreederNumberFromPedigreeNumber($result['pedigree_number']);
            $searchArray[$breederNumber] = $ubnOfBirth;
        }
        return $searchArray;
    }

}