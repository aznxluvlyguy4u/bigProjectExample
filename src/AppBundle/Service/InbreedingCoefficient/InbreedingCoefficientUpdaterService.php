<?php


namespace AppBundle\Service\InbreedingCoefficient;


use AppBundle\Enumerator\InbreedingCoefficientProcessSlot;
use AppBundle\model\ParentIdsPair;

class InbreedingCoefficientUpdaterService extends InbreedingCoefficientUpdaterServiceBase implements InbreedingCoefficientUpdaterServiceInterface
{


    public function setManualAdminProcessSlot()
    {
        $this->setProcessSlot(InbreedingCoefficientProcessSlot::_1);
    }


    public function setReportProcessSlot()
    {
        $this->setProcessSlot(InbreedingCoefficientProcessSlot::_2);
    }


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

    public function generateForAllAnimalsAndLitters()
    {
        $this->generateForAllAnimalsAndLitterBase(false, true);
    }

    public function regenerateForAllAnimalsAndLitters()
    {
        $this->generateForAllAnimalsAndLitterBase(false, true);
    }

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

}
