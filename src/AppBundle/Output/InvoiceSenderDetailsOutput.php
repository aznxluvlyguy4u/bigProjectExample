<?php
/**
 * Created by IntelliJ IDEA.
 * User: werner
 * Date: 10-5-17
 * Time: 10:42
 */

namespace AppBundle\Output;


use AppBundle\Entity\InvoiceSenderDetails;

class InvoiceSenderDetailsOutput
{

    public static function createInvoiceSenderDetailsOutput(InvoiceSenderDetails $invoiceSenderDetails){
        return array(
            'name' => $invoiceSenderDetails->getName(),
            'vat_number' => $invoiceSenderDetails->getvatNumber(),
            'chamber_of_commerce_number' => $invoiceSenderDetails->getChamberOfCommerceNumber(),
            'iban' => $invoiceSenderDetails->getIban(),
            'address' => AddressOutput::createAddressOutput($invoiceSenderDetails->getAddress()),
            'payment_deadline_in_days' => $invoiceSenderDetails->getPaymentDeadlineInDays()
        );
    }
}