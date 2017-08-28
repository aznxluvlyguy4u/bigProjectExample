<?php

namespace AppBundle\Controller;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\InvoiceRule;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Invoice;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;


/**
 * Class InvoiceAPIController
 * @package AppBundle\Controller
 * @Route("/api/v1/invoices")
 */
class InvoiceAPIController extends APIController implements InvoiceAPIControllerInterface
{
    /**
     *
     * @ApiDoc(
     *   section = "Invoices",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Retrieve all invoices"
     * )
     * @Method("GET")
     * @Route("")
     * @return JsonResponse
     */
    function getInvoices(Request $request)
    {
        return $this->get('app.invoice')->getInvoices($request);
    }

    /**
     *
     * @ApiDoc(
     *   section = "Invoices",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Retrieve a specific invoice"
     * )
     *
     * @Method("GET")
     * @Route("/{id}")
     *
     */
    function getInvoice($id)
    {
        return $this->get('app.invoice')->getInvoice($id);
    }

    /**
     * @ApiDoc(
     *   section = "Invoices",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Create an invoice"
     * )
     *
     * @param Request $request
     * @Method("POST")
     * @Route("")
     * @return JsonResponse
     */
    function createInvoice(Request $request)
    {
        return $this->get('app.invoice')->createInvoice($request);
    }

    /**
     *
     * @ApiDoc(
     *   section = "Invoices",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Update an invoice"
     * )
     *
     * @param Request $request
     * @Method("PUT")
     * @Route("/{id}")
     * @return JsonResponse
     */
    function updateInvoice(Request $request, Invoice $id)
    {
        return $this->get('app.invoice')->updateInvoice($request, $id);
    }

    /**
     * @ApiDoc(
     *   section = "Invoices",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Delete an invoice"
     * )
     *
     * @param Request $request
     * @Method("DELETE")
     * @Route("/{id}")
     * @return JsonResponse
     */
    function deleteInvoice(Request $request, Invoice $id)
    {
        return $this->get('app.invoice')->deleteInvoice($request, $id);
    }

    /**
     * @ApiDoc(
     *   section = "Invoices",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Get all existing invoice rules"
     * )
     *
     * @Route("/invoice-rules")
     * @param Request $request
     * @Method("GET")
     * @return jsonResponse
     */
    public function getInvoiceRules(Request $request)
    {
        return $this->get('app.invoice')->getInvoiceRules($request);
    }

    /**
     * @ApiDoc(
     *   section = "Invoices",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Create an invoice rule for an invoice"
     * )
     *
     * @Route("/{invoice}/invoice-rules")
     * @param Request $request
     * @Method("POST")
     * @return jsonResponse
     */
    public function createInvoiceRuleTemplate(Request $request, Invoice $invoice)
    {
        return $this->get('app.invoice')->createInvoiceRuleTemplate($request, $invoice);
    }

    /**
     * @ApiDoc(
     *   section = "Invoices",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Update an invoice rule"
     * )
     *
     * @Route("/invoice-rules")
     * @param Request $request
     * @Method("PUT")
     * @return jsonResponse
     */
    public function updateInvoiceRuleTemplate(Request $request)
    {
        return $this->get('app.invoice')->updateInvoiceRuleTemplate($request);
    }

    /**
     * @ApiDoc(
     *   section = "Invoices",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Delete an invoice rule belonging to an invoice"
     * )
     *
     * @Route("/{invoice}/invoice-rules/{id}")
     * @param Request $request
     * @Method("DELETE")
     * @return jsonResponse
     */
    public function deleteInvoiceRuleTemplate(Request $request, InvoiceRule $invoiceRuleTemplate, Invoice $invoice)
    {
        return $this->get('app.invoice')->deleteInvoiceRuleTemplate($request, $invoiceRuleTemplate, $invoice);
    }

}