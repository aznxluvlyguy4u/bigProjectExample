<?php


namespace AppBundle\Service\Invoice;


use AppBundle\Entity\Invoice;
use AppBundle\Service\BaseSerializer;
use AppBundle\Service\CacheService;
use AppBundle\Service\ControllerServiceBase;
use AppBundle\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use PhpTwinfield\ApiConnectors\InvoiceApiConnector;
use PhpTwinfield\Secure\WebservicesAuthentication;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Translation\TranslatorInterface;

class TwinfieldService extends ControllerServiceBase
{
    private $authenticationConnection;

    private $invoiceConnection;

    public function __construct(BaseSerializer $baseSerializer, CacheService $cacheService, EntityManagerInterface $manager, UserService $userService, TranslatorInterface $translator, Logger $logger, $twinfieldUser, $twinfieldPassword, $twinfieldOrganisation)
    {
        parent::__construct($baseSerializer, $cacheService, $manager, $userService, $translator, $logger);
        $this->authenticationConnection = new WebservicesAuthentication($twinfieldUser, $twinfieldPassword, $twinfieldOrganisation);
        $this->invoiceConnection = new InvoiceApiConnector($this->authenticationConnection);
    }

    public function sendInvoiceToTwinfield(Invoice $invoice) {
        $twinfieldInvoice = new \PhpTwinfield\Invoice();
        $twinfieldInvoice->setInvoiceDate($invoice->getInvoiceDate());
        $twinfieldInvoice->setInvoiceNumber($invoice->getInvoiceNumber());
        $twinfieldInvoice->setCurrency("euro");
        $twinfieldInvoice->setInvoiceType("FACTUUR");
    }
}