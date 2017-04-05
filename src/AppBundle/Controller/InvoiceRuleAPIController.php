<?php
/**
 * Created by IntelliJ IDEA.
 * User: werner
 * Date: 5-4-17
 * Time: 14:45
 */

namespace AppBundle\Controller;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Invoice;
use AppBundle\Entity\InvoiceRule;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

class InvoiceRuleAPIController extends APIController implements InvoiceRuleAPIControllerInterface
{
    /**
     * @return JsonResponse
     * @Method("GET")
     * @Route("")
     */
    public function getInvoiceRules()
    {
        // TODO: Implement deleteInvoiceRule() method.
    }

    /**
     * @param Request $request
     * @param InvoiceRule $invoiceRule
     * @Method("PUT")
     * @Route("/{invoiceRule}")
     */
    public function updateInvoiceRule(Request $request, InvoiceRule $invoiceRule)
    {
        // TODO: Implement deleteInvoiceRule() method.
    }

    /**
     * @param Request $request
     * @Method("POST")
     * @Route("")
     */
    public function createInvoiceRule(Request $request)
    {
        // TODO: Implement deleteInvoiceRule() method.
    }

    /**
     * @param InvoiceRule $invoiceRule
     * @Method("DELETE")
     * @Route("/{invoiceRule}")
     */
    public function deleteInvoiceRule(InvoiceRule $invoiceRule)
    {
        // TODO: Implement deleteInvoiceRule() method.
    }

    /**
     * @param InvoiceRule $invoiceRule
     * @param Invoice $invoice
     * @Method("PUT")
     * @Route("{invoice}/{invoiceRule}")
     */
    public function linkInvoiceRuleToInvoice(InvoiceRule $invoiceRule, Invoice $invoice)
    {
        // TODO: Implement linkInvoiceRuleToInvoice() method.
    }

}