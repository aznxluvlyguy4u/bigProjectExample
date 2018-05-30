<?php


namespace AppBundle\Output;


use AppBundle\Entity\Animal;

class BreedValuesOutput extends OutputServiceBase
{
    public function get(Animal $animal)
    {
        if($animal->getLatestBreedGrades() != null) {
            return $this->getSerializer()->normalizeToArray($animal->getLatestBreedGrades());
        }

        return [];
    }
}