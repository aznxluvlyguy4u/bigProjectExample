<?php
/**
 * Created by IntelliJ IDEA.
 * User: werner
 * Date: 14-4-17
 * Time: 11:07
 */

namespace AppBundle\Output;


use AppBundle\Entity\Invoice;
use AppBundle\Entity\InvoiceRuleLocked;

class InvoiceOutput
{
    /**
     * @param $invoices
     * @return array
     */
    public static function createInvoiceOutputList($invoices){
        $results = array();
        /** @var Invoice $invoice */
        foreach ($invoices as $invoice) {
            if (!$invoice->isDeleted()) {
                $results[] = self::createInvoiceOutput($invoice);
            }
        }
        return $results;
    }

    /**
     * @param Invoice $invoice
     * @return array
     */
    public static function createInvoiceOutput($invoice){
        return array(
            'id' => $invoice->getId(),
            'company_name' => $invoice->getCompanyName(),
            'company_vat_number' => $invoice->getCompanyVatNumber(),
            'ubn' => $invoice->getUbn(),
            'invoice_number' => $invoice->getInvoiceNumber(),
            'invoice_date' => $invoice->getInvoiceDate(),
            'company' => CompanyOutput::createCompanyOutputNoInvoices($invoice->getCompany()),
            'invoice_rules' => InvoiceRuleOutput::createInvoiceRuleOutputList($invoice->getInvoiceRules()),
            'invoice_rules_locked' => InvoiceRuleLockedOutput::createInvoiceRuleOutputList($invoice->getLockedInvoiceRules())
        );
    }
}