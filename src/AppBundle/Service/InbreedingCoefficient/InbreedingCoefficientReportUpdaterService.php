<?php


namespace AppBundle\Service\InbreedingCoefficient;


use AppBundle\Enumerator\InbreedingCoefficientProcessSlot;
use AppBundle\model\ParentIdsPair;

class InbreedingCoefficientReportUpdaterService extends InbreedingCoefficientUpdaterServiceBase
{
    /**
     * @param  array|ParentIdsPair[] $parentIdsPairs
     * @param  bool $findGlobalMatch
     */
    public function generateInbreedingCoefficients(array $parentIdsPairs, bool $findGlobalMatch = false)
    {
        $this->generateInbreedingCoefficientsBase($parentIdsPairs, $findGlobalMatch,false);
    }

    /**
     * @param  array|ParentIdsPair[] $parentIdsPairs
     * @param  bool $findGlobalMatch
     */
    public function regenerateInbreedingCoefficients(array $parentIdsPairs, bool $findGlobalMatch = false)
    {
        $this->generateInbreedingCoefficientsBase($parentIdsPairs, $findGlobalMatch,true);
    }


    /**
     * @param  array|ParentIdsPair[]  $parentIdsPairs
     * @param  bool  $setFindGlobalMatch
     * @param  bool  $recalculate
     * @param  string  $processSlot
     */
    protected function generateInbreedingCoefficientsBase(
        array $parentIdsPairs, bool $setFindGlobalMatch, bool $recalculate,
        string $processSlot = InbreedingCoefficientProcessSlot::REPORT
    )
    {
        if (empty($parentIdsPairs)) {
            $this->logger->notice('ParentIdsPairs input is empty. Nothing will be processed');
            return;
        }

        $this->setProcessSlot($processSlot);

        $this->resetCounts();

        $groupedAnimalIdsSets = $this->getParentGroupedAnimalIdsByPairs($parentIdsPairs, $recalculate);
        $setCount = count($groupedAnimalIdsSets);

        $this->totalInbreedingCoefficientPairs = $setCount;
        $this->logMessageGroup = $setCount.' parent groups';

        $this->processGroupedAnimalIdsSets($groupedAnimalIdsSets, $recalculate, $setFindGlobalMatch);

        $this->writeBatchCount('Completed!');
    }


    private function processGroupedAnimalIdsSets(array $groupedAnimalIdsSets, bool $recalculate, bool $setFindGlobalMatch)
    {
        $this->refillParentsCalculationTables($groupedAnimalIdsSets);

        foreach ($groupedAnimalIdsSets as $groupedAnimalIdSet)
        {
            $this->processGroupedAnimalIdsSet($groupedAnimalIdSet, $recalculate, $setFindGlobalMatch);
        }

        $this->clearParentsCalculationTables();
    }

}
