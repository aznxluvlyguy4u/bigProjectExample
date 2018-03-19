<?php

namespace AppBundle\Entity;
use AppBundle\Constant\BreedIndexDiscriminatorTypeConstant;

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
    public function getIndexes($generationDate, $isIncludingOnlyAliveAnimals)
    {
        return $this->getBreedIndexes($generationDate, $isIncludingOnlyAliveAnimals, FertilityBreedIndex::class);
    }

    /**
     * @param \DateTime $generationDate
     * @param bool $isIncludingOnlyAliveAnimals
     * @return array|float[]
     * @throws \Exception
     */
    public function getValues($generationDate, $isIncludingOnlyAliveAnimals)
    {
        return $this->getBreedIndexValues($generationDate, $isIncludingOnlyAliveAnimals, BreedIndexDiscriminatorTypeConstant::FERTILITY);
    }
}