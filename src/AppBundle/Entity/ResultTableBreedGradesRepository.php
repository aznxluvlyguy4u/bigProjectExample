<?php

namespace AppBundle\Entity;

use AppBundle\Util\SqlUtil;

/**
 * Class ResultTableBreedGradesRepository
 * @package AppBundle\Entity
 */
class ResultTableBreedGradesRepository extends BaseRepository {

    /**
     * @param $animalIds
     * @return int
     */
    public function deleteByAnimalIdsAndSql($animalIds)
    {
        return $this->deleteTableRecordsByTableNameAndAnimalIdsAndSql('result_table_breed_grades', $animalIds);
    }

}