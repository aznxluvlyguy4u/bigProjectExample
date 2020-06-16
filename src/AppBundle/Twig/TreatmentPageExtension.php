<?php

namespace AppBundle\Twig;



use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TreatmentPageExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('treatmentPageCount', [$this, 'pagesOnlyContainingTreatments']),
            new TwigFunction('treatmentOffset', [$this, 'getTreatmentOffset']),
            new TwigFunction('eweCardOffspringRemainingOnLastPage', [$this, 'getEweCardOffspringRemainingOnLastPage']),
        ];
    }

    public function pagesOnlyContainingTreatments(array $treatments, int $maxRowsPagex, int $maxCombinedRowsPagex, int $remainingOffsprings): int
    {
        $maxMedicationRowsPage1 = $maxCombinedRowsPagex - $remainingOffsprings;

        $medicationRows = 0;
        $pageCount = 0;
        $maxRowsOnPage = $maxMedicationRowsPage1;

        foreach ($treatments as $treatment) {
            $medicationsCountPerTreatment = count( $treatment['medications']);

            if ($medicationRows + $medicationsCountPerTreatment > $maxRowsOnPage) {
                // start on new page
                $pageCount++;
                $medicationRows = 0;
                $maxRowsOnPage = $maxRowsPagex;
            }

            $medicationRows += $medicationsCountPerTreatment;
        }
        return $pageCount;
    }


    public function getTreatmentOffset(
        array $treatments, int $maxRowsPagex, int $maxCombinedRowsPagex,
        int $remainingOffsprings, int $previousTreatmentPageOrdinal
    ): int
    {
        $maxMedicationRowsPage1 = $maxCombinedRowsPagex - $remainingOffsprings;

        $medicationRows = 0;
        $pageCount = 0;
        $maxRowsOnPage = $maxMedicationRowsPage1;

        $treatmentCount = 0;

        foreach ($treatments as $treatment) {
            $treatmentCount++;
            $medicationsCountPerTreatment = count( $treatment['medications']);

            if ($medicationRows + $medicationsCountPerTreatment > $maxRowsOnPage) {
                // start on new page
                $pageCount++;
                $medicationRows = 0;
                $maxRowsOnPage = $maxRowsPagex;
            }

            if ($pageCount > $previousTreatmentPageOrdinal) {
                // Offset = total of medication rows in previous pages
                return $treatmentCount;
            }

            $medicationRows += $medicationsCountPerTreatment;
        }
        return $treatmentCount;
    }


    public function getEweCardOffspringRemainingOnLastPage(int $totalOffspring, int $maxRowsPage1, int $maxRowsPagex): int
    {
        if ($totalOffspring < $maxRowsPage1) {
            return $totalOffspring - $maxRowsPage1;
        }

        $totalOffspringAfterPage1 = $totalOffspring - $maxRowsPage1;
        return $totalOffspringAfterPage1 % $maxRowsPagex;
    }
}
