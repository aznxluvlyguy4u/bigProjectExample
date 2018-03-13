<?php

namespace AppBundle\Controller;


use AppBundle\Entity\InvoiceRule;
use Symfony\Component\HttpFoundation\Request;

interface InvoiceRuleAPIControllerInterface
{
    function getInvoiceRules(Request $request);
    function updateInvoiceRule(Request $request);
    function createInvoiceRule(Request $request);
    function deleteInvoiceRule(Request $request, InvoiceRule $invoiceRule);
}