<?php


namespace AppBundle\Entity;


use AppBundle\Setting\InbreedingCoefficientSetting;

class CalcInbreedingCoefficientBaseRepository extends CalcTableBaseRepository
{
    protected function maxGenerations(): int
    {
        return InbreedingCoefficientSetting::DEFAULT_GENERATION_OF_ASCENDANTS;
    }

    public function animalYearAndMonthFilter(int $year, int $month, string $alias): string
    {
        return "(date_part('YEAR', $alias.date_of_birth) = $year AND date_part('MONTH', $alias.date_of_birth) = $month)";
    }

    protected function precision(): int
    {
        $precision = InbreedingCoefficientSetting::DECIMAL_PRECISION;
        $minValue = 4;
        $maxValue = 12;
        $constant = 'InbreedingCoefficientSetting::DECIMAL_PRECISION';

        switch (true) {
            case !intval($precision): throw new \Exception($constant.' must be an integer');
            case $precision < 0: throw new \Exception($constant.' must be positive');
            case $precision < $minValue: throw new \Exception($constant.' must be at least '.$minValue);
            case $precision > $maxValue: throw new \Exception($constant.' cannot be greater than '.$maxValue);
            default: return $precision;
        }
    }
}
