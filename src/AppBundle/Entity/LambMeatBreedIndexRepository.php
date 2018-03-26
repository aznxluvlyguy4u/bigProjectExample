<?php

namespace AppBundle\Entity;
use AppBundle\Constant\BreedIndexDiscriminatorTypeConstant;

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
    public function getIndexes($generationDate, $isIncludingOnlyAliveAnimals)
    {
        return $this->getBreedIndexes($generationDate, $isIncludingOnlyAliveAnimals, LambMeatBreedIndex::class);
    }


    /**
     * @param \DateTime $generationDate
     * @param bool $isIncludingOnlyAliveAnimals
     * @return array|float[]
     * @throws \Exception
     */
    public function getValues($generationDate, $isIncludingOnlyAliveAnimals)
    {
        return $this->getBreedIndexValues($generationDate, $isIncludingOnlyAliveAnimals, BreedIndexDiscriminatorTypeConstant::LAMB_MEAT);
    }
}