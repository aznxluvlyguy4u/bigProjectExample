<?php


namespace AppBundle\Service\Twinfield;


use AppBundle\Entity\Invoice;
use AppBundle\Entity\InvoiceRuleSelection;
use AppBundle\Service\BaseSerializer;
use AppBundle\Service\CacheService;
use AppBundle\Service\ControllerServiceBase;
use AppBundle\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use PhpTwinfield\ApiConnectors\CustomerApiConnector;
use PhpTwinfield\ApiConnectors\InvoiceApiConnector;
use PhpTwinfield\Article;
use PhpTwinfield\Customer;
use PhpTwinfield\Exception;
use PhpTwinfield\InvoiceLine;
use PhpTwinfield\Office;
use PhpTwinfield\Request\Read\Transaction;
use PhpTwinfield\Secure\WebservicesAuthentication;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Translation\TranslatorInterface;

class TwinfieldInvoiceService extends ControllerServiceBase
{
    /** @var WebservicesAuthentication */
    private $authenticationConnection;

    private $user;

    private $password;

    private $organisation;
    /**
     * @var InvoiceApiConnector
     */
    private $invoiceConnection;
    /** @var TwinfieldCustomerService */
    private $twinfieldCustomerService;

    public function instantiateServices( $twinfieldUser, $twinfieldPassword, $twinfieldOrganisation, TwinfieldCustomerService $twinfieldCustomerService) {
        $this->authenticationConnection = new WebservicesAuthentication($twinfieldUser, $twinfieldPassword, $twinfieldOrganisation);
        $this->invoiceConnection = new InvoiceApiConnector($this->authenticationConnection);
        $this->twinfieldCustomerService = $twinfieldCustomerService;
        $this->user = $twinfieldUser;
        $this->password = $twinfieldPassword;
        $this->organisation = $twinfieldOrganisation;
    }

    public function sendInvoiceToTwinfield(Invoice $invoice) {
        /** @var Customer $customer */
        $customer = $this->twinfieldCustomerService->getSingleCustomer($invoice->getCompanyDebtorNumber(), $invoice->getCompanyTwinfieldAdministrationCode());
        $customer->setCode($invoice->getCompanyDebtorNumber());
        $office = new Office();
        $office->setCode($invoice->getCompanyTwinfieldAdministrationCode());
        $twinfieldInvoice = new \PhpTwinfield\Invoice();
        $twinfieldInvoice->setInvoiceDate($invoice->getInvoiceDate());
        $twinfieldInvoice->setCurrency("EUR");
        $twinfieldInvoice->setInvoiceType("FACTUUR");
        $twinfieldInvoice->setCustomer($customer);
        $twinfieldInvoice->setOffice($office);
        $twinfieldInvoice->setStatus("concept");
        $twinfieldInvoice->setPaymentMethod("cash");
        /** @var InvoiceRuleSelection $selection */
        foreach ($invoice->getInvoiceRuleSelections() as $selection) {
            $line = new InvoiceLine();
            $line->setQuantity($selection->getAmount());
            $line->setArticle($selection->getInvoiceRule()->getArticleCode());
            switch ($selection->getInvoiceRule()->getVatPercentageRate()) {
                case 0:
                    $line->setVatCode("VN");
                    break;

                case 6:
                    $line->setVatCode("VL");
                    break;

                case 21:
                    $line->setVatCode("VH");
                    break;
            }
            if ($selection->getInvoiceRule()->getSubArticleCode()) {
                $line->setSubArticle($selection->getInvoiceRule()->getSubArticleCode());
            }
            $twinfieldInvoice->addLine($line);
        }

        try {
            return $this->invoiceConnection->send($twinfieldInvoice);
        } catch (Exception $e) {
            return $e;
        }
    }

    public function reAuthenticate() {
        $this->authenticationConnection = new WebservicesAuthentication($this->user, $this->password, $this->organisation);
        $this->invoiceConnection = new InvoiceApiConnector($this->authenticationConnection);
    }

    public function sendAllInvoicesToTwinfield($invoices) {
        $resultSet = [];

        /** @var Invoice $invoice */
        foreach ($invoices as $invoice) {
            /** @var Customer $customer */
            $customer = $this->twinfieldCustomerService->getSingleCustomer($invoice->getCompanyDebtorNumber(), $invoice->getCompanyTwinfieldAdministrationCode());
            $customer->setCode($invoice->getCompanyDebtorNumber());
            $office = new Office();
            $office->setCode($invoice->getCompanyTwinfieldAdministrationCode());
            $twinfieldInvoice = new \PhpTwinfield\Invoice();
            $twinfieldInvoice->setInvoiceDate($invoice->getInvoiceDate());
            $twinfieldInvoice->setCurrency("EUR");
            $twinfieldInvoice->setInvoiceType("FACTUUR");
            $twinfieldInvoice->setCustomer($customer);
            $twinfieldInvoice->setOffice($office);
            $twinfieldInvoice->setStatus("concept");
            $twinfieldInvoice->setPaymentMethod("cash");
            /** @var InvoiceRuleSelection $selection */
            foreach ($invoice->getInvoiceRuleSelections() as $selection) {
                $line = new InvoiceLine();
                $line->setQuantity($selection->getAmount());
                $line->setArticle($selection->getInvoiceRule()->getArticleCode());
                switch ($selection->getInvoiceRule()->getVatPercentageRate()) {
                    case 0:
                        $line->setVatCode("VN");
                        break;

                    case 6:
                        $line->setVatCode("VL");
                        break;

                    case 21:
                        $line->setVatCode("VH");
                        break;
                }
                if ($selection->getInvoiceRule()->getSubArticleCode()) {
                    $line->setSubArticle($selection->getInvoiceRule()->getSubArticleCode());
                }
                $twinfieldInvoice->addLine($line);
            }
            $resultSet[] = $twinfieldInvoice;
        }
        try {
            return $this->invoiceConnection->sendAll($resultSet);
        } catch (Exception $e) {
            return $e;
        }
    }

}