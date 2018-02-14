<?php

namespace AppBundle\Controller;


use AppBundle\Entity\InvoiceRuleTemplate;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;


/**
 * Class InvoiceRuleTemplateAPIController
 * @package AppBundle\Controller
 * @Route("api/v1/invoice-rule-templates")
 */
class InvoiceRuleTemplateAPIController extends APIController implements InvoiceRuleTemplateAPIControllerInterface
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
     * @Method("DELETE")
     * @return jsonResponse
     */
    public function deleteInvoiceRule(Request $request, InvoiceRuleTemplate $invoiceRule)
    {
        return $this->get('app.invoice.rule')->deleteInvoiceRule($request, $invoiceRule);
    }

}