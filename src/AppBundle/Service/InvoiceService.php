<?php

namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Company;
use AppBundle\Entity\Invoice;
use AppBundle\Entity\InvoiceRepository;
use AppBundle\Entity\InvoiceRule;
use AppBundle\Entity\InvoiceRuleSelection;
use AppBundle\Entity\InvoiceSenderDetails;
use AppBundle\Entity\LedgerCategory;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\InvoiceRuleType;
use AppBundle\Enumerator\InvoiceStatus;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Serializer\PreSerializer\InvoicePreSerializer;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\Validator;
use AppBundle\Validation\AdminValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InvoiceService extends ControllerServiceBase
{
    /** @var array */
    private $ledgerCategoriesById;
    /** @var array */
    private $invalidLedgerCategoryIds;

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
        $invoices = $repo->findBy(array('isDeleted' => false), array('invoiceDate' => 'ASC'));
        return ResultUtil::successResult($this->getBaseSerializer()->getDecodedJson($invoices, [JmsGroup::INVOICE]));
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

        $invoice->setSenderDetails($details);

        /**
         * NOTE!
         *
         * Currently invoiceRuleSelections are added in a separate endpoint.
         */

        if ($invoice->getStatus() == InvoiceStatus::UNPAID) {
            $invoice->setInvoiceDate(new \DateTime());
        }

        /** @var Company $company */
        $company = $invoice->getCompany() && $invoice->getCompany()->getId()
            ? $this->getManager()->getRepository(Company::class)->find($invoice->getCompany()->getId()) : null;
        if ($company !== null) {
            $invoice->setCompany($company);
            $invoice->setCompanyAddress($company->getAddress());
            $company->addInvoice($invoice);
            $this->getManager()->persist($company);
        }

        $year = new \DateTime();
        $year = $year->format('Y');
        $previousInvoice = $this->getManager()->getRepository(Invoice::class)->getInvoiceOfCurrentYearWithLastInvoiceNumber($year);
        $number = $previousInvoice === null ?
            (int)$year * 10000 :
            $previousInvoice->getInvoiceNumber() + 1
        ;
        $invoice->setInvoiceNumber($number);

        $this->persistAndFlush($invoice);
        return ResultUtil::successResult($this->getInvoiceOutput($invoice));
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

        if ($invoice->getStatus() === InvoiceStatus::UNPAID) {
            $invoice->setInvoiceDate(new \DateTime());
        }
        else {
            $details = $this->retrieveValidatedSenderDetails($temporaryInvoice);
            if ($details instanceof JsonResponse) {
                return $details;
            }

            $invoice->setSenderDetails($details);
        }


        /**
         * NOTE!
         *
         * Currently invoiceRuleSelections are added in a separate endpoint.
         */


        /** @var Company $newCompany */
        $newCompany = $temporaryInvoice->getCompany() && $temporaryInvoice->getCompany()->getCompanyId()
            ? $this->getManager()->getRepository(Company::class)->findOneByCompanyId($temporaryInvoice->getCompany()->getCompanyId()) : null;
        $oldCompany = $invoice->getCompany();

        if ($newCompany) {

            if ($oldCompany !== null){
                if ($oldCompany->getCompanyId() !== $newCompany->getCompanyId()) {
                    $oldCompany->removeInvoice($invoice);
                    $newCompany->addInvoice($invoice);
                    $invoice->setCompany($newCompany);
                    $invoice->setCompanyAddress($newCompany->getAddress());
                    $this->getManager()->persist($oldCompany);
                    $this->getManager()->persist($newCompany);
                }

            } else {
                $invoice->setCompany($newCompany);
                $invoice->setCompanyAddress($newCompany->getAddress());
                $newCompany->addInvoice($invoice);
                $this->getManager()->persist($newCompany);
            }

        } else {
            if ($oldCompany !== null) {
                $invoice->setCompany(null);
                $oldCompany->removeInvoice($invoice);
                $this->getManager()->persist($oldCompany);
            }
        }

        $temporaryInvoice->setCompany($newCompany);
        $invoice->copyValues($temporaryInvoice);
        $invoice->updateTotal();

        $this->persistAndFlush($invoice);
        return ResultUtil::successResult($this->getInvoiceOutput($invoice));
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

        /** @var InvoiceRule $invoiceRule */
        $invoiceRule = $invoiceRuleSelection->getInvoiceRule();

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
}