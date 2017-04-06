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
    function getInvoiceRules(Request $request);
    function updateInvoiceRule(Request $request);
    function createInvoiceRule(Request $request);
    function deleteInvoiceRule(Request $request, InvoiceRule $invoiceRule);
    function linkInvoiceRuleToInvoice(InvoiceRule $invoiceRule, Invoice $invoice);

}