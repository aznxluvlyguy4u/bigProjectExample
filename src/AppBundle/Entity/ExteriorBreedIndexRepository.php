<?php

namespace AppBundle\Entity;
use AppBundle\Constant\BreedIndexDiscriminatorTypeConstant;

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
    public function getIndexes($generationDate, $isIncludingOnlyAliveAnimals)
    {
        return $this->getBreedIndexes($generationDate, $isIncludingOnlyAliveAnimals, ExteriorBreedIndex::class);
    }

    /**
     * @param \DateTime $generationDate
     * @param bool $isIncludingOnlyAliveAnimals
     * @return array|float[]
     */
    public function getValues($generationDate, $isIncludingOnlyAliveAnimals)
    {
        return $this->getBreedIndexValues($generationDate, $isIncludingOnlyAliveAnimals, BreedIndexDiscriminatorTypeConstant::EXTERIOR);
    }
}