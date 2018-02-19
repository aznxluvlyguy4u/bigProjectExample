<?php

namespace AppBundle\Entity;

/**
 * Class ExteriorBreedIndexRepository
 * @package AppBundle\Entity
 */
class ExteriorBreedIndexRepository extends BreedIndexRepository implements BreedIndexRepositoryInterface {

    /**
     * @param \DateTime $generationDate
     * @param bool $isIncludingOnlyAliveAnimals
     * @return array|ExteriorBreedIndex[]
     */
    public function getValues($generationDate, $isIncludingOnlyAliveAnimals)
    {
        return $this->getBreedIndexValues($generationDate, $isIncludingOnlyAliveAnimals, ExteriorBreedIndex::class);
    }

}