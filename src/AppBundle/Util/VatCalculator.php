<?php


namespace AppBundle\Util;


use AppBundle\Entity\InvoiceRuleSelection;
use AppBundle\Entity\VatBreakdown;
use AppBundle\Entity\VatBreakdownRecord;
use Doctrine\Common\Collections\ArrayCollection;

class VatCalculator
{
    const DECIMAL_COUNT_IN_SUB_TOTAL = 3;
    const DECIMAL_COUNT_IN_GRAND_TOTAL = 2;


    /**
     * @param ArrayCollection|InvoiceRuleSelection[] $invoiceRuleSelections
     * @return VatBreakdown
     */
    public static function calculateVatBreakdown($invoiceRuleSelections)
    {
        $vatBreakdown = new VatBreakdown();
        /** @var VatBreakdownRecord[] $vatBreakdownRecords */
        $vatBreakdownRecords = [];

        // Group values
        foreach ($invoiceRuleSelections as $invoiceRuleSelection) {
            $amount = $invoiceRuleSelection->getAmount();
            $rule = $invoiceRuleSelection->getInvoiceRule();

            if (!$rule || !$amount) {
                continue;
            }

            $vatPercentageRate = $rule->getVatPercentageRate();
            if ($vatPercentageRate === 0 || $vatPercentageRate === null) {
                continue;
            }

            $vatPercentageRateKey = strval($vatPercentageRate);

            $record = ArrayUtil::get($vatPercentageRateKey, $vatBreakdownRecords);
            if (!$record) {
                $record = new VatBreakdownRecord();
                $record->setVatPercentageRate($vatPercentageRate);
            }

            $singlePriceExclVat = $rule->getPriceExclVat();
            $priceExclVat = $singlePriceExclVat * $amount;
            $vat = $priceExclVat * ($vatPercentageRate/100);

            // Only sum up the price incl vat after rounding the values

            $record->addToPriceExclVatTotal($priceExclVat);
            $record->addToVat($vat);

            $vatBreakdownRecords[$vatPercentageRateKey] = $record;
        }


        // Round values
        $grandTotalPriceExclVat = 0;
        $grandTotalPriceVat = 0;
        $grandTotalPriceInclVat = 0;
        foreach ($vatBreakdownRecords as $vatPercentageRateKey => $record) {
            $priceExclVat = round($record->getPriceExclVatTotal(), self::DECIMAL_COUNT_IN_SUB_TOTAL);
            $vat = round($record->getVat(), self::DECIMAL_COUNT_IN_SUB_TOTAL);

            $record
                ->setPriceExclVatTotal($priceExclVat)
                ->setVat($vat)
            ;

            $vatBreakdownRecords[$vatPercentageRateKey] = $record;

            $grandTotalPriceExclVat += $priceExclVat;
            $grandTotalPriceVat += $vat;
            $grandTotalPriceInclVat += $priceExclVat + $vat;
        }

        $vatBreakdown
            ->setRecords(new ArrayCollection($vatBreakdownRecords))
            ->setTotalExclVat($grandTotalPriceExclVat)
            ->setVat($grandTotalPriceVat)
            ->setTotalInclVat(round($grandTotalPriceInclVat, self::DECIMAL_COUNT_IN_GRAND_TOTAL))
        ;
        return $vatBreakdown;
    }
}