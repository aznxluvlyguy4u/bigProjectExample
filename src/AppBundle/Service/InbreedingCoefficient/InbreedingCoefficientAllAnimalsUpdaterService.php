<?php


namespace AppBundle\Service\InbreedingCoefficient;


use AppBundle\Enumerator\InbreedingCoefficientProcessSlot;
use AppBundle\model\metadata\YearMonthData;

class InbreedingCoefficientAllAnimalsUpdaterService extends InbreedingCoefficientUpdaterServiceBase
{

    public function generateForAllAnimalsAndLitters()
    {
        $this->generateForAllAnimalsAndLitterBase(false, true);
    }

    public function regenerateForAllAnimalsAndLitters()
    {
        $this->generateForAllAnimalsAndLitterBase(false, true);
    }


    protected function generateForAllAnimalsAndLitterBase(bool $recalculate, bool $setFindGlobalMatch)
    {
        $this->setProcessSlot(InbreedingCoefficientProcessSlot::ADMIN);

        $this->resetCounts();

        $this->updateAnimalsWithoutParents();

        $yearsAndMonthsAnimalIdsSets = $this->calcInbreedingCoefficientParentRepository->getAllYearsAndMonths();

        if ($recalculate) {
            $this->totalInbreedingCoefficientPairs = array_sum(
                array_map(function (YearMonthData $yearMonthData) {
                    return $yearMonthData->getCount();
                }, $yearsAndMonthsAnimalIdsSets)
            );
        } else {
            $this->totalInbreedingCoefficientPairs = array_sum(
                array_map(function (YearMonthData $yearMonthData) {
                    return $yearMonthData->getMissingInbreedingCoefficientCount();
                }, $yearsAndMonthsAnimalIdsSets)
            );

            $alreadyExistsCount = array_sum(
                array_map(function (YearMonthData $yearMonthData) {
                    return $yearMonthData->getNonMissingCount();
                }, $yearsAndMonthsAnimalIdsSets)
            );
            $this->logger->notice("$alreadyExistsCount inbreeding coefficient pairs skipped (already exist). Includes animals without both parents.");

            $yearsAndMonthsAnimalIdsSets = array_filter(
                $yearsAndMonthsAnimalIdsSets, function (YearMonthData $yearMonthData) {
                return $yearMonthData->hasMissingInbreedingCoefficients();
            }
            );
        }

        foreach ($yearsAndMonthsAnimalIdsSets as $period)
        {
            $this->generateForAllAnimalsAndLitterBasePeriodLoop(
                $period, $recalculate, $setFindGlobalMatch
            );
        }

        $this->writeBatchCount('Completed!');
    }


    private function generateForAllAnimalsAndLitterBasePeriodLoop(
        YearMonthData $period, bool $recalculate, bool $setFindGlobalMatch
    )
    {
        $year = $period->getYear();
        $month = $period->getMonth();

        $this->logMessageGroup = "$year-$month (year-month)";

        $groupedAnimalIdsSets = $this->getParentGroupedAnimalIdsByYearAndMonth($year, $month);
        $this->writeBatchCount();

        foreach ($groupedAnimalIdsSets as $groupedAnimalIdsSet)
        {
            $this->processGroupedAnimalIdsSets([$groupedAnimalIdsSet], $recalculate, $setFindGlobalMatch);
        }
    }


    /**
     * @param  array  $groupedAnimalIdsSets
     * @param  bool  $recalculate
     * @param  bool  $setFindGlobalMatch
     */
    private function processGroupedAnimalIdsSets(array $groupedAnimalIdsSets, bool $recalculate, bool $setFindGlobalMatch)
    {
        $this->refillParentsCalculationTables($groupedAnimalIdsSets);

        foreach ($groupedAnimalIdsSets as $groupedAnimalIdSet)
        {
            $this->processGroupedAnimalIdsSet($groupedAnimalIdSet, $recalculate, $setFindGlobalMatch);
        }

        $this->clearParentsCalculationTables();
    }



    private function updateAnimalsWithoutParents()
    {
        $this->logger->notice("Remove update mark if animal now has both parents...");
        $sql1 = "UPDATE animal SET inbreeding_coefficient_match_updated_at = NULL
WHERE inbreeding_coefficient_id ISNULL AND inbreeding_coefficient_match_updated_at NOTNULL
    AND parent_father_id NOTNULL AND parent_mother_id NOTNULL";
        $this->em->getConnection()->executeQuery($sql1);

        $this->logger->notice("Add update mark to animals without parents...");
        $sql2 = "UPDATE animal SET inbreeding_coefficient_match_updated_at = NOW()
WHERE EXISTS(
              SELECT
                  a.id
              FROM animal a
              WHERE (parent_father_id ISNULL OR parent_mother_id ISNULL OR date_of_birth ISNULL)
                AND inbreeding_coefficient_match_updated_at ISNULL
                AND a.id = animal.id
          )";
        $this->em->getConnection()->executeQuery($sql2);
        $this->logger->notice("Finished updating update marks for animals without parents");
    }


    private function getParentGroupedAnimalIdsByYearAndMonth(int $year, int $month, bool $recalculate = false): array
    {
        return $this->getParentGroupedAnimalAndLitterIds(
            "date_part('YEAR', date_of_birth) = $year AND date_part('MONTH', date_of_birth) = $month AND",
            $recalculate
        );
    }
}
