<?php
/**
 * Created by IntelliJ IDEA.
 * User: werner
 * Date: 6-4-17
 * Time: 10:49
 */

namespace AppBundle\Controller;

use AppBundle\Entity\InvoiceRule;
use Symfony\Component\HttpFoundation\Request;

interface InvoiceRuleTemplateAPIControllerInterface
{
    public function getInvoiceRuleTemplates(Request $request);
    public function createInvoiceRuleTemplate(Request $request);
    public function updateInvoiceRuleTemplate(Request $request);
    public function deleteInvoiceRuleTemplate(Request $request, InvoiceRule $invoiceRuleTemplate);
}