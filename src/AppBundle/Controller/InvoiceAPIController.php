<?php
/**
 * Created by IntelliJ IDEA.
 * User: werner
 * Date: 5-4-17
 * Time: 13:45
 */

namespace AppBundle\Controller;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class InvoiceAPIController
 * @package AppBundle\Controller
 * @Route("/invoices")
 */
class InvoiceAPIController extends APIController implements InvoiceAPIControllerInterface
{
    /**
     * @Method("GET")
     * @Route("")
     *
     */
    function getInvoices()
    {
        // TODO: Implement getInvoices() method.
    }

    /**
     * @param Request $request
     * @Method("GET")
     * @Route("/by")
     */
    function getInvoicesBy(Request $request)
    {
        // TODO: Implement getInvoicesBy() method.
    }

    /**
     * @param Request $request
     * @Method("POST")
     * @Route("")
     */
    function createInvoice(Request $request)
    {
        // TODO: Implement createInvoice() method.
    }

    /**
     * @param Request $request
     * @Method("PUT")
     * @Route("/{id}")
     */
    function updateInvoice(Request $request, $id)
    {
        // TODO: Implement updateInvoice() method.
    }

    /**
     * @param Request $request
     * @Method("DELETE")
     * @Route("/{id}")
     */
    function deleteInvoice(Request $request, $id)
    {
        // TODO: Implement deleteInvoice() method.
    }

}