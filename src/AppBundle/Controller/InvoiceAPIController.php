<?php

namespace AppBundle\Controller;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\InvoiceRepository;
use AppBundle\Entity\InvoiceRule;
use AppBundle\Entity\InvoiceSenderDetails;
use AppBundle\Enumerator\InvoiceStatus;
use AppBundle\Output\InvoiceOutput;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Company;
use AppBundle\Entity\Invoice;
use AppBundle\Entity\Location;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Util\Validator;
use AppBundle\Enumerator\JMSGroups;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Validation\AdminValidator;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class InvoiceAPIController
 * @package AppBundle\Controller
 * @Route("/api/v1/invoices")
 */
class InvoiceAPIController extends APIController implements InvoiceAPIControllerInterface
{
    /**
     *
     * @ApiDoc(
     *   section = "Invoices",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Retrieve all invoices"
     * )
     * @Method("GET")
     * @Route("")
     * @return JsonResponse
     */
    function getInvoices(Request $request)
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) {
            /** @var Location $location */
            $location = $this->getSelectedLocation($request);
            /** @var InvoiceRepository $repo */
            $repo = $this->getManager()->getRepository(Invoice::class);
            $invoices = $repo->findClientAvailableInvoices($location->getUbn());
            $invoices = InvoiceOutput::createInvoiceOutputListNoCompany($invoices);
            return new JsonResponse(array(Constant::RESULT_NAMESPACE => $invoices), Response::HTTP_OK);
        }
        $repo = $this->getManager()->getRepository(Invoice::class);
        $status = $request->get('status');
        $invoices = $repo->findBy(array('isDeleted' => false), array('invoiceDate' => 'ASC'));
        $invoices = InvoiceOutput::createInvoiceOutputList($invoices);

        return new JsonResponse(array(Constant::RESULT_NAMESPACE =>$invoices), Response::HTTP_OK);
    }

    /**
     *
     * @ApiDoc(
     *   section = "Invoices",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Retrieve a specific invoice"
     * )
     *
     * @Method("GET")
     * @Route("/{id}")
     *
     */
    function getInvoice($id) {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) {
            $invoice = $this->getManager()->getRepository(Invoice::class)->findOneBy(array('id' => $id));
            /** @var Invoice $invoice */
            $invoice = InvoiceOutput::createInvoiceOutputNoCompany($invoice);
            return new JsonResponse(array(Constant::RESULT_NAMESPACE => $invoice), Response::HTTP_OK);
        }
        $invoice = $this->getManager()->getRepository(Invoice::class)->findOneBy(array('id' => $id));
        /** @var Invoice $invoice */
        $invoice = InvoiceOutput::createInvoiceOutput($invoice);
        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $invoice), Response::HTTP_OK);
    }

    /**
     * @ApiDoc(
     *   section = "Invoices",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Create an invoice"
     * )
     *
     * @param Request $request
     * @Method("POST")
     * @Route("")
     * @return JsonResponse
     */
    function createInvoice(Request $request)
    {
        // 
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) {return $validationResult->getJsonResponse();}
        $invoice = new Invoice();
        $rules = array();
        ($invoice);
        $content = $this->getContentAsArray($request);
        $contentRules = $content['invoice_rules'];
        $deserializedRules = new ArrayCollection();
        foreach ($contentRules as $contentRule){
            /** @var InvoiceRule $rule */
            $invoiceRule = $this->getManager()->getRepository(InvoiceRule::class)
                ->findOneBy(array('id' => $contentRule['id']));
            $deserializedRules->add($invoiceRule);
        }
        /** @var InvoiceSenderDetails $details */
        $details = $this->getManager()->getRepository(InvoiceSenderDetails::class)
            ->findOneBy(array('id' => $content['sender_details']['id']));
        $invoice->setInvoiceRules($deserializedRules);
        $invoice->setTotal($content['total']);
        $invoice->setUbn($content["ubn"]);
        $invoice->setCompanyLocalId($content['company_id']);
        $invoice->setCompanyName($content['company_name']);
        $invoice->setCompanyVatNumber($content['company_vat_number']);
        $invoice->setCompanyDebtorNumber($content['company_debtor_number']);
        $invoice->setStatus($content["status"]);
        $invoice->setSenderDetails($details);
        if ($invoice->getStatus() == InvoiceStatus::UNPAID) {
            $invoice->setInvoiceDate(new \DateTime());
        }
        /** @var Company $company */
        $company = $this->getManager()->getRepository(Company::class)->findOneBy(array('companyId' => $content['company']['company_id']));
        if ($company != null) {
            $invoice->setCompany($company);
            $company->addInvoice($invoice);
        }
        /** @var InvoiceRepository $repo */
        $repo = $this->getManager()->getRepository(Invoice::class);
        $year = new \DateTime();
        $year = $year->format('Y');
        $number = $repo->getInvoicesOfCurrentYear($year);
        if($number == null) {
            $number = (int)$year * 10000;
            $invoice->setInvoiceNumber($number);
        }
        else {
            $number = $number[0]->getInvoiceNumber();
            $number++;
            $invoice->setInvoiceNumber($number);
        }
        $this->persistAndFlush($invoice);
        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $invoice), Response::HTTP_OK);
    }

    /**
     *
     * @ApiDoc(
     *   section = "Invoices",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Update an invoice"
     * )
     *
     * @param Request $request
     * @Method("PUT")
     * @Route("/{id}")
     * @return JsonResponse
     */
    function updateInvoice(Request $request, Invoice $id)
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) {return $validationResult->getJsonResponse();}
        $content = $this->getContentAsArray($request);
        $temporaryInvoice = new Invoice();
        $contentRules = $content['invoice_rules'];
        $deserializedRules = new ArrayCollection();
        /** @var Company $invoiceCompany */
        $invoiceCompany = $this->getManager()->getRepository(Company::class)->findOneBy(array('companyId' => $content['company']['company_id']));
        $id->setInvoiceRules($deserializedRules);
        foreach ($contentRules as $contentRule){
            /** @var InvoiceRule $rule */
            $invoiceRule = $this->getManager()->getRepository(InvoiceRule::class)
                ->findOneBy(array('id' => $contentRule['id']));
            $deserializedRules->add($invoiceRule);
        }
        $id->setInvoiceRules($deserializedRules);
        if ($id->getCompany() != null && $id->getCompany()->getId() != $invoiceCompany->getId()){
            /** @var Company $oldCompany */
            $oldCompany = $this->getManager()->getRepository(Company::class)->findOneBy(array('id' => $id->getCompany()->getId()));
            $oldCompany->removeInvoice($id);
            $invoiceCompany->addInvoice($id);
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
        $id->copyValues($temporaryInvoice);
        if ($id->getStatus() == "UNPAID") {
            $id->setInvoiceDate(new \DateTime());
        }
        else {
            $details = $this->getManager()->getRepository(InvoiceSenderDetails::class)
                ->findOneBy(array('id' => $content['sender_details']['id']));
            $id->setSenderDetails($details);
        }
        $this->persistAndFlush($id);
        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $id), Response::HTTP_OK);
    }

    /**
     * @ApiDoc(
     *   section = "Invoices",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Delete an invoice"
     * )
     *
     * @param Request $request
     * @Method("DELETE")
     * @Route("/{id}")
     * @return JsonResponse
     */
    function deleteInvoice(Request $request, Invoice $id)
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) {return $validationResult->getJsonResponse();}
        if ($id->getStatus() == InvoiceStatus::NOT_SEND || $id->getStatus() == InvoiceStatus::INCOMPLETE){
        $id->setIsDeleted(true);
        $this->persistAndFlush($id);
        }
        else {
            return new JsonResponse(array(Constant::ERRORS_NAMESPACE => "Error, you tried to remove an invoice that was already send"), Response::HTTP_OK);
        }
        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $id), Response::HTTP_OK);
    }

    /**
     * @ApiDoc(
     *   section = "Invoices",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Get all existing invoice rules"
     * )
     *
     * @Route("/invoice-rules")
     * @param Request $request
     * @Method("GET")
     * @return jsonResponse
     */
    public function getInvoiceRules(Request $request)
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) { return $validationResult->getJsonResponse(); }

        $repository = $this->getDoctrine()->getRepository(InvoiceRule::class);
        $ruleTemplates = $repository->findBy(array('isDeleted' => false, 'type' => 'standard'));
        $output = $this->getDecodedJson($ruleTemplates, JMSGroups::INVOICE_RULE_TEMPLATE);

        return new JsonResponse([Constant::RESULT_NAMESPACE => $output], Response::HTTP_OK);
    }

    /**
     * @ApiDoc(
     *   section = "Invoices",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Create an invoice rule for an invoice"
     * )
     *
     * @Route("/{invoice}/invoice-rules")
     * @param Request $request
     * @Method("POST")
     * @return jsonResponse
     */
    public function createInvoiceRuleTemplate(Request $request, Invoice $invoice)
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) { return $validationResult->getJsonResponse(); }

        $content = $this->getContentAsArray($request);
        /** @var InvoiceRule $ruleTemplate */
        $ruleTemplate = $this->getObjectFromContent($content, InvoiceRule::class);

        $ruleTemplate->setInvoice($invoice);
        $this->persistAndFlush($ruleTemplate);

        $invoice->addInvoiceRule($ruleTemplate);
        $this->persistAndFlush($invoice);

        $output = $this->getDecodedJson($ruleTemplate, JMSGroups::INVOICE_RULE_TEMPLATE);
        return new JsonResponse([Constant::RESULT_NAMESPACE => $output], Response::HTTP_OK);
    }

    /**
     * @ApiDoc(
     *   section = "Invoices",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Update an invoice rule"
     * )
     *
     * @Route("/invoice-rules")
     * @param Request $request
     * @Method("PUT")
     * @return jsonResponse
     */
    public function updateInvoiceRuleTemplate(Request $request)
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) { return $validationResult->getJsonResponse(); }

        $content = $this->getContentAsArray($request);

        /** @var InvoiceRule $updatedRuleTemplate */
        $updatedRuleTemplate = new InvoiceRule();
        $updatedRuleTemplate->setDescription($content['description']);
        $updatedRuleTemplate->setVatPercentageRate($content['vat_percentage_rate']);
        $updatedRuleTemplate->setPriceExclVat($content['price_excl_vat']);

        $repository = $this->getDoctrine()->getRepository(InvoiceRule::class);
        /** @var InvoiceRule $currentRuleTemplate */
        $currentRuleTemplate = $repository->findOneBy(array('id' => $content['id']));
        if(!$currentRuleTemplate) { return Validator::createJsonResponse('THE INVOICE RULE TEMPLATE IS NOT FOUND.', Response::HTTP_PRECONDITION_REQUIRED); }

        $currentRuleTemplate->copyValues($updatedRuleTemplate);
        $this->persistAndFlush($currentRuleTemplate);

        $output = $this->getDecodedJson($updatedRuleTemplate, JMSGroups::INVOICE_RULE_TEMPLATE);
        return new JsonResponse([Constant::RESULT_NAMESPACE => $output], Response::HTTP_OK);
    }

    /**
     * @ApiDoc(
     *   section = "Invoices",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Delete an invoice rule belonging to an invoice"
     * )
     *
     * @Route("/{invoice}/invoice-rules/{id}")
     * @param Request $request
     * @Method("DELETE")
     * @return jsonResponse
     */
    public function deleteInvoiceRuleTemplate(Request $request, InvoiceRule $invoiceRuleTemplate, Invoice $invoice)
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) { return $validationResult->getJsonResponse(); }

        $repository = $this->getDoctrine()->getRepository(InvoiceRule::class);
        /** @var InvoiceRule $ruleTemplate */
        $ruleTemplate = $repository->find($invoiceRuleTemplate);

        if(!$ruleTemplate) { return Validator::createJsonResponse('THE INVOICE RULE TEMPLATE IS NOT FOUND.', Response::HTTP_PRECONDITION_REQUIRED); }
        $invoice->removeInvoiceRule($ruleTemplate);
        $ruleTemplate->setIsDeleted(true);
        $ruleTemplate->setInvoice(null);
        $this->persistAndFlush($invoice);
        $this->persistAndFlush($ruleTemplate);

        $output = $this->getDecodedJson($ruleTemplate, JMSGroups::INVOICE_RULE_TEMPLATE);
        return new JsonResponse([Constant::RESULT_NAMESPACE => $output], Response::HTTP_OK);
    }

}