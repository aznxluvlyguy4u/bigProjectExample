<?php


namespace AppBundle\Service\InbreedingCoefficient;


use AppBundle\Enumerator\InbreedingCoefficientProcessSlot;
use AppBundle\model\ParentIdsPair;

class InbreedingCoefficientReportUpdaterService extends InbreedingCoefficientUpdaterServiceBase
{
    /**
     * @param  array|ParentIdsPair[] $parentIdsPairs
     */
    public function generateInbreedingCoefficients(array $parentIdsPairs)
    {
        $this->generateInbreedingCoefficientsBase($parentIdsPairs,false);
    }

    /**
     * @param  array|ParentIdsPair[] $parentIdsPairs
     */
    public function regenerateInbreedingCoefficients(array $parentIdsPairs)
    {
        $this->generateInbreedingCoefficientsBase($parentIdsPairs,true);
    }


    /**
     * @param  array|ParentIdsPair[]  $parentIdsPairs
     * @param  bool  $recalculate
     * @param  string  $processSlot
     */
    protected function generateInbreedingCoefficientsBase(
        array $parentIdsPairs, bool $recalculate,
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

        $this->processGroupedAnimalIdsSets($groupedAnimalIdsSets, $recalculate);

        $this->writeBatchCount('Completed!');
    }


    private function processGroupedAnimalIdsSets(array $groupedAnimalIdsSets, bool $recalculate)
    {
        $this->refillParentsCalculationTables($groupedAnimalIdsSets);

        foreach ($groupedAnimalIdsSets as $groupedAnimalIdSet)
        {
            $this->processGroupedAnimalIdsSet($groupedAnimalIdSet, $recalculate);
        }

        $this->clearParentsCalculationTables();
    }

}
