<?php


namespace AppBundle\Util;


use AppBundle\Entity\Animal;
use AppBundle\Entity\Litter;
use AppBundle\model\ParentIdsPair;

class ParentIdsPairUtil
{
    /**
     * All animals in collection should have both a mother and father
     *
     * @param array|Animal[] $animals
     * @return array|ParentIdsPair[]
     */
    public static function fromAnimals(array $animals): array {
        $parentIdsPairs = array_map(function(Animal $animal) {
            return new ParentIdsPair(
                $animal->getParentFatherId(),
                $animal->getParentMotherId()
            );
        }, $animals);
        return self::uniqueParentIdsPairs($parentIdsPairs);
    }

    /**
     * All litters in collection should have both a mother and father
     *
     * @param array|Litter[] $litters
     * @return array|ParentIdsPair[]
     */
    public static function fromLitters(array $litters): array {
        $parentIdsPairs = array_map(function(Litter $litter) {
            return new ParentIdsPair(
                $litter->getAnimalFather()->getId(),
                $litter->getAnimalMother()->getId()
            );
        }, $litters);
        return self::uniqueParentIdsPairs($parentIdsPairs);
    }

    /**
     * @param array|ParentIdsPair[] $parentIdsPairs
     * @return array
     */
    private static function uniqueParentIdsPairs(array $parentIdsPairs): array {
        $uniqueResults = [];
        foreach ($parentIdsPairs as $parentIdsPair) {
            $key = $parentIdsPair->getRamId().'-'.$parentIdsPair->getEweId();
            if (key_exists($key, $parentIdsPair)) {
                continue;
            }
            $uniqueResults[$key] = $parentIdsPair;
        }
        return $uniqueResults;
    }
}