<?php

namespace AppBundle\Entity;

/**
 * Class FertilityBreedIndexRepository
 * @package AppBundle\Entity
 */
class FertilityBreedIndexRepository extends BreedIndexRepository implements BreedIndexRepositoryInterface {

    /**
     * @param \DateTime $generationDate
     * @param bool $isIncludingOnlyAliveAnimals
     * @return array|FertilityBreedIndex[]
     */
    public function getValues($generationDate, $isIncludingOnlyAliveAnimals)
    {
        return $this->getBreedIndexValues($generationDate, $isIncludingOnlyAliveAnimals, FertilityBreedIndex::class);
    }

}