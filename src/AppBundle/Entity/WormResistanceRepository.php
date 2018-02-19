<?php

namespace AppBundle\Entity;

/**
 * Class WormResistanceRepository
 * @package AppBundle\Entity
 */
class WormResistanceRepository extends BreedIndexRepository implements BreedIndexRepositoryInterface {

    /**
     * @param \DateTime $generationDate
     * @param bool $isIncludingOnlyAliveAnimals
     * @return array|WormResistanceBreedIndex[]
     */
    public function getValues($generationDate, $isIncludingOnlyAliveAnimals)
    {
        return $this->getBreedIndexValues($generationDate, $isIncludingOnlyAliveAnimals, WormResistanceBreedIndex::class);
    }

}