<?php

namespace AppBundle\Entity;

/**
 * Class LambMeatBreedIndexRepository
 * @package AppBundle\Entity
 */
class LambMeatBreedIndexRepository extends BaseRepository {

    /**
     * @param $generationDate
     * @param $isIncludingOnlyAliveAnimals
     * @return array
     * @throws \Exception
     */
    public function getLambMeatIndexValues($generationDate, $isIncludingOnlyAliveAnimals)
    {
        //TODO function still needs to be implemented
        throw new \Exception('This function still needs to be implemented');

        return [];
    }

}