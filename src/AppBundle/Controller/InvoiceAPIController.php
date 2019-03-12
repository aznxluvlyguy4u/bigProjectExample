<?php

namespace AppBundle\Controller;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\Invoice;
use AppBundle\Service\FeedbackQueueInvoiceMessageService;
use AppBundle\Service\Invoice\BatchInvoiceService;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;


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
     * @param Request $request
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
     * @param Invoice $invoice
     * @param Request $request
     * @Method("GET")
     * @Route("/{invoice}")
     *
     */
    function getInvoice(Request $request, Invoice $invoice)
    {
        return $this->get('app.invoice')->getInvoice($request, $invoice);
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
     *   description = "Create a batch of invoices automatically"
     * )
     *
     * @param Request $request
     * @Method("POST")
     * @Route("/batch")
     * @return JsonResponse
     */
    function creatInvoiceBatch(Request $request) {
        return $this->get(FeedbackQueueInvoiceMessageService::class)->createBatchInvoiceMessage($request);
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
     * @param Invoice $id
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
     * @param Invoice $id
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
     * @Route("/{invoice}/invoice-rule-selection")
     * @param Request $request
     * @param Invoice $invoice
     * @Method("POST")
     * @return jsonResponse
     */
    public function createInvoiceRuleSelection(Request $request, Invoice $invoice)
    {
        return $this->get('app.invoice')->createInvoiceRuleSelection($request, $invoice);
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
    public function updateInvoiceRule(Request $request)
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
     * @Route("/{invoice}/invoice-rule-selection/{invoiceRuleSelectionId}")
     * @param Request $request
     * @param int $invoiceRuleSelectionId
     * @param Invoice $invoice
     * @Method("DELETE")
     * @return jsonResponse
     */
    public function deleteInvoiceRule(Request $request, Invoice $invoice, $invoiceRuleSelectionId)
    {
        return $this->get('app.invoice')->deleteInvoiceRuleSelection($request, $invoice, $invoiceRuleSelectionId);
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
     * @Route("/{invoice}/pdf")
     * @param Request $request
     * @param Invoice $invoice
     */
    public function getInvoicePdf(Request $request, Invoice $invoice) {
        return $this->get('app.invoice')->getInvoicePdf($request, $invoice);
    }

}