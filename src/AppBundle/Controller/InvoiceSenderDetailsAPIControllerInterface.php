<?php

namespace AppBundle\Controller;


use AppBundle\Entity\InvoiceSenderDetails;
use Symfony\Component\HttpFoundation\Request;

interface InvoiceSenderDetailsAPIControllerInterface
{
    public function getInvoiceSenderDetails();
    public function createInvoiceSenderDetails(Request $request);
    public function updateInvoiceSenderDetails(Request $request, InvoiceSenderDetails $invoiceSenderDetails);
    public function deleteInvoiceSenderDetails(InvoiceSenderDetails $invoiceSenderDetails);
}