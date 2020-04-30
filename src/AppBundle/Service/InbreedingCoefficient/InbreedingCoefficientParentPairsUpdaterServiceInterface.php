<?php


namespace AppBundle\Service\InbreedingCoefficient;


use AppBundle\model\ParentIdsPair;

interface InbreedingCoefficientParentPairsUpdaterServiceInterface extends InbreedingCoefficientUpdaterServiceInterface
{
    /**
     * @param  array|ParentIdsPair[] $parentPairs
     * @param  bool  $recalculate
     */
    public function addPairs(array $parentPairs, bool $recalculate = false);

    /**
     * @param  ParentIdsPair $parentPair
     * @param  bool  $recalculate
     */
    public function addPair(ParentIdsPair $parentPair, bool $recalculate = false);
}
