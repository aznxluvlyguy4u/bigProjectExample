<?php

namespace AppBundle\Controller;


use AppBundle\Entity\Invoice;
use AppBundle\Entity\InvoiceRuleTemplate;
use Symfony\Component\HttpFoundation\Request;

interface InvoiceRuleTemplateAPIControllerInterface
{
    function getInvoiceRules(Request $request);
    function updateInvoiceRule(Request $request);
    function createInvoiceRule(Request $request);
    function deleteInvoiceRule(Request $request, InvoiceRuleTemplate $invoiceRule);
}