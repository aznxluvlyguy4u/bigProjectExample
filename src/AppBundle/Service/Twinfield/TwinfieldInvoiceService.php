<?php


namespace AppBundle\Service\Invoice;


use AppBundle\Entity\Invoice;
use AppBundle\Entity\InvoiceRuleSelection;
use AppBundle\Service\BaseSerializer;
use AppBundle\Service\CacheService;
use AppBundle\Service\ControllerServiceBase;
use AppBundle\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use PhpTwinfield\ApiConnectors\CustomerApiConnector;
use PhpTwinfield\ApiConnectors\InvoiceApiConnector;
use PhpTwinfield\InvoiceLine;
use PhpTwinfield\Secure\WebservicesAuthentication;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Translation\TranslatorInterface;

class TwinfieldService extends ControllerServiceBase
{
    private $authenticationConnection;

    private $invoiceConnection;

    private $customerConnection;

    public function __construct(BaseSerializer $baseSerializer, CacheService $cacheService, EntityManagerInterface $manager, UserService $userService, TranslatorInterface $translator, Logger $logger, $twinfieldUser, $twinfieldPassword, $twinfieldOrganisation)
    {
        parent::__construct($baseSerializer, $cacheService, $manager, $userService, $translator, $logger);
        $this->authenticationConnection = new WebservicesAuthentication($twinfieldUser, $twinfieldPassword, $twinfieldOrganisation);
        $this->invoiceConnection = new InvoiceApiConnector($this->authenticationConnection);
        $this->customerConnection = new CustomerApiConnector($this->authenticationConnection);
    }

    public function sendInvoiceToTwinfield(Invoice $invoice) {
        $twinfieldInvoice = new \PhpTwinfield\Invoice();
        $twinfieldInvoice->setInvoiceDate($invoice->getInvoiceDate());
        $twinfieldInvoice->setInvoiceNumber($invoice->getInvoiceNumber());
        $twinfieldInvoice->setCurrency("euro");
        $twinfieldInvoice->setInvoiceType("FACTUUR");

        /** @var InvoiceRuleSelection $selection */
        foreach ($invoice->getInvoiceRuleSelections() as $selection) {
            $line = new InvoiceLine();
            $line->setQuantity($selection->getAmount());
            $line->setDescription($selection->getInvoiceRule()->getDescription());
            $line->setArticle($selection->getInvoiceRule()->getLedgerCategory()->getCode());
            $line->setUnitsPriceExcl($selection->getInvoiceRule()->getPriceExclVat());
            $line->setUnitsPriceInc(
                ($selection->getInvoiceRule()->getPriceExclVat() * $selection->getInvoiceRule()->getVatPercentageRate())
            );
            $twinfieldInvoice->addLine($line);
        }
        $twinfieldInvoice->setDeliverAddressNumber($invoice->getCompanyAddressStreetNumber());
    }


}