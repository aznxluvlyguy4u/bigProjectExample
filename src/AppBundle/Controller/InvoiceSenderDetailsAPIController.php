<?php

namespace AppBundle\Controller;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Entity\InvoiceSenderDetails;
use AppBundle\Entity\BillingAddress;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Validation\AdminValidator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class InvoiceSenderDetailsAPIController
 * @package AppBundle\Controller
 * @Route("/api/v1/invoice-sender-details")
 */
class InvoiceSenderDetailsAPIController extends APIController implements InvoiceSenderDetailsAPIControllerInterface
{
    /**
     * @ApiDoc(
     *   section = "Invoice sender details",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Retrieve invoice sender details"
     * )
     *
     * @return JsonResponse
     * @Route("")
     * @Method("GET")
     */
    public function getInvoiceSenderDetails()
    {
        return $this->get('app.invoice.sender.details')->getInvoiceSenderDetails();
    }

    /**
     * @ApiDoc(
     *   section = "Invoice sender details",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Create new invoice sender details"
     * )
     *
     * @param Request $request
     * @return mixed
     * @Method("POST")
     * @Route("")
     */
    public function createInvoiceSenderDetails(Request $request)
    {
        return $this->get('app.invoice.sender.details')->createInvoiceSenderDetails($request);
    }

    /**
     * @ApiDoc(
     *   section = "Invoice sender details",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Update existing invoice sender details"
     * )
     *
     * @param Request $request, InvoiceSenderDetails $invoiceSenderDetails
     * @return mixed
     * @Method("PUT")
     * @Route("/{invoiceSenderDetails}")
     */
    public function updateInvoiceSenderDetails(Request $request, InvoiceSenderDetails $invoiceSenderDetails)
    {
        return $this->get('app.invoice.sender.details')->updateInvoiceSenderDetails($request, $invoiceSenderDetails);
    }

    /**
     * @ApiDoc(
     *   section = "Invoice sender details",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Delete invoice sender details"
     * )
     *
     * @param InvoiceSenderDetails $invoiceSenderDetails
     * @return mixed
     * @Method("DELETE")
     * @Route("{invoiceSenderDetails}")
     */
    public function deleteInvoiceSenderDetails(InvoiceSenderDetails $invoiceSenderDetails)
    {
        return $this->get('app.invoice.sender.details')->deleteInvoiceSenderDetails($invoiceSenderDetails);
    }

}