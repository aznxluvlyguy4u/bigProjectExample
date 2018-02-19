<?php

namespace AppBundle\Entity;

/**
 * Class LambMeatBreedIndexRepository
 * @package AppBundle\Entity
 */
class LambMeatBreedIndexRepository extends BreedIndexRepository implements BreedIndexRepositoryInterface {

    /**
     * @param \DateTime $generationDate
     * @param bool $isIncludingOnlyAliveAnimals
     * @return array|LambMeatBreedIndex[]
     */
    public function getValues($generationDate, $isIncludingOnlyAliveAnimals)
    {
        return $this->getBreedIndexValues($generationDate, $isIncludingOnlyAliveAnimals, LambMeatBreedIndex::class);
    }

}