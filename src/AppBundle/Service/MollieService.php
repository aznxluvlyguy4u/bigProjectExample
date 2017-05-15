<?php
/**
 * Created by IntelliJ IDEA.
 * User: werner
 * Date: 15-5-17
 * Time: 16:42
 */

namespace AppBundle\Service;

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

    public function __construct($credentials = array(), $currentEnvironment = null)
    {
        $this->client = new Mollie_API_Client();
        switch ($currentEnvironment) {
            case "dev":
                $this->client->setApiKey($credentials[0]);
                $this->webHostAddress = "http://localhost:8080";
                $this->apiHostAddress = "http://6e853932.ngrok.io";
                break;
            case "stage":
                $this->client->setApiKey($credentials[0]);
                $this->apiHostAddress = "http://nsfo-dev-api.jongensvantechniek.nl";
                $this->webHostAddress = 'http://nsfo-dev.jongensvantechniek.nl';
                break;
            case "prod":
                $this->client->setApiKey($credentials[1]);
                $this->apiHostAddress = "nsfo-api.jongensvantechniek.nl";
                $this->webHostAddress = 'http://online.nsfo.nl';
                break;
            case "local":
                $this->client->setApiKey($credentials[0]);
                $this->webHostAddress = "localhost:8080";
                $this->apiHostAddress = "http://xxxxxxx.ngrok.io";
                break;
        }
    }

    public function createPayment(Invoice $invoice){
        $payment = $this->client->payments->create(
                    array(
                        'description' => 'NSFO factuur',
                        'amount' => $invoice->getTotal(),
                        'redirectUrl' => $this->webHostAddress.'/main/invoices/details/'.$invoice->getId(),
                        'webhookUrl' => $this->apiHostAddress.'/api/v1/mollie/update/'.$invoice->getId(),
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