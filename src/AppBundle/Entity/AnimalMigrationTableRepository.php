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
        $sql = "SELECT pedigree_number, stn_origin, ubn_of_birth FROM animal_migration_table t
                WHERE pedigree_country_code = 'NL' AND t.ubn_of_birth NOTNULL AND t.stn_origin NOTNULL ";
        $results = $this->getConnection()->query($sql)->fetchAll();
        
        $searchArray = [];
        foreach ($results as $result) {
            $pedigreeNumber = $result['pedigree_number'];
            if($pedigreeNumber != null && $pedigreeNumber != '') {
                $breederNumber = StringUtil::getBreederNumberFromPedigreeNumber($pedigreeNumber);
            } else {
                $stnOrigin = $result['stn_origin'];
                $breederNumber = StringUtil::getBreederNumberFromStnOrigin($stnOrigin, true);
            }
            $ubnOfBirth = $result['ubn_of_birth'];

            $searchArray[$breederNumber] = $ubnOfBirth;
        }
        return $searchArray;
    }

}