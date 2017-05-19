<?php

namespace AppBundle\Controller;

use AppBundle\Entity\InvoiceRule;
use Symfony\Component\HttpFoundation\Request;

interface InvoiceRuleAPIControllerInterface
{
    public function getInvoiceRuleTemplates(Request $request);
    public function createInvoiceRuleTemplate(Request $request);
    public function updateInvoiceRuleTemplate(Request $request);
    public function deleteInvoiceRuleTemplate(Request $request, InvoiceRule $invoiceRuleTemplate);
}