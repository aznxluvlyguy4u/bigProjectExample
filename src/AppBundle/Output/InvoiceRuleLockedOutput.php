<?php

namespace AppBundle\Output;


use AppBundle\Entity\InvoiceRuleLocked;

class InvoiceRuleLockedOutput
{
    public static function createInvoiceRuleOutputList($invoiceRules) {
        $results = array();
        foreach ($invoiceRules as $invoiceRule) {
            $results[] = self::createInvoiceRuleOutput($invoiceRule);
        }
        return $results;
    }

    /**
     * @param InvoiceRuleLocked $invoiceRule
     * @return array
     */
    private static function createInvoiceRuleOutput($invoiceRule){
        return array(
            'id' => $invoiceRule->getId(),
            'description' => $invoiceRule->getDescription(),
            'vat_percentage_rate' => $invoiceRule->getVatPercentageRate(),
            'price_excl_vat' => $invoiceRule->getPriceExclVat(),
            'sort_order' => $invoiceRule->getSortOrder(),
            'category' => $invoiceRule->getCategory(),
            'type' => $invoiceRule->getType()
        );
    }
}