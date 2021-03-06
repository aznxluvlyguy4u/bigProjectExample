<?php

namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Entity\ActionLog;
use AppBundle\Entity\Company;
use AppBundle\Entity\Invoice;
use AppBundle\Entity\InvoiceRepository;
use AppBundle\Entity\InvoiceRule;
use AppBundle\Entity\InvoiceRuleSelection;
use AppBundle\Entity\InvoiceSenderDetails;
use AppBundle\Entity\LedgerCategory;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationRepository;
use AppBundle\Entity\Message;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\InvoiceAction;
use AppBundle\Enumerator\InvoiceMessages;
use AppBundle\Enumerator\InvoiceRuleType;
use AppBundle\Enumerator\InvoiceStatus;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Enumerator\MessageType;
use AppBundle\Serializer\PreSerializer\InvoicePreSerializer;
use AppBundle\Service\ExternalProvider\ExternalProviderInvoiceService;
use AppBundle\Service\Google\FireBaseService;
use AppBundle\Service\Invoice\InvoicePdfGeneratorService;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\Validator;
use AppBundle\Validation\AdminValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InvoiceService extends ControllerServiceBase
{
    const TWIG_FILE = "Invoice/invoice.html.twig";
    const FOOTER_FILE = "Invoice/_footer.html.twig";

    /** @var array */
    private $ledgerCategoriesById;
    /** @var array */
    private $invalidLedgerCategoryIds;
    /** @var ExternalProviderInvoiceService */
    private $twinfieldInvoiceService;

    /** @var  InvoicePdfGeneratorService */
    private $invoicePdfGeneratorService;

    /** @var FireBaseService */
    private $fireBaseService;



    /**
     * @required
     *
     * @param FireBaseService $fireBaseService
     */
    public function setFireBaseService(FireBaseService $fireBaseService) {
        $this->fireBaseService = $fireBaseService;
    }

    public function instantiateServices(InvoicePdfGeneratorService $invoicePdfGeneratorService, ExternalProviderInvoiceService $twinfieldInvoiceService) {
        $this->invoicePdfGeneratorService = $invoicePdfGeneratorService;
        $this->twinfieldInvoiceService = $twinfieldInvoiceService;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    function getInvoices(Request $request)
    {
        if (!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN)
            || UserService::isGhostLogin($request)) {
            /** @var Location $location */
            $location = $this->getSelectedLocation($request);
            /** @var InvoiceRepository $repo */
            $repo = $this->getManager()->getRepository(Invoice::class);
            $invoices = $repo->findClientAvailableInvoices($location->getUbn());
            return ResultUtil::successResult($this->getBaseSerializer()->getDecodedJson($invoices, [JmsGroup::INVOICE_NO_COMPANY]));
        }
        $repo = $this->getManager()->getRepository(Invoice::class);
        $status = $request->get('status');
        $invoices = $repo->findBy(array('isDeleted' => false), array('invoiceNumber' => 'DESC'));
        return ResultUtil::successResult($this->getBaseSerializer()->getDecodedJson($invoices, [JmsGroup::INVOICE_OVERVIEW]));
    }


    /**
     * @param $id
     * @param Request $request
     * @return JsonResponse
     */
    function getInvoice(Request $request, $id)
    {
        /** @var Invoice $invoice */
        $invoice = $this->getManager()->getRepository(Invoice::class)->find($id);

        $type = JmsGroup::INVOICE;
        if (!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN)
            || UserService::isGhostLogin($request)
        ) {
            $type = JmsGroup::INVOICE_NO_COMPANY;

            if (!$this->isClientAllowedToSeeInvoice($this->getSelectedLocation($request), $invoice)) {
                return ResultUtil::errorResult('THE INVOICE DOES NOT BELONG TO THE LOGGED IN CLIENT', Response::HTTP_PRECONDITION_REQUIRED);
            }
        }

        return ResultUtil::successResult($this->getBaseSerializer()->getDecodedJson($invoice, $type));
    }

    /**
     * @param $id
     * @param Request $request
     * @return JsonResponse
     */
    function getInvoicePdf(Request $request, $id)
    {
        /** @var Invoice $invoice */
        $invoice = $this->getManager()->getRepository(Invoice::class)->find($id);
        if ($invoice->getCompanyAddressState() && $invoice->getCompanyAddressCountry() === "Netherlands") {
            switch ($invoice->getCompanyAddressState()) {
                case "DR":
                    $invoice->setCompanyAddressState("Drenthe");
                    break;
                case "FL":
                    $invoice->setCompanyAddressState("Flevoland");
                    break;
                case "FR":
                    $invoice->setCompanyAddressState("Friesland");
                    break;
                case "GD":
                    $invoice->setCompanyAddressState("Gelderland");
                    break;
                case "GR":
                    $invoice->setCompanyAddressState("Groningen");
                    break;
                case "LB":
                    $invoice->setCompanyAddressState("Limburg");
                    break;
                case "NB":
                    $invoice->setCompanyAddressState("Noord-Brabant");
                    break;
                case "NH":
                    $invoice->setCompanyAddressState("Noord-Holland");
                    break;
                case "OV":
                    $invoice->setCompanyAddressState("Overijssel");
                    break;
                case "UT":
                    $invoice->setCompanyAddressState("Utrecht");
                    break;
                case "ZH":
                    $invoice->setCompanyAddressState("Zuid-Holland");
                    break;
                case "ZL":
                    $invoice->setCompanyAddressState("Zeeland");
                    break;
                default:
                    break;
            }
        }
        return $this->invoicePdfGeneratorService->getInvoicePdfBase(self::TWIG_FILE, self::FOOTER_FILE, $invoice);
    }

    /**
     * @param Location $selectedLocation
     * @param Invoice $invoice
     * @return bool
     */
    private function isClientAllowedToSeeInvoice(Location $selectedLocation, Invoice $invoice)
    {
        return
            $selectedLocation && $selectedLocation->getCompany() && $selectedLocation->getCompany()->getId()
            && $invoice && $invoice->getCompany() && $invoice->getCompany()->getId()
            && $selectedLocation->getCompany()->getId() === $invoice->getCompany()->getId()
        ;
    }


    /**
     * @param Invoice $invoice
     * @return mixed
     */
    private function getInvoiceOutput(Invoice $invoice)
    {
        return $this->getBaseSerializer()->getDecodedJson($invoice, [JmsGroup::INVOICE]);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    function createInvoice(Request $request)
    {
        if (!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN))
        { return AdminValidator::getStandardErrorResponse(); }

        /** @var Invoice $invoice */
        $invoice = $this->getBaseSerializer()->deserializeToObject($request->getContent(), Invoice::class);

        $details = $this->retrieveValidatedSenderDetails($invoice);
        if ($details instanceof JsonResponse) {
            return $details;
        }

        $invoice = $this->roundInvoiceRuleSelectionAmountsInInvoice($invoice);

        $invoice->setSenderDetails($details);
        $log = new ActionLog($this->getUser(), $this->getUser(), InvoiceAction::NEW_INVOICE);
        /**
         * NOTE!
         *
         * Currently invoiceRuleSelections are added in a separate endpoint.
         */

        if ($invoice->getStatus() == InvoiceStatus::UNPAID) {
            $invoice->setInvoiceDate(new \DateTime());
            $repository = $this->getManager()->getRepository(Location::class);
            $location = $repository->findOneByActiveUbn($invoice->getUbn());
            $message = $this->createInvoiceCreatedMessage($request, $invoice);

            $this->fireBaseService->sendNsfoMessageToUser($location->getOwner(), $message);
        }

        /** @var Company $company */
        $company = $invoice->getCompany() && $invoice->getCompany()->getId()
            ? $this->getManager()->getRepository(Company::class)->find($invoice->getCompany()->getId()) : null;
        if ($company !== null) {
            $invoice->setCompany($company);
            $invoice->setCompanyAddressStreetName($company->getBillingAddress()->getStreetName());
            $invoice->setCompanyAddressStreetNumber($company->getBillingAddress()->getAddressNumber());
            $invoice->setCompanyAddressPostalCode($company->getBillingAddress()->getPostalCode());
            $invoice->setCompanyAddressCity($company->getBillingAddress()->getCity());
            $invoice->setCompanyAddressCountry($company->getBillingAddress()->getCountryName());
            if ($company->getBillingAddress()->getAddressNumberSuffix() != null && $company->getBillingAddress()->getAddressNumberSuffix() != "") {
                $invoice->setCompanyAddressStreetNumberSuffix($company->getBillingAddress()->getAddressNumberSuffix());
            }
            if ($company->getBillingAddress()->getState() != null && $company->getBillingAddress()->getState() != "") {
                $invoice->setCompanyAddressState($company->getBillingAddress()->getState());
            }
            $company->addInvoice($invoice);
            $invoice->setCompanyTwinfieldOfficeCode($company->getTwinfieldOfficeCode());
            $invoice->setCompanyTwinfieldCode($company->getDebtorNumber());

            $this->getManager()->persist($company);
        }

        if ($invoice->getStatus() == InvoiceStatus::UNPAID) {
            return $this->validateAndSendToTwinfield($invoice);
        }
        $this->persistAndFlush($invoice);
        $this->persistAndFlush($log);

        return ResultUtil::successResult($this->getInvoiceOutput($invoice));
    }

    /**
     * @param Request $request
     * @param Invoice $invoice
     * @return Message
     */
    private function createInvoiceCreatedMessage(Request $request, Invoice $invoice) {

        $client = $this->getAccountOwner($request);
        $message = new Message();
        $message->setSender($client);
        $message->setType(MessageType::NEW_INVOICE);
        $message->setSubject(InvoiceMessages::NEW_INVOICE_SUBJECT);
        $message->setMessage(InvoiceMessages::NEW_INVOICE_MESSAGE);
        $message->setReceiver($invoice->getCompany()->getOwner());
        /** @var LocationRepository $repository */
        $repository = $this->getManager()->getRepository(Location::class);
        $location = $repository->findOneByActiveUbn($invoice->getUbn());
        $message->setReceiverLocation($location);
        $this->persistAndFlush($message);

        return $message;
    }

    /**
     * @param Invoice $invoice
     * @return JsonResponse|InvoiceSenderDetails
     */
    private function retrieveValidatedSenderDetails(Invoice $invoice)
    {
        if ($invoice->getSenderDetails() === null || $invoice->getSenderDetails()->getId() === null) {
            return $this->getSenderDetailsAreMissingErrorMessage();
        }

        $details = $this->getManager()->getRepository(InvoiceSenderDetails::class)
            ->find($invoice->getSenderDetails()->getId());

        if ($details === null || !$details->containsAllNecessaryData()) {
            return $this->getSenderDetailsAreMissingErrorMessage();
        }

        return $details;
    }

    /**
     * @return JsonResponse
     */
    private function getSenderDetailsAreMissingErrorMessage()
    {
        return ResultUtil::errorResult('SENDER DETAILS ARE MISSING', Response::HTTP_PRECONDITION_REQUIRED);
    }


    /**
     * @param Request $request
     * @param Invoice $invoice
     * @return JsonResponse
     */
    function updateInvoice(Request $request, Invoice $invoice)
    {
        if (!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN))
        { return AdminValidator::getStandardErrorResponse(); }

        /** @var Invoice $temporaryInvoice */
        $temporaryInvoice = $this->getBaseSerializer()->deserializeToObject(
            RequestUtil::revertToJson(
                InvoicePreSerializer::clean($request->getContent())
            ),
            Invoice::class
        );

        $temporaryInvoice = $this->roundInvoiceRuleSelectionAmountsInInvoice($temporaryInvoice);

        $onlySetPaidStatusOnUnpaidInvoice = $temporaryInvoice->getStatus() === InvoiceStatus::PAID && $invoice->getStatus() === InvoiceStatus::UNPAID;

        if ($invoice->getStatus() === InvoiceStatus::CANCELLED
            || $invoice->getStatus() === InvoiceStatus::PAID
            || ($invoice->getStatus() === InvoiceStatus::UNPAID && !$onlySetPaidStatusOnUnpaidInvoice)
        ) {
            return ResultUtil::errorResult($this->translateUcFirstLower('INVOICES THAT ARE ALREADY CANCELLED, SENT BUT UNPAID OR PAID CANNOT BE EDITED ANYMORE'), Response::HTTP_PRECONDITION_REQUIRED);
        }

        /*
         * First check if only the status has to be set to PAID.
         * Do not allow any other simultaneous edits besides that!
         */
        if ($onlySetPaidStatusOnUnpaidInvoice) {
            //$log = new ActionLog($this->getUser(), $this->getUser(), InvoiceAction::INVOICE_PAID_ADMIN);
            $invoice->setStatus(InvoiceStatus::PAID);
            $invoice->setPaidDate(new \DateTime());

            $invoice->updateTotal();
            $invoice->setTotal($invoice->getVatBreakdownRecords()->getTotalInclVat());

            $this->persistAndFlush($invoice);
            return ResultUtil::successResult($this->getInvoiceOutput($invoice));
        }


        if ($temporaryInvoice->getStatus() === InvoiceStatus::UNPAID) {
            $invoice->setInvoiceDate(new \DateTime());
        }

        $details = $this->retrieveValidatedSenderDetails($temporaryInvoice);
        if ($details instanceof JsonResponse) {
            return $details;
        }
        $invoice->setSenderDetails($details);


        /**
         * NOTE!
         *
         * Currently invoiceRuleSelections are added in a separate endpoint.
         */


        /** @var Company $newCompany */
        $newCompany = $temporaryInvoice->getCompany() && $temporaryInvoice->getCompany()->getCompanyId()
            ? $this->getManager()->getRepository(Company::class)->findOneByCompanyId($temporaryInvoice->getCompany()->getCompanyId()) : null;
        $oldCompany = $invoice->getCompany();

        $temporaryInvoice->setCompany($newCompany);
        $temporaryInvoice->setCompanyTwinfieldCode($temporaryInvoice->getCompanyDebtorNumber());
        $invoice->copyValues($temporaryInvoice);



        $removeOldCompany = false;
        $setNewCompany = false;

        if ($newCompany) {

            if ($oldCompany !== null && $oldCompany->getCompanyId() !== $newCompany->getCompanyId()){
                $removeOldCompany = true;
            }
            $setNewCompany = true;

        } elseif ($oldCompany !== null) {
            $removeOldCompany = true;
        }

        if ($removeOldCompany) {
            $invoice->setCompany(null);
            $oldCompany->removeInvoice($invoice);
            $this->getManager()->persist($oldCompany);
        }

        // Note, always first remove old company before setting a new company
        if ($setNewCompany) {
            $invoice->setCompany($newCompany);
            $newCompany->addInvoice($invoice);
            $this->getManager()->persist($newCompany);
        }



        if ($invoice->getStatus() === InvoiceStatus::UNPAID) {
            $log = new ActionLog($this->getUser(), $this->getUser(), InvoiceAction::INVOICE_SEND);
            $invoice->setInvoiceDate(new \DateTime());
            $message = $this->createInvoiceCreatedMessage($request, $invoice);
            /** @var LocationRepository $repository */
            $repository = $this->getManager()->getRepository(Location::class);
            $location = $repository->findOneByActiveUbn($invoice->getUbn());
            $this->persistAndFlush($log);

            if ($location) {
                $this->fireBaseService->sendNsfoMessageToUser($location->getOwner(), $message);
            }

            return $this->validateAndSendToTwinfield($invoice);
        }

        $invoice->updateTotal();
        $invoice->setTotal($invoice->getVatBreakdownRecords()->getTotalInclVat());
        $this->persistAndFlush($invoice);
        return ResultUtil::successResult($this->getInvoiceOutput($invoice));
    }

    /**
     * @param Invoice $invoice
     * @return JsonResponse
     * @throws \Exception
     */
    private function validateAndSendToTwinfield(Invoice $invoice) {
        $message = "Company debtor number and/or twinfield administration code are not filled out";
        $log = new ActionLog($this->getUser(), $this->getUser(), InvoiceAction::TWINFIELD_ERROR, false, $message);
        if ($invoice->getCompanyTwinfieldOfficeCode() && $invoice->getCompanyDebtorNumber()) {
            $result = $this->twinfieldInvoiceService->sendInvoiceToTwinfield($invoice);
            if (is_a($result, \PhpTwinfield\Invoice::class)) {
                $invoice->setInvoiceNumber($result->getInvoiceNumber());
                $invoice->setInvoiceDate(new \DateTime());
                $this->persistAndFlush($invoice);
                return ResultUtil::successResult($invoice);
            }
            $message = $result->getMessage();
            $log = new ActionLog($this->getUser(), $this->getUser(), InvoiceAction::TWINFIELD_ERROR, false, $message);
        }
        $invoice->setStatus(InvoiceStatus::NOT_SEND);
        $invoice->setInvoiceDate(null);
        $this->persistAndFlush($log);
        $this->persistAndFlush($invoice);
        return ResultUtil::errorResult($message, JsonResponse::HTTP_PRECONDITION_REQUIRED);
    }

    /**
     * @param Request $request
     * @param Invoice $id
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    function deleteInvoice(Request $request, Invoice $id)
    {
        if (!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN))
        { return AdminValidator::getStandardErrorResponse(); }

        if ($id->getStatus() === InvoiceStatus::NOT_SEND || $id->getStatus() === InvoiceStatus::INCOMPLETE){
            $id->setIsDeleted(true);
            $this->persistAndFlush($id);
        }
        else {
            return new JsonResponse(array(Constant::ERRORS_NAMESPACE => "Error, you tried to remove an invoice that was already send"), Response::HTTP_OK);
        }
        return ResultUtil::successResult($id);
    }


    private function initializeLedgerCategorySearchArray()
    {
        if ($this->ledgerCategoriesById === null) {
            $this->ledgerCategoriesById = [];
        }
        if ($this->invalidLedgerCategoryIds === null) {
            $this->invalidLedgerCategoryIds = [];
        }
    }


    private function purgeLedgerCategorySearchArrays()
    {
        $this->ledgerCategoriesById = [];
        $this->invalidLedgerCategoryIds = [];
    }


    /**
     * @param int|string $ledgerCategoryId
     * @return LedgerCategory|null
     */
    private function getLedgerCategoryById($ledgerCategoryId)
    {
        $this->initializeLedgerCategorySearchArray();

        $ledgerCategory = ArrayUtil::get($ledgerCategoryId, $this->ledgerCategoriesById, null);

        if ($ledgerCategory) {
            return $ledgerCategory;
        }

        if (key_exists($ledgerCategoryId, $this->invalidLedgerCategoryIds)) {
            return null;
        }

        $ledgerCategory = $this->getManager()->getRepository(LedgerCategory::class)
            ->find($ledgerCategoryId);

        if ($ledgerCategory) {
            $this->ledgerCategoriesById[$ledgerCategoryId] = $ledgerCategory;
        } else {
            $this->invalidLedgerCategoryIds[$ledgerCategoryId] = $ledgerCategoryId;
        }

        return $ledgerCategory;
    }


    /**
     * @param Request $request
     * @param Invoice $invoice
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createInvoiceRuleSelection(Request $request, Invoice $invoice)
    {
        if (!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN))
        { return AdminValidator::getStandardErrorResponse(); }

        /** @var InvoiceRuleSelection $invoiceRuleSelection */
        $invoiceRuleSelection = $this->getBaseSerializer()->deserializeToObject($request->getContent(), InvoiceRuleSelection::class);
        $invoiceRuleSelection = $this->roundInvoiceRuleSelectionAmount($invoiceRuleSelection);

        /** @var InvoiceRule $invoiceRule */
        $invoiceRule = $invoiceRuleSelection->getInvoiceRule();
        $invoiceRule->setDefaultValuesIfEmpty();

        $validationResult = $this->validateRuleTemplate($invoiceRule);
        if ($validationResult instanceof JsonResponse) {
            return $validationResult;
        }

        if ($invoiceRule->getType() === InvoiceRuleType::STANDARD && $invoiceRule->getId()) {
            $invoiceRule = $this->getManager()->getRepository(InvoiceRule::class)
                ->find($invoiceRule->getId());
            if ($invoiceRule->getType() !== InvoiceRuleType::STANDARD) {
                return ResultUtil::errorResult('INVOICE RULE WITH GIVEN ID IS NOT OF TYPE STANDARD', Response::HTTP_BAD_REQUEST);
            }
        }

        $invoiceRule->setLedgerCategory(
            $this->getLedgerCategoryById($invoiceRule->getLedgerCategory()->getId())
        );

        $invoiceRuleSelection->setInvoiceRule($invoiceRule);
        $invoiceRuleSelection->setInvoice($invoice);
        $invoice->addInvoiceRuleSelection($invoiceRuleSelection);
        $invoice->updateTotal();

        if ($invoiceRule->getType() !== InvoiceRuleType::STANDARD) {
            $this->getManager()->persist($invoiceRule);
        }

        $this->getManager()->persist($invoiceRuleSelection);
        $this->getManager()->persist($invoice);
        $this->getManager()->flush();

        $this->purgeLedgerCategorySearchArrays();

        return ResultUtil::successResult($this->getInvoiceOutput($invoice));
    }


    /**
     * @param InvoiceRule $ruleTemplate
     * @return JsonResponse|boolean
     */
    private function validateRuleTemplate(InvoiceRule $ruleTemplate)
    {
        $errorMessage = '';
        $ledgerCategory = null;

        // Null checks

        if ($ruleTemplate->getDescription() === '' || $ruleTemplate->getDescription() === null) {
            $errorMessage .= $this->translateUcFirstLower('DESCRIPTION CANNOT BE EMPTY').'. ';
        }
        if ($ruleTemplate->getPriceExclVat() === null
            || (!is_float($ruleTemplate->getPriceExclVat()) && !is_int($ruleTemplate->getPriceExclVat()))
        ){
            $errorMessage .= $this->translateUcFirstLower('PRICE EXCL VAT CANNOT BE EMPTY, BUT CAN BE ZERO').'. ';
        }
        if ($ruleTemplate->getVatPercentageRate() === null) {
            $errorMessage .= $this->translateUcFirstLower('VAT PERCENTAGE RATE CANNOT BE EMPTY, BUT CAN BE ZERO').'. ';
        }
        if ($ruleTemplate->getLedgerCategory() === null || $ruleTemplate->getLedgerCategory()->getId() === null) {
            $errorMessage .= $this->translateUcFirstLower('LEDGER CATEGORY CANNOT BE EMPTY').'. ';
        } else {
            $ledgerCategory = $this->getLedgerCategoryById($ruleTemplate->getLedgerCategory()->getId());

            if ($ledgerCategory === null) {
                $errorMessage .= $this->translateUcFirstLower('NO LEDGER CATEGORY FOUND FOR GIVEN LEDGER CATEGORY ID').'. ';
            } elseif (!$ledgerCategory->isActive()) {
                $errorMessage .= $this->translateUcFirstLower('LEDGER CATEGORY IS INACTIVE').'. ';
            }
        }

        // Value checks

        if (!Validator::hasValidNumberOfCurrencyDecimals($ruleTemplate->getPriceExclVat())) {
            $errorMessage .= $this->translateUcFirstLower('CURRENCY CANNOT EXCEED '.Validator::MAX_NUMBER_OF_CURRENCY_INPUT_DECIMALS.' DECIMAL SPACES').'. ';
        }

        if ($errorMessage !== '') {
            return ResultUtil::errorResult($errorMessage,Response::HTTP_PRECONDITION_REQUIRED);
        }

        return true;
    }


    /**
     * @param Request $request
     * @param int $invoiceRuleSelectionId
     * @param Invoice $invoice
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteInvoiceRuleSelection(Request $request, Invoice $invoice, $invoiceRuleSelectionId)
    {
        if (!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN))
        { return AdminValidator::getStandardErrorResponse(); }

        $repository = $this->getManager()->getRepository(InvoiceRuleSelection::class);
        /** @var InvoiceRuleSelection $invoiceRuleSelection */
        $invoiceRuleSelection = $repository->find($invoiceRuleSelectionId);

        if(!$invoiceRuleSelection) { return ResultUtil::errorResult('THE INVOICE RULE SELECTION IS NOT FOUND.', Response::HTTP_PRECONDITION_REQUIRED); }
        $invoice->removeInvoiceRuleSelection($invoiceRuleSelection);

        $invoiceRule = $invoiceRuleSelection->getInvoiceRule();
        if ($invoiceRule->getType() === InvoiceRuleType::CUSTOM) {
            $this->getManager()->remove($invoiceRule);
        }

        $invoice->updateTotal();

        $this->getManager()->persist($invoice);
        $this->getManager()->remove($invoiceRuleSelection);
        $this->getManager()->flush();

        return ResultUtil::successResult($this->getInvoiceOutput($invoice));
    }


    /**
     * @param Invoice $invoice
     * @return Invoice
     */
    private function roundInvoiceRuleSelectionAmountsInInvoice(Invoice $invoice)
    {
        if ($invoice) {
            foreach ($invoice->getInvoiceRuleSelections() as $invoiceRuleSelection) {
                $invoiceRuleSelection->setAmount(round($invoiceRuleSelection->getAmount(), InvoiceRuleSelection::AMOUNT_MAX_DECIMALS));
            }
        }

        return $invoice;
    }


    /**
     * @param InvoiceRuleSelection $invoiceRuleSelection
     * @return InvoiceRuleSelection
     */
    private function roundInvoiceRuleSelectionAmount(InvoiceRuleSelection $invoiceRuleSelection)
    {
        $invoiceRuleSelection->setAmount(round($invoiceRuleSelection->getAmount(), InvoiceRuleSelection::AMOUNT_MAX_DECIMALS));
        return $invoiceRuleSelection;
    }
}