<?php


namespace AppBundle\Service\InbreedingCoefficient;


use AppBundle\Enumerator\InbreedingCoefficientProcessSlot;

class InbreedingCoefficientUbnUpdaterService extends InbreedingCoefficientParentPairsUpdaterService
{

    /**
     * @param  string  $ubn
     */
    public function generateForAnimalsAndLittersOfUbn(string $ubn)
    {
        $this->generateForAnimalsAndLittersOfUbnBase($ubn,false);
    }

    /**
     * @param  string  $ubn
     */
    public function regenerateForAnimalsAndLittersOfUbn(string $ubn)
    {
        $this->generateForAnimalsAndLittersOfUbnBase($ubn,true);
    }

    protected function generateForAnimalsAndLittersOfUbnBase(string $ubn, bool $recalculate)
    {
        $parentIdsPairs = $this->inbreedingCoefficientRepository->findParentIdsPairsWithMissingInbreedingCoefficient(0, $recalculate, $ubn);
        $this->generateInbreedingCoefficientsBase(
            $parentIdsPairs,false, $recalculate,
            InbreedingCoefficientProcessSlot::SMALL
        );
    }

}
