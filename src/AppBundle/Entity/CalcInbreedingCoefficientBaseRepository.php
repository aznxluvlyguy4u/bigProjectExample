<?php


namespace AppBundle\Entity;


use AppBundle\Setting\InbreedingCoefficientSetting;

class CalcInbreedingCoefficientBaseRepository extends CalcTableBaseRepository
{
    protected function maxGenerations(): int
    {
        return InbreedingCoefficientSetting::DEFAULT_GENERATION_OF_ASCENDANTS;
    }

    protected function animalYearAndMonthFilter(int $year, int $month): string
    {
        return "(date_part('YEAR', a.date_of_birth) = $year AND date_part('MONTH', a.date_of_birth) = $month)";
    }
}
