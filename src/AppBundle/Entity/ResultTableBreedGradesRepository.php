<?php

namespace AppBundle\Entity;

use AppBundle\Util\SqlUtil;

/**
 * Class ResultTableBreedGradesRepository
 * @package AppBundle\Entity
 */
class ResultTableBreedGradesRepository extends BaseRepository {

    /**
     * @param array $animalIds
     * @throws \Doctrine\DBAL\DBALException
     */
    public function deleteByAnimalsIds($animalIds)
    {
        $animalIdFilterString = SqlUtil::getFilterStringByIdsArray($animalIds, 'animal_id');
        if($animalIdFilterString != '') {
            $sql = "DELETE FROM result_table_breed_grades WHERE ".$animalIdFilterString;
            $this->getConnection()->exec($sql);
        }
    }

}