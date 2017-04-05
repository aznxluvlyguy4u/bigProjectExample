<?php
/**
 * Created by IntelliJ IDEA.
 * User: werner
 * Date: 5-4-17
 * Time: 14:45
 */

namespace AppBundle\Controller;


use AppBundle\Entity\Invoice;
use AppBundle\Entity\InvoiceRule;
use Symfony\Component\HttpFoundation\Request;

interface InvoiceRuleAPIControllerInterface
{
    function getInvoiceRules();
    function updateInvoiceRule(Request $request, InvoiceRule $invoiceRule);
    function createInvoiceRule(Request $request);
    function deleteInvoiceRule(InvoiceRule $invoiceRule);
    function linkInvoiceRuleToInvoice(InvoiceRule $invoiceRule, Invoice $invoice);

}