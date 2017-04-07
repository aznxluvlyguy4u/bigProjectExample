<?php
/**
 * Created by IntelliJ IDEA.
 * User: werner
 * Date: 7-4-17
 * Time: 10:24
 */

namespace AppBundle\Controller;


use AppBundle\Entity\InvoiceSenderDetails;
use Symfony\Component\HttpFoundation\Request;

interface InvoiceSenderDetailsAPIControllerInterface
{
    public function getInvoiceSenderDetails();
    public function createInvoiceSenderDetails(Request $request);
    public function updateInvoiceSenderDetails(Request $request);
    public function deleteInvoiceSenderDetails(InvoiceSenderDetails $invoiceSenderDetails);
}