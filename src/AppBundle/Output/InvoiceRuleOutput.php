<?php
/**
 * Created by IntelliJ IDEA.
 * User: werner
 * Date: 14-4-17
 * Time: 11:33
 */

namespace AppBundle\Output;


use AppBundle\Entity\InvoiceRule;

class InvoiceRuleOutput
{
    public static function createInvoiceRuleOutputList($invoiceRules) {
        $results = array();
        foreach ($invoiceRules as $invoiceRule) {
            $results[] = self::createInvoiceRuleOutput($invoiceRule);
        }
        return $results;
    }

    /**
     * @param InvoiceRule $invoiceRule
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