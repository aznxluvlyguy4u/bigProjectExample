<?php


namespace AppBundle\Service\ExternalProvider;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\Invoice;
use AppBundle\Entity\InvoiceRuleSelection;
use AppBundle\Enumerator\TwinfieldEnums;
use PhpTwinfield\ApiConnectors\InvoiceApiConnector;
use PhpTwinfield\Customer;
use PhpTwinfield\Exception;
use PhpTwinfield\InvoiceLine;
use PhpTwinfield\Office;
use Symfony\Component\HttpFoundation\Response;

class ExternalProviderInvoiceService extends ExternalProviderBase implements ExternalProviderInterface
{
    /**
     * @var InvoiceApiConnector
     */
    private $invoiceConnection;
    /** @var ExternalProviderCustomerService */
    private $twinfieldCustomerService;

    /**
     * @required
     *
     * @param ExternalProviderCustomerService $twinfieldCustomerService
     */
    public function setExternalProviderCustomerService(ExternalProviderCustomerService $twinfieldCustomerService) {
        $this->twinfieldCustomerService = $twinfieldCustomerService;
    }


    /**
     * @required
     */
    public function reAuthenticate() {
        $this->getAuthenticator()->refreshConnection();
        $this->invoiceConnection = new InvoiceApiConnector($this->getAuthenticator()->getConnection());
    }


    /**
     * @param Invoice $invoice
     * @return \Exception|Customer|Exception|\PhpTwinfield\Invoice
     * @throws \Exception
     */
    public function sendInvoiceToTwinfield(Invoice $invoice) {
        /** @var Customer $customer */
        $customer = $this->twinfieldCustomerService
            ->getSingleCustomer(
                $invoice->getCompanyDebtorNumber(),
                $invoice->getCompanyTwinfieldOfficeCode()
            );
        if (is_a($customer, JsonResponse::class)) {
            return $customer;
        }
        if ($customer == null) {
            return "Debtor number not found in twinfield";
        }

        $customer->setCode($invoice->getCompanyDebtorNumber());
        $office = new Office();
        $office->setCode($invoice->getCompanyTwinfieldOfficeCode());
        $twinfieldInvoice = new \PhpTwinfield\Invoice();
        $twinfieldInvoice->setInvoiceDate($invoice->getInvoiceDate());
        $twinfieldInvoice->setCurrency(TwinfieldEnums::CURRENCY_TYPE);
        $twinfieldInvoice->setInvoiceType(TwinfieldEnums::INVOICE_TYPE);
        $twinfieldInvoice->setCustomer($customer);
        $twinfieldInvoice->setOffice($office);
        $twinfieldInvoice->setStatus(TwinfieldEnums::INVOICE_STATUS);
        $twinfieldInvoice->setPaymentMethod(TwinfieldEnums::PAYMENT_METHOD);
        /** @var InvoiceRuleSelection $selection */
        foreach ($invoice->getInvoiceRuleSelections() as $selection) {
            $this->setupInvoiceLine($selection, $twinfieldInvoice);
        }

        $this->resetRetryCount();
        return $this->sendTwinfieldInvoice($twinfieldInvoice);
    }

    /**
     * @param \PhpTwinfield\Invoice $twinfieldInvoice
     * @return \Exception|\PhpTwinfield\Invoice
     * @throws \Exception
     */
    private function sendTwinfieldInvoice(\PhpTwinfield\Invoice $twinfieldInvoice)
    {
        try {
            return $this->invoiceConnection->send($twinfieldInvoice);
        } catch (\Exception $exception) {
            if (!$this->allowRetryTwinfieldApiCall($exception)) {
                $this->resetRetryCount();
                return $exception;
            }

            $this->incrementRetryCount();
            $this->reAuthenticate();
            return $this->sendTwinfieldInvoice($twinfieldInvoice);
        }
    }

    private function setupInvoiceLine(InvoiceRuleSelection $selection, \PhpTwinfield\Invoice $twinfieldInvoice) {
        $line = new InvoiceLine();
        $line->setQuantity($selection->getAmount());
        $line->setArticle($selection->getInvoiceRule()->getArticleCode());
        $this->setVatCode($selection, $line);
        if ($selection->getInvoiceRule()->getSubArticleCode()) {
            $line->setSubArticle($selection->getInvoiceRule()->getSubArticleCode());
        }
        $twinfieldInvoice->addLine($line);
    }

    private function setVatCode(InvoiceRuleSelection $selection, InvoiceLine $line) {
        switch ($selection->getInvoiceRule()->getVatPercentageRate()) {
            case 0: $line->setVatCode(TwinfieldEnums::NO_VAT); break;
            case 6: $line->setVatCode(TwinfieldEnums::LOW_VAT); break;
            case 21: $line->setVatCode(TwinfieldEnums::HIGH_VAT); break;
            default: $line->setVatCode(TwinfieldEnums::NO_VAT); break;
        }
    }
}