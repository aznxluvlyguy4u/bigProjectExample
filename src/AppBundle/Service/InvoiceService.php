<?php

namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Company;
use AppBundle\Entity\Invoice;
use AppBundle\Entity\InvoiceRepository;
use AppBundle\Entity\InvoiceRule;
use AppBundle\Entity\InvoiceSenderDetails;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\InvoiceStatus;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Output\InvoiceOutput;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InvoiceService extends ControllerServiceBase
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    function getInvoices(Request $request)
    {
        if (!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN)) {
            /** @var Location $location */
            $location = $this->getSelectedLocation($request);
            /** @var InvoiceRepository $repo */
            $repo = $this->getManager()->getRepository(Invoice::class);
            $invoices = $repo->findClientAvailableInvoices($location->getUbn());
            $invoices = InvoiceOutput::createInvoiceOutputListNoCompany($invoices);
            return ResultUtil::successResult($invoices);
        }
        $repo = $this->getManager()->getRepository(Invoice::class);
        $status = $request->get('status');
        $invoices = $repo->findBy(array('isDeleted' => false), array('invoiceDate' => 'ASC'));
        $invoices = InvoiceOutput::createInvoiceOutputList($invoices);

        return ResultUtil::successResult($invoices);
    }


    /**
     * @param $id
     * @return JsonResponse
     */
    function getInvoice($id)
    {
        if (!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN)) {
            $invoice = $this->getManager()->getRepository(Invoice::class)->findOneBy(array('id' => $id));
            /** @var Invoice $invoice */
            $invoice = InvoiceOutput::createInvoiceOutputNoCompany($invoice);
            return ResultUtil::successResult($invoice);
        }
        $invoice = $this->getManager()->getRepository(Invoice::class)->findOneBy(array('id' => $id));
        /** @var Invoice $invoice */
        $invoice = InvoiceOutput::createInvoiceOutput($invoice);
        return ResultUtil::successResult($invoice);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    function createInvoice(Request $request)
    {
        if (!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN))
        { return AdminValidator::getStandardErrorResponse(); }

        $invoice = new Invoice();
        $rules = array();
        ($invoice);
        $content = RequestUtil::getContentAsArray($request);
        $contentRules = $content['invoice_rules'];
        $deserializedRules = new ArrayCollection();
        foreach ($contentRules as $contentRule){
            /** @var InvoiceRule $rule */
            $invoiceRule = $this->getManager()->getRepository(InvoiceRule::class)
                ->findOneBy(array('id' => $contentRule['id']));
            $deserializedRules->add($invoiceRule);
        }

        $details = $this->retrieveValidatedSenderDetails($content);
        if ($details instanceof InvoiceSenderDetails) {
            $invoice->setSenderDetails($details);
        }

        $invoice->setInvoiceRules($deserializedRules);
        $invoice->setTotal($content['total']);
        $invoice->setUbn($content["ubn"]);
        $invoice->setCompanyLocalId($content['company_id']);
        $invoice->setCompanyName($content['company_name']);
        $invoice->setCompanyVatNumber($content['company_vat_number']);
        $invoice->setCompanyDebtorNumber($content['company_debtor_number']);
        $invoice->setStatus($content["status"]);
        if ($invoice->getStatus() == InvoiceStatus::UNPAID) {
            $invoice->setInvoiceDate(new \DateTime());
        }
        /** @var Company $company */
        $company = $this->getManager()->getRepository(Company::class)->findOneBy(array('companyId' => $content['company']['company_id']));
        if ($company !== null) {
            $invoice->setCompany($company);
            $company->addInvoice($invoice);
        }
        $year = new \DateTime();
        $year = $year->format('Y');
        $number = $this->getManager()->getRepository(Invoice::class)->getInvoicesOfCurrentYear($year);
        if($number === null || count($number) == 0) {
            $number = (int)$year * 10000;
            $invoice->setInvoiceNumber($number);
        }
        else {
            $number = $number[0]->getInvoiceNumber();
            $number++;
            $invoice->setInvoiceNumber($number);
        }
        $this->persistAndFlush($invoice);
        return ResultUtil::successResult($invoice);
    }


    /**
     * @param ArrayCollection $content
     * @return JsonResponse|InvoiceSenderDetails
     */
    private function retrieveValidatedSenderDetails(ArrayCollection $content)
    {
        $senderDetailsErrorMessage = ResultUtil::errorResult('SENDER DETAILS ARE MISSING', Response::HTTP_PRECONDITION_REQUIRED);
        if (!$content->containsKey('sender_details') || !key_exists('id', $content->get('sender_details'))) {
            return $senderDetailsErrorMessage;
        }

        $details = $this->getManager()->getRepository(InvoiceSenderDetails::class)
            ->findOneBy(array('id' => $content['sender_details']['id']));

        if ($details == null || !$details->containsAllNecessaryData()) {
            return $senderDetailsErrorMessage;
        }

        return $details;
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

        $content = RequestUtil::getContentAsArray($request);
        $temporaryInvoice = new Invoice();
        $contentRules = $content['invoice_rules'];
        $deserializedRules = new ArrayCollection();
        /** @var Company $invoiceCompany */
        $invoiceCompany = $this->getManager()->getRepository(Company::class)->findOneBy(array('companyId' => $content['company']['company_id']));
        $invoice->setInvoiceRules($deserializedRules);
        foreach ($contentRules as $contentRule){
            /** @var InvoiceRule $rule */
            $invoiceRule = $this->getManager()->getRepository(InvoiceRule::class)
                ->findOneBy(array('id' => $contentRule['id']));
            $deserializedRules->add($invoiceRule);
        }
        $invoice->setInvoiceRules($deserializedRules);
        if ($invoice->getCompany() !== null && $invoice->getCompany()->getId() !== $invoiceCompany->getId()){
            /** @var Company $oldCompany */
            $oldCompany = $this->getManager()->getRepository(Company::class)->findOneBy(array('id' => $invoice->getCompany()->getId()));
            $oldCompany->removeInvoice($invoice);
            $invoiceCompany->addInvoice($invoice);
            $this->persistAndFlush($oldCompany);
            $this->persistAndFlush($invoiceCompany);
        }
        $temporaryInvoice->setCompany($invoiceCompany);
        $temporaryInvoice->setInvoiceNumber($content['invoice_number']);
        $temporaryInvoice->setTotal($content['total']);
        $temporaryInvoice->setStatus($content['status']);
        $temporaryInvoice->setUbn($content["ubn"]);
        $temporaryInvoice->setCompanyLocalId($content['company_id']);
        $temporaryInvoice->setCompanyName($content['company_name']);
        $temporaryInvoice->setCompanyVatNumber($content['company_vat_number']);
        $temporaryInvoice->setStatus($content["status"]);
        $invoice->copyValues($temporaryInvoice);
        if ($invoice->getStatus() === "UNPAID") {
            $invoice->setInvoiceDate(new \DateTime());
        }
        else {
            $details = $this->retrieveValidatedSenderDetails($content);
            if ($details instanceof JsonResponse) {
                return $details;
            }

            $invoice->setSenderDetails($details);
        }
        $this->persistAndFlush($invoice);
        return ResultUtil::successResult($invoice);
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


    /**
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getInvoiceRules(Request $request)
    {
        if (!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN))
        { return AdminValidator::getStandardErrorResponse(); }

        $repository = $this->getManager()->getRepository(InvoiceRule::class);
        $ruleTemplates = $repository->findBy(array('isDeleted' => false, 'type' => 'standard'));
        $output = $this->getBaseSerializer()->getDecodedJson($ruleTemplates, JmsGroup::INVOICE_RULE_TEMPLATE);

        return ResultUtil::successResult($output);
    }


    /**
     * @param Request $request
     * @param Invoice $invoice
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createInvoiceRule(Request $request, Invoice $invoice)
    {
        if (!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN))
        { return AdminValidator::getStandardErrorResponse(); }

        /** @var InvoiceRule $ruleTemplate */
        $ruleTemplate = $this->getBaseSerializer()->deserializeToObject($request->getContent(), InvoiceRule::class);

        $errorMessage = '';
        if ($ruleTemplate->getDescription() === '' || $ruleTemplate->getDescription() === null) {
            $errorMessage .= $this->translateUcFirstLower('DESCRIPTION CANNOT BE EMPTY').'. ';
        }
        if ($ruleTemplate->getPriceExclVat() === null) {
            $errorMessage .= $this->translateUcFirstLower('PRICE EXCL VAT CANNOT BE EMPTY, BUT CAN BE ZERO').'. ';
        }
        if ($ruleTemplate->getVatPercentageRate() === null) {
            $errorMessage .= $this->translateUcFirstLower('VAT PERCENTAGE RATE CANNOT BE EMPTY, BUT CAN BE ZERO').'. ';
        }

        if ($errorMessage !== '') {
            return ResultUtil::errorResult($errorMessage,Response::HTTP_PRECONDITION_REQUIRED);
        }

        $ruleTemplate->setInvoice($invoice);
        $this->persistAndFlush($ruleTemplate);

        $invoice->addInvoiceRule($ruleTemplate);
        $this->persistAndFlush($invoice);

        $output = $this->getBaseSerializer()->getDecodedJson($ruleTemplate, JmsGroup::INVOICE_RULE);
        return ResultUtil::successResult($output);
    }


    /**
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateInvoiceRule(Request $request)
    {
        if (!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN))
        { return AdminValidator::getStandardErrorResponse(); }

        $content = RequestUtil::getContentAsArray($request);

        /** @var InvoiceRule $updatedRuleTemplate */
        $updatedRuleTemplate = new InvoiceRule();
        $updatedRuleTemplate->setDescription($content['description']);
        $updatedRuleTemplate->setVatPercentageRate($content['vat_percentage_rate']);
        $updatedRuleTemplate->setPriceExclVat($content['price_excl_vat']);

        $repository = $this->getManager()->getRepository(InvoiceRule::class);
        /** @var InvoiceRule $currentRuleTemplate */
        $currentRuleTemplate = $repository->findOneBy(array('id' => $content['id']));
        if(!$currentRuleTemplate) { return ResultUtil::errorResult('THE INVOICE RULE TEMPLATE IS NOT FOUND.', Response::HTTP_PRECONDITION_REQUIRED); }

        $currentRuleTemplate->copyValues($updatedRuleTemplate);
        $this->persistAndFlush($currentRuleTemplate);

        $output = $this->getBaseSerializer()->getDecodedJson($updatedRuleTemplate, JmsGroup::INVOICE_RULE);
        return ResultUtil::successResult($output);
    }


    /**
     * @param Request $request
     * @param InvoiceRule $invoiceRule
     * @param Invoice $invoice
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteInvoiceRule(Request $request, InvoiceRule $invoiceRule, Invoice $invoice)
    {
        if (!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN))
        { return AdminValidator::getStandardErrorResponse(); }

        $repository = $this->getManager()->getRepository(InvoiceRule::class);
        /** @var InvoiceRule $ruleTemplate */
        $ruleTemplate = $repository->find($invoiceRule);

        if(!$ruleTemplate) { return ResultUtil::errorResult('THE INVOICE RULE TEMPLATE IS NOT FOUND.', Response::HTTP_PRECONDITION_REQUIRED); }
        $invoice->removeInvoiceRule($ruleTemplate);
        $ruleTemplate->setIsDeleted(true);
        $ruleTemplate->setInvoice(null);
        $this->persistAndFlush($invoice);
        $this->persistAndFlush($ruleTemplate);

        $output = $this->getBaseSerializer()->getDecodedJson($ruleTemplate, JmsGroup::INVOICE_RULE);
        return ResultUtil::successResult($output);
    }
}