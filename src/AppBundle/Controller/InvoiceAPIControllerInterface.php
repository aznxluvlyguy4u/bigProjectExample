<?php
/**
 * Created by IntelliJ IDEA.
 * User: werner
 * Date: 5-4-17
 * Time: 13:45
 */

namespace AppBundle\Controller;


use AppBundle\Entity\Invoice;
use Symfony\Component\HttpFoundation\Request;

interface InvoiceAPIControllerInterface
{
    function getInvoices(Request $request);
    function createInvoice(Request $request);
    function updateInvoice(Request $request, Invoice $id);
    function deleteInvoice(Request $request, Invoice $id);
}