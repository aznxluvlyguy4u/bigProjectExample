<?php

namespace AppBundle\Entity;
use AppBundle\Constant\BreedIndexDiscriminatorTypeConstant;

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
    public function getIndexes($generationDate, $isIncludingOnlyAliveAnimals)
    {
        return $this->getBreedIndexes($generationDate, $isIncludingOnlyAliveAnimals, WormResistanceBreedIndex::class);
    }

    /**
     * @param \DateTime $generationDate
     * @param bool $isIncludingOnlyAliveAnimals
     * @return array|float[]
     */
    public function getValues($generationDate, $isIncludingOnlyAliveAnimals)
    {
        return $this->getBreedIndexValues($generationDate, $isIncludingOnlyAliveAnimals, BreedIndexDiscriminatorTypeConstant::WORM_RESISTANCE);
    }
}