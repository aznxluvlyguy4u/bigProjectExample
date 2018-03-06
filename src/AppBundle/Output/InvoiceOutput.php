<?php

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
     * @param $invoices
     * @return array
     */
    public static function createInvoiceOutputListNoCompany($invoices){
        $results = array();
        /** @var Invoice $invoice */
        foreach ($invoices as $invoice) {
            if (!$invoice->isDeleted()) {
                $results[] = self::createInvoiceOutputNoCompany($invoice);
            }
        }
        return $results;
    }

    /**
     * @param Invoice $invoice
     * @return array
     */
    public static function createInvoiceOutput($invoice){
        $res = array(
            'id' => $invoice->getId(),
            'company_name' => $invoice->getCompanyName(),
            'company_vat_number' => $invoice->getCompanyVatNumber(),
            'ubn' => $invoice->getUbn(),
            'status' => $invoice->getStatus(),
            'invoice_number' => $invoice->getInvoiceNumber(),
            'total' => $invoice->getTotal(),
            'invoice_date' => $invoice->getInvoiceDate(),
            'invoice_rule_selections' => InvoiceRuleOutput::createInvoiceRuleOutputList($invoice->getInvoiceRuleSelections())
        );
        if ($invoice->getMollieId() != null){
            $res['mollie_id'] = $invoice->getMollieId();
        }

        if ($invoice->getCompany() != null){
            $res['company'] = CompanyOutput::createCompanyOutputNoInvoices($invoice->getCompany());
        }

        if($invoice->getSenderDetails() != null){
            $res['sender_details'] = InvoiceSenderDetailsOutput::createInvoiceSenderDetailsOutput($invoice->getSenderDetails());
        }
        return $res;
    }

    /**
     * @param Invoice $invoice
     * @return array
     */
    public static function createInvoiceOutputNoCompany($invoice){
        $res = array('id' => $invoice->getId(),
            'company_id' => $invoice->getCompanyLocalId(),
            'company_name' => $invoice->getCompanyName(),
            'company_vat_number' => $invoice->getCompanyVatNumber(),
            'status' => $invoice->getStatus(),
            'ubn' => $invoice->getUbn(),
            'invoice_number' => $invoice->getInvoiceNumber(),
            'total' => $invoice->getTotal(),
            'invoice_date' => $invoice->getInvoiceDate(),
            'invoice_rule_selections' => InvoiceRuleOutput::createInvoiceRuleOutputList($invoice->getInvoiceRuleSelections())
        );
        if ($invoice->getMollieId() != null){
            $res['mollie_id'] = $invoice->getMollieId();
        }
        if($invoice->getSenderDetails() != null){
            $res['sender_details'] = InvoiceSenderDetailsOutput::createInvoiceSenderDetailsOutput($invoice->getSenderDetails());
        }
        return $res;
    }
}