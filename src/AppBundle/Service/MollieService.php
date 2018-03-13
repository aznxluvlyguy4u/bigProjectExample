<?php
/**
 * Created by IntelliJ IDEA.
 * User: werner
 * Date: 15-5-17
 * Time: 16:42
 */

namespace AppBundle\Service;

use AppBundle\Constant\Constant;
use AppBundle\Constant\Endpoint;
use AppBundle\Entity\Invoice;
use Mollie_API_Client;

class MollieService
{
    /** @var Mollie_API_Client $client */
    private $client;

    /** @var string  */
    private $apiHostAddress;

    /** @var string  */
    private $webHostAddress;

    public function __construct($credentials = array(), $apiUrls = array(), $webUrls = array(), $currentEnvironment = null)
    {
        $this->client = new Mollie_API_Client();
        switch ($currentEnvironment) {
            case "dev":
                $this->client->setApiKey($credentials[0]);
                $this->webHostAddress = $webUrls[0];
                $this->apiHostAddress = $apiUrls[0];
                break;
            case "stage":
                $this->client->setApiKey($credentials[0]);
                $this->apiHostAddress = $apiUrls[1];
                $this->webHostAddress = $webUrls[1];
                break;
            case "prod":
                $this->client->setApiKey($credentials[1]);
                $this->apiHostAddress = $apiUrls[2];
                $this->webHostAddress = $webUrls[2];
                break;
            case "local":
                $this->client->setApiKey($credentials[0]);
                $this->webHostAddress = $webUrls[0];
                $this->apiHostAddress = $apiUrls[0];
                break;
        }
    }

    public function createPayment(Invoice $invoice){
        $payment = $this->client->payments->create(
                    array(
                        'description' => 'NSFO factuur',
                        'amount' => $invoice->getTotal(),
                        'redirectUrl' => $this->webHostAddress.Endpoint::FRONTEND_INVOICE_DETAILS_ENDPOINT.'/'.$invoice->getId(),
                        'webhookUrl' => $this->apiHostAddress.Endpoint::MOLLIE_ENDPOINT.'/update/'.$invoice->getId(),
                        'method' => 'ideal',
                        'metadata' => array(
                            'order_id' => $invoice->getInvoiceNumber()
                        )
                        )
                    );

        return $payment;
    }

    public function getPayment(Invoice $invoice){
        $payment = $this->client->payments->get($invoice->getMollieId());
        return $payment;
    }
}