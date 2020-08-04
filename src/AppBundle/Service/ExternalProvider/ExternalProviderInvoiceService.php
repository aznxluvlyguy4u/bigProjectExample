<?php


namespace AppBundle\Service\ExternalProvider;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\Invoice;
use AppBundle\Entity\InvoiceRuleSelection;
use AppBundle\Enumerator\TwinfieldEnums;
use AppBundle\Util\NullChecker;
use PhpTwinfield\ApiConnectors\BrowseDataApiConnector;
use PhpTwinfield\ApiConnectors\InvoiceApiConnector;
use PhpTwinfield\BrowseColumn;
use PhpTwinfield\BrowseSortField;
use PhpTwinfield\Customer;
use PhpTwinfield\Enums\BrowseColumnOperator;
use PhpTwinfield\Exception;
use PhpTwinfield\InvoiceLine;
use PhpTwinfield\Office;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ExternalProviderInvoiceService extends ExternalProviderBase implements ExternalProviderInterface
{
    /**
     * @var InvoiceApiConnector
     */
    private $invoiceConnection;
    /**
     * @var BrowseDataApiConnector
     */
    private $browsDataApiConnection;
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
        $this->browsDataApiConnection = new BrowseDataApiConnector($this->getAuthenticator()->getConnection());
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

    /**
     * @param $request
     * @return \Exception|\PhpTwinfield\BrowseData
     * @throws \Exception
     */
    public function getAllInvoicesForCustomer($request) {
        $location = $this->userService->getSelectedLocation($request);
        NullChecker::checkLocation($location);

        $officeCode = $location->getCompany()->getTwinfieldOfficeCode();
        if ($officeCode == null) {
            throw new BadRequestHttpException("Dit UBN heeft geen geregistreerde Twinfield administratie code, neem contact op met NSFO");
        }

        $debtorNumber = $location->getCompany()->getDebtorNumber();
        if ($debtorNumber == null) {
            throw new BadRequestHttpException("Dit UBN heeft geen debiteurnummer, neem contact op met NSFO");
        }

        try {
            // First, create the columns that you want to retrieve (see the browse definition for which columns are available)
            $columns[] = (new BrowseColumn())
                ->setField('fin.trs.head.office')
                ->setLabel('Office')
                ->setVisible(true)
                ->setAsk(true)
                ->setOperator(BrowseColumnOperator::EQUAL())
                ->setFrom($officeCode);

            $columns[] = (new BrowseColumn())
                ->setField('fin.trs.line.dim2')
                ->setLabel('Customer Number')
                ->setVisible(true)
                ->setAsk(true)
                ->setOperator(BrowseColumnOperator::BETWEEN())
                ->setFrom($debtorNumber);

            $columns[] = (new BrowseColumn())
                ->setField('fin.trs.line.openvaluesigned')
                ->setLabel('Outstanding')
                ->setVisible(true)
                ->setOperator(BrowseColumnOperator::NONE());

            $columns[] = (new BrowseColumn())
                ->setField('fin.trs.line.invnumber')
                ->setLabel('Invoice Number')
                ->setVisible(true)
                ->setOperator(BrowseColumnOperator::NONE());

            $columns[] = (new BrowseColumn())
                ->setField('fin.trs.head.date')
                ->setLabel('Invoice Date')
                ->setVisible(true)
                ->setAsk(false)
                ->setOperator(BrowseColumnOperator::NONE());

            $columns[] = (new BrowseColumn())
                ->setField('fin.trs.head.status')
                ->setLabel('Status')
                ->setVisible(true)
                ->setAsk(true)
                ->setOperator(BrowseColumnOperator::NONE());

            $columns[] = (new BrowseColumn())
                ->setField('fin.trs.line.matchstatus')
                ->setLabel('Available')
                ->setVisible(true)
                ->setAsk(true)
                ->setOperator(BrowseColumnOperator::NONE());

            $columns[] = (new BrowseColumn())
                ->setField('fin.trs.head.code')
                ->setLabel('Currency')
                ->setVisible(true)
                ->setOperator(BrowseColumnOperator::EQUAL())
                ->setFrom('VRK');

            $columns[] = (new BrowseColumn())
                ->setField('fin.trs.line.valuesigned')
                ->setLabel('Value')
                ->setVisible(true)
                ->setAsk(true)
                ->setOperator(BrowseColumnOperator::NONE());

            // Second, create sort fields
            $sortFields[] = new BrowseSortField('fin.trs.line.invnumber');

            // Get the browse data
            return $this->browsDataApiConnection->getBrowseData('100', $columns, $sortFields);
        } catch (\Exception $exception) {
            if (!$this->allowRetryTwinfieldApiCall($exception)) {
                $this->resetRetryCount();
                return $exception;
            }

            $this->incrementRetryCount();
            $this->reAuthenticate();
            return $this->getAllInvoicesForCustomer($request);
        }
    }

    /**
     * @param Office $office
     * @return array
     * @throws \Exception
     */
    private function listAllInvoices(Office $office): array
    {
        try {
            return $this->invoiceConnection->listAll($office);
        } catch (\Exception $exception) {
            if (!$this->allowRetryTwinfieldApiCall($exception)) {
                $this->resetRetryCount();
                throw new \Exception($exception->getMessage(),Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $this->incrementRetryCount();
            $this->reAuthenticate();
            return $this->listAllOffices($office);
        }
    }
}
