<?php


namespace AppBundle\Entity;


interface BreedIndexRepositoryInterface
{
    /**
     * @param \DateTime $generationDate
     * @param boolean $isIncludingOnlyAliveAnimals
     * @return BreedIndex[]|array
     */
    function getValues($generationDate, $isIncludingOnlyAliveAnimals);
}