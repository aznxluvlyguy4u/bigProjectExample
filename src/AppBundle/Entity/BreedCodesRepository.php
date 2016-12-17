<?php

namespace AppBundle\Entity;
use AppBundle\Util\SqlUtil;
use Doctrine\Common\Collections\Criteria;

/**
 * Class BreedCodesRepository
 * @package AppBundle\Entity
 */
class BreedCodesRepository extends BaseRepository {

    /**
     * Delete breedCodes and related BreedCode records
     * 
     * @param $animalIds
     * @throws \Doctrine\DBAL\DBALException
     */
    public function deleteByAnimalIds($animalIds)
    {
        $animalIdFilterString = SqlUtil::getFilterStringByIdsArray($animalIds, 'a.id');
        $animalIdInBreedCodesFilterString = strtr($animalIdFilterString, ['a.id' => 'animal_id']);
        
        $sql = "SELECT c.id as code_id, b.id as breed_codes_id FROM breed_code c
                    LEFT JOIN breed_codes b ON c.breed_codes_id = b.id
                WHERE ".$animalIdInBreedCodesFilterString;
        $results = $this->getConnection()->query($sql)->fetchAll();

        if($animalIdFilterString != '') {
            $sql = "UPDATE animal as a SET breed_codes_id = NULL WHERE ".$animalIdFilterString;
            $this->getConnection()->exec($sql);

            $sql = "UPDATE breed_codes SET animal_id = NULL WHERE ".$animalIdInBreedCodesFilterString;
            $this->getConnection()->exec($sql);
        }

        if(count($results) > 0) {

            $groupedSqlResults = SqlUtil::groupSqlResultsByVariable($results);
            $codesFilterString = SqlUtil::getFilterStringByIdsArray($groupedSqlResults['code_id']);
            $breedCodesFilterString = SqlUtil::getFilterStringByIdsArray($groupedSqlResults['breed_codes_id']);;

            if($codesFilterString !=  '') {
                $sql = "UPDATE breed_code SET breed_codes_id = NULL WHERE ".$breedCodesFilterString;
                $this->getConnection()->exec($sql);

                $sql = "DELETE FROM breed_code WHERE ".$codesFilterString;
                $this->getConnection()->exec($sql);
            }

            if($breedCodesFilterString != '') {
                $sql = "DELETE FROM breed_codes WHERE ".$breedCodesFilterString;
                $this->getConnection()->exec($sql);
            }
        }

        $this->deleteOrphanedBreedCodesRecords();
    }


    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function deleteOrphanedBreedCodesRecords()
    {
        $sql = "SELECT s.id as breed_codes_id, c.id as code_id FROM breed_codes s
                  LEFT JOIN animal a ON s.animal_id = a.id
                  LEFT JOIN breed_code c ON c.breed_codes_id = s.id
                WHERE a.id ISNULL
                UNION
                SELECT NULL as breed_codes_id, id as code_id FROM breed_code
                WHERE breed_codes_id ISNULL";
        $results = $this->getConnection()->query($sql)->fetchAll();

        if(count($results) == 0) { return; }

        $groupedResults = SqlUtil::groupSqlResultsByVariable($results);
        $breedCodesIdsFilterString = SqlUtil::getFilterStringByIdsArray($groupedResults['breed_codes_id']);
        $SingleBreedCodeIdsFilterString = SqlUtil::getFilterStringByIdsArray($groupedResults['code_id']);

        if($breedCodesIdsFilterString != '') {
            $sql = "UPDATE breed_code SET breed_codes_id = NULL WHERE ".$SingleBreedCodeIdsFilterString;
            $this->getConnection()->exec($sql);

            $sql = "DELETE FROM breed_codes WHERE ".$breedCodesIdsFilterString;
            $this->getConnection()->exec($sql);
        }

        if($SingleBreedCodeIdsFilterString != '') {
            $sql = "DELETE FROM breed_code WHERE ".$SingleBreedCodeIdsFilterString;
            $this->getConnection()->exec($sql);
        }
    }

    
}