<?php

namespace AppBundle\Entity;

/**
 * Class AnimalCacheRepository
 * @package AppBundle\Entity
 */
class AnimalCacheRepository extends BaseRepository {


    /**
     * @param bool $ignoreAnimalsWithAnExistingCache
     * @param string $ignoreCacheBeforeDateString
     * @return array
     */
    public function getAnimalCacherInputData($ignoreAnimalsWithAnExistingCache = true, $ignoreCacheBeforeDateString = null)
    {
        $sql = "SELECT animal.id as animal_id, gender, date_of_birth, breed_type, animal_cache.id as animal_cache_id, animal_cache.log_date
                FROM animal LEFT JOIN animal_cache ON animal.id = animal_cache.animal_id";
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

}