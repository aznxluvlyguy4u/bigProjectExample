<?php

namespace AppBundle\Controller;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\InvoiceRule;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;


/**
 * Class InvoiceRuleAPIController
 * @package AppBundle\Controller
 * @Route("api/v1/invoice-rules")
 */
class InvoiceRuleAPIController extends APIController implements InvoiceRuleAPIControllerInterface
{
    /**
     * @Route("")
     * @param Request $request
     * @Method("GET")
     * @return jsonResponse
     */
    public function getInvoiceRules(Request $request)
    {
        return $this->get('app.invoice.rule')->getInvoiceRules($request);
    }

    /**
     * @Route("")
     * @param Request $request
     * @Method("POST")
     * @return jsonResponse
     */
    public function createInvoiceRule(Request $request)
    {
        return $this->get('app.invoice.rule')->createInvoiceRule($request);
    }

    /**
     * @Route("")
     * @param Request $request
     * @Method("PUT")
     * @return jsonResponse
     */
    public function updateInvoiceRule(Request $request)
    {
        return $this->get('app.invoice.rule')->updateInvoiceRule($request);
    }

    /**
     * @Route("/{id}")
     * @param Request $request
     * @param InvoiceRule $invoiceRule
     * @Method("DELETE")
     * @return jsonResponse
     */
    public function deleteInvoiceRule(Request $request, InvoiceRule $invoiceRule)
    {
        return $this->get('app.invoice.rule')->deleteInvoiceRule($request, $invoiceRule);
    }

}
