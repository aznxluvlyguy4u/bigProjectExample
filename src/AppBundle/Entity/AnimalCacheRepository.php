<?php

namespace AppBundle\Entity;
use AppBundle\Util\PedigreeUtil;

/**
 * Class AnimalCacheRepository
 * @package AppBundle\Entity
 */
class AnimalCacheRepository extends BaseRepository {


    /**
     * @param bool $ignoreAnimalsWithAnExistingCache
     * @param null $ignoreCacheBeforeDateString
     * @param string $sqlFilter
     * @return array
     */
    private function getAnimalCacherInputData($ignoreAnimalsWithAnExistingCache = true, $ignoreCacheBeforeDateString = null, $sqlFilter)
    {
        $sql = "SELECT animal.id as animal_id, gender, date_of_birth, breed_type, animal_cache.id as animal_cache_id, animal_cache.log_date
                FROM animal LEFT JOIN animal_cache ON animal.id = animal_cache.animal_id ".$sqlFilter;
        $records = $this->getManager()->getConnection()->query($sql)->fetchAll();

        $results = [];

        if($ignoreAnimalsWithAnExistingCache && $ignoreCacheBeforeDateString == null) {
            foreach ($records as $record) {
                if($record['animal_cache_id'] == null) {
                    $results[] = $record;
                }
            }
            return $results;

        } elseif($ignoreAnimalsWithAnExistingCache && $ignoreCacheBeforeDateString != null) {
            foreach ($records as $record) {
                if($record['animal_cache_id'] == null && $record['log_date'] >= $ignoreCacheBeforeDateString) {
                    $results[] = $record;
                }
            }
            return $results;

        } elseif(!$ignoreAnimalsWithAnExistingCache && $ignoreCacheBeforeDateString != null) {
            foreach ($records as $record) {
                if($record['log_date'] >= $ignoreCacheBeforeDateString) {
                    $results[] = $record;
                }
            }
            return $results;

        } else {
            return $records;
        }
    }


    /**
     * @param bool $ignoreAnimalsWithAnExistingCache
     * @param string $ignoreCacheBeforeDateString
     * @param int $locationId
     * @return array
     */
    public function getAnimalCacherInputDataPerLocation($ignoreAnimalsWithAnExistingCache = true, $ignoreCacheBeforeDateString = null, $locationId = null)
    {
        $filter = $locationId == null ? '' : " WHERE location_id = ".$locationId;
        return $this->getAnimalCacherInputData($ignoreAnimalsWithAnExistingCache, $ignoreCacheBeforeDateString, $filter);
    }


    /**
     * @param bool $ignoreAnimalsWithAnExistingCache
     * @param null $ignoreCacheBeforeDateString
     * @param $animalId
     * @return array
     */
    public function getAnimalCacherInputDataForAnimalAndAscendants($ignoreAnimalsWithAnExistingCache = true, $ignoreCacheBeforeDateString = null, $animalId) {

        $pedigreeUtil = new PedigreeUtil($this->getManager(), $animalId);

        $filter = " WHERE animal.id = ".$animalId;
        foreach ($pedigreeUtil->getParentIds() as $parentId) {
            $filter = $filter.' OR animal.id = '.$parentId;
        }

        return $this->getAnimalCacherInputData($ignoreAnimalsWithAnExistingCache, $ignoreCacheBeforeDateString, $filter);
    }


    /**
     * @param bool $ignoreAnimalsWithAnExistingCache
     * @param null $ignoreCacheBeforeDateString
     * @param int $locationId
     * @return array
     */
    public function getAnimalCacherInputDataForAnimalAndAscendantsByLocationId($ignoreAnimalsWithAnExistingCache = true, $ignoreCacheBeforeDateString = null, $locationId) {

        $animalIds = PedigreeUtil::findAnimalAndAscendantsOfLocationIdByJoinedSql($this->getConnection(), $locationId);

        if(count($animalIds) == 0) { return []; }

        $filter = " WHERE";
        foreach ($animalIds as $animalId) {
            $filter = $filter.' animal.id = '.$animalId.' OR ';
        }
        $filter = rtrim($filter, 'OR ');
        $animalIds = null;

        return $this->getAnimalCacherInputData($ignoreAnimalsWithAnExistingCache, $ignoreCacheBeforeDateString, $filter);
    }
}