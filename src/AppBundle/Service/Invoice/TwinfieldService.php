<?php


namespace AppBundle\Service\Invoice;


use PhpTwinfield\ApiConnectors\ArticleApiConnector;
use PhpTwinfield\Secure\WebservicesAuthentication;

class TwinfieldService
{
    private $authenticationConnection;

    private $invoiceConnector;

    public function __construct()
    {
    }
}