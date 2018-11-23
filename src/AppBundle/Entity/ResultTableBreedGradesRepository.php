<?php

namespace AppBundle\Entity;

use AppBundle\Util\SqlUtil;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

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


    /**
     * @param int $limit
     * @param int|null $locationId
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function retrieveAnimalsWithMostBreedValues(int $limit = 10, $locationId = null)
    {
        $maxResults = 500;
        if (empty($limit) || (!is_int($limit) && !ctype_digit($limit)) || $limit > $maxResults || $limit < 1) {
            throw new PreconditionFailedHttpException('Limit must be integer below '.$maxResults);
        }

        if ($locationId !== null && !is_int($locationId) && !ctype_digit($locationId)) {
            throw new PreconditionFailedHttpException('locationId must be integer or null');
        }

        $sql = "SELECT
                  result_table_value_variable
                FROM breed_value_type";
        $results = $this->getConnection()->query($sql)->fetchAll();
        $resultTableValueColumns = SqlUtil::getSingleValueGroupedSqlResults(
            'result_table_value_variable', $results,false,false);

        $columnCountKey = 'breed_values_count';
        $resultTableAlias = 'r';
        $columnCountQueryPart = SqlUtil::columnNotNullCountQueryPart($resultTableValueColumns, $resultTableAlias);

        $locationFilter = empty($locationId) ? '' : ' WHERE a.location_id = '.$locationId.' ';

        $sql = "SELECT
                  a.uln_country_code,
                  a.uln_number,
                  va.uln,
                  va.historic_ubns,
                  a.location_id,
                  l.ubn,
                  $resultTableAlias.$columnCountKey
                FROM animal a
                  INNER JOIN (SELECT
                                  $columnCountQueryPart as $columnCountKey,
                                  $resultTableAlias.animal_id
                                FROM result_table_breed_grades $resultTableAlias
                                  INNER JOIN animal a on $resultTableAlias.animal_id = a.id
                                  $locationFilter
                                ORDER BY $columnCountQueryPart DESC
                                LIMIT $limit
                              )r ON r.animal_id = a.id
                  INNER JOIN view_animal_livestock_overview_details va ON va.animal_id = r.animal_id
                  LEFT JOIN location l on a.location_id = l.id
                  $locationFilter";
        return $this->getConnection()->query($sql)->fetchAll();
    }
}