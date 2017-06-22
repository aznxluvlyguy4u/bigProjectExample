<?php

namespace AppBundle\Entity;

use AppBundle\Constant\JsonInputConstant;
use AppBundle\Util\SqlUtil;

/**
 * Class ErrorLogAnimalPedigreeRepository
 * @package AppBundle\Entity
 */
class ErrorLogAnimalPedigreeRepository extends BaseRepository
{

    /**
     * @param bool $returnChainsAsArray
     * @return array
     */
    public function findAllAsSearchArray($returnChainsAsArray = false)
    {
        $sql = "SELECT * FROM error_log_animal_pedigree";
        $results = $this->getConnection()->query($sql)->fetchAll();

        $searchArray = [];
        foreach ($results as $result)
        {
            $animalId = $result['animal_id'];

            if($animalId) {

                if($returnChainsAsArray) {
                    $searchArray[$animalId] = [
                        JsonInputConstant::ANIMAL_ID => $animalId,
                        JsonInputConstant::PARENT_IDS => json_decode($result['parent_ids']),
                        JsonInputConstant::PARENT_TYPES => json_decode($result['parent_types']),
                    ];
                } else {
                    $searchArray[$animalId] = [
                        JsonInputConstant::ANIMAL_ID => $animalId,
                        JsonInputConstant::PARENT_IDS => $result['parent_ids'],
                        JsonInputConstant::PARENT_TYPES => $result['parent_types'],
                    ];
                }

            }
        }

        return $searchArray;
    }


    /**
     * @param array $animalIds
     * @return int
     */
    public function removeByAnimalIds($animalIds)
    {
        if(count($animalIds) === 0) { return 0; }

        $filterString = SqlUtil::filterString($animalIds, JsonInputConstant::ANIMAL_ID, false);;

        $sql = 'DELETE FROM error_log_animal_pedigree WHERE '.$filterString;
        return SqlUtil::updateWithCount($this->getConnection(), $sql);
    }


    /**
     * @param $valuesByAnimalId
     * @return int
     */
    public function updateByAnimalIdArrays($valuesByAnimalId)
    {
        if(count($valuesByAnimalId) === 0) { return 0; }

        $valuesString = $this->getValuesString($valuesByAnimalId, false);

        $sql = 'UPDATE error_log_animal_pedigree SET parent_ids = v.parent_ids, parent_types = v.parent_types
                FROM (VALUES ' . $valuesString . '
                ) as v(animal_id, parent_ids, parent_types) WHERE error_log_animal_pedigree.animal_id = v.animal_id';
        return SqlUtil::updateWithCount($this->getConnection(), $sql);
    }


    /**
     * @param $valuesByAnimalId
     * @return int
     */
    public function insertByAnimalIdArrays($valuesByAnimalId)
    {
        if(count($valuesByAnimalId) === 0) { return 0; }

        $valuesString = $this->getValuesString($valuesByAnimalId, true);

        $sql = 'INSERT INTO error_log_animal_pedigree (id, animal_id, parent_ids, parent_types) VALUES '.$valuesString;
        return SqlUtil::updateWithCount($this->getConnection(), $sql);
    }


    /**
     * @param array $valuesByAnimalId
     * @param boolean $includeNextValId
     * @return string
     */
    private function getValuesString($valuesByAnimalId, $includeNextValId)
    {
        $nextValString = $includeNextValId ? "nextval('error_log_animal_pedigree_id_seq')," : '';

        $valuesString = '';
        $prefix = '';
        foreach ($valuesByAnimalId as $animalId => $array) {
            $parentIds = $array[JsonInputConstant::PARENT_IDS];
            $parentTypes = $array[JsonInputConstant::PARENT_TYPES];


            $valuesString = $valuesString . $prefix
                . "(" . $nextValString . $animalId . ",'" . $parentIds . "','" . $parentTypes . "')";

            $prefix = ',';
        }

        return $valuesString;
    }
}