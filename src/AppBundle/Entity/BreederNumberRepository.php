<?php

namespace AppBundle\Entity;
use AppBundle\Util\SqlUtil;
use Doctrine\Common\Collections\Criteria;

/**
 * Class BreederNumberRepository
 * @package AppBundle\Entity
 */
class BreederNumberRepository extends BaseRepository {

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getCurrentRecordsSearchArray()
    {
        $sql = "SELECT breeder_number, source FROM breeder_number";
        $results = $this->getConnection()->query($sql)->fetchAll();
        $savedBreederNumbers = [];
        foreach ($results as $result) {
            $breederNumber = $result['breeder_number'];
            $source = $result['source'];
            $savedBreederNumbers[$breederNumber] = $source;
        }
        return $savedBreederNumbers;
    }


    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getUbnOfBirthByBreederNumberSearchArray()
    {
        $sql = "SELECT breeder_number, ubn_of_birth FROM breeder_number";
        $results = $this->getConnection()->query($sql)->fetchAll();
        $savedBreederNumbers = [];
        foreach ($results as $result) {
            $breederNumber = $result['breeder_number'];
            $ubnOfBirth = $result['ubn_of_birth'];
            $savedBreederNumbers[$breederNumber] = $ubnOfBirth;
        }
        return $savedBreederNumbers;
    }


    /**
     * @param string $breederNumber
     * @param string $ubnOfBirth
     * @param string $source
     * @throws \Doctrine\DBAL\DBALException
     */
    public function insertNewRecordBySql($breederNumber, $ubnOfBirth, $source)
    {
        $source = SqlUtil::getNullCheckedValueForSqlQuery($source, true);

        $sql = "INSERT INTO breeder_number (id, breeder_number, ubn_of_birth, source)
                VALUES(nextval('breeder_number_id_seq'),'".$breederNumber."','".$ubnOfBirth."',".$source.")";
        $this->getConnection()->exec($sql);
    }
}