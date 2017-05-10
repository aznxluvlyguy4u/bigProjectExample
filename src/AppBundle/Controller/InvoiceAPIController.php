<?php
/**
 * Created by IntelliJ IDEA.
 * User: werner
 * Date: 5-4-17
 * Time: 13:45
 */

namespace AppBundle\Controller;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\InvoiceRuleLocked;
use AppBundle\Entity\InvoiceRuleTemplate;
use AppBundle\Entity\InvoiceSenderDetails;
use AppBundle\Output\InvoiceOutput;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Company;
use AppBundle\Entity\Invoice;
use AppBundle\Entity\InvoiceRule;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use AppBundle\Util\Validator;
use AppBundle\Enumerator\JMSGroups;
use Doctrine\ORM\QueryBuilder;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Validation\AdminValidator;

/**
 * Class InvoiceAPIController
 * @package AppBundle\Controller
 * @Route("/api/v1/invoices")
 */
class InvoiceAPIController extends APIController implements InvoiceAPIControllerInterface
{
    /**
     * @Method("GET")
     * @Route("")
     * @return JsonResponse
     */
    function getInvoices()
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) { return $validationResult->getJsonResponse(); }
        $repo = $this->getManager()->getRepository(Invoice::class);
        $invoices = $repo->findBy(array('isDeleted' => false), array('invoiceDate' => 'ASC'));
        $invoices = InvoiceOutput::createInvoiceOutputList($invoices);
        return new JsonResponse(array(Constant::RESULT_NAMESPACE =>$invoices), 200);
    }

    /**
     * @Method("GET")
     * @Route("/incomplete")
     * @return JsonResponse
     */
    function getIncompleteInvoices()
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) { return $validationResult->getJsonResponse(); }
        $repo = $this->getManager()->getRepository(Invoice::class);
        $invoices = $repo->findBy(array('isDeleted' => false, 'status' => 'UNPAID'), array('invoiceDate' => 'ASC'));
        $invoices = InvoiceOutput::createInvoiceOutputList($invoices);
        return new JsonResponse(array(Constant::RESULT_NAMESPACE =>$invoices), 200);
    }

    /**
     * @Method("GET")
     * @Route("/{id}")
     *
     */
    function getInvoice($id) {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) { return $validationResult->getJsonResponse(); }
        $invoice = $this->getManager()->getRepository(Invoice::class)->findOneBy(array('id' => $id));
        /** @var Invoice $invoice */
        $invoice = InvoiceOutput::createInvoiceOutput($invoice);
        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $invoice), 200);
    }

    /**
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
        self::setInvoiceNumber($invoice);
        $content = $this->getContentAsArray($request);
        $contentRules = $content['invoice_rules'];
        $deserializedRules = new ArrayCollection();
        $lockedRules = new ArrayCollection();
        foreach ($contentRules as $contentRule){
            /** @var InvoiceRuleTemplate $rule */
            $invoiceRule = $this->getManager()->getRepository(InvoiceRuleTemplate::class)
                ->findOneBy(array('id' => $contentRule['id']));
            $deserializedRules->add($invoiceRule);
            $lockedRule = $this->getManager()->getRepository(InvoiceRuleLocked::class)->findOneBy(array('id' => $invoiceRule->getLockedVersion()->getId()));
            $lockedRules->add($lockedRule);
        }
        /** @var InvoiceSenderDetails $details */
        $details = $this->getManager()->getRepository(InvoiceSenderDetails::class)
            ->findOneBy(array('id' => $content['sender_details']['id']));
        $invoice->setInvoiceRules($deserializedRules);
        $invoice->setLockedInvoiceRules($lockedRules);
        $invoice->setUbn($content["ubn"]);
        $invoice->setCompanyName($content['company_name']);
        $invoice->setCompanyVatNumber($content['company_vat_number']);
        $invoice->setCompanyDebtorNumber($content['company_debtor_number']);
        $invoice->setStatus($content["status"]);
        $invoice->setSenderDetails($details);
        if ($invoice->getStatus() == "UNPAID") {
            $invoice->setInvoiceDate(new \DateTime());
        }
        /** @var Company $company */
        $company = $this->getManager()->getRepository(Company::class)->findOneBy(array('companyId' => $content['company']['company_id']));
        if ($company != null) {
            $invoice->setCompany($company);
            $company->addInvoice($invoice);
        }
        $this->persistAndFlush($invoice);
        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $invoice), 200);
    }

    /**
     * Function to properly set the InvoiceNumber
     * InvoiceNumber format is "CurrentYear" + a number that is 5 long (00001, 03010, etc)
     * @param Invoice $invoice
     */
    private function setInvoiceNumber(Invoice $invoice) {
        $year = new \DateTime();
        $year = $year->format('Y');
        $year = $year."%";
        $year = (string)$year;
        // This query should get The invoice with the highest invoiceNumber, by ordering the invoices based on
        // InvoiceNumber DESC and limiting results to 1
        $qb = $this->getManager()->getRepository(Invoice::class)->createQueryBuilder('qb')
            ->where('qb.invoiceNumber LIKE :year')
            ->orderBy('qb.invoiceNumber', 'DESC')
            ->setMaxResults(1)
            ->setParameter('year', $year)
            ->getQuery();
        /** @var Invoice $invoices */
        $invoices = $qb->getResult();

        if ($invoices == null){
            $number = $year * 100000;
            $invoice->setInvoiceNumber($number);
        }
        else {
            $number = $invoices[0]->getInvoiceNumber();
            $number = (int)$number;
            $number++;
            $number = (string)$number;
            $invoice->setInvoiceNumber($number);
        }
    }

    /**
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
        $lockedRules = new ArrayCollection();
        /** @var Company $invoiceCompany */
        $invoiceCompany = $this->getManager()->getRepository(Company::class)->findOneBy(array('companyId' => $content['company']['company_id']));
        $id->setInvoiceRules($deserializedRules);
        $id->setLockedInvoiceRules($lockedRules);
        foreach ($contentRules as $contentRule){
            /** @var InvoiceRuleTemplate $rule */
            $invoiceRule = $this->getManager()->getRepository(InvoiceRuleTemplate::class)
                ->findOneBy(array('id' => $contentRule['id']));
            $deserializedRules->add($invoiceRule);
            $lockedRule = $this->getManager()->getRepository(InvoiceRuleLocked::class)->findOneBy(array('id' => $invoiceRule->getLockedVersion()->getId()));
            $lockedRules->add($lockedRule);
        }
        $id->setInvoiceRules($deserializedRules);
        $id->setLockedInvoiceRules($lockedRules);
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
        $temporaryInvoice->setStatus($content['status']);
        $temporaryInvoice->setUbn($content["ubn"]);
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
        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $id), 200);
    }

    /**
     * @param Request $request
     * @Method("DELETE")
     * @Route("/{id}")
     * @return JsonResponse
     */
    function deleteInvoice(Request $request, Invoice $id)
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) {return $validationResult->getJsonResponse();}
        if ($id->getStatus() == "NOT SEND" || $id->getStatus() == "INCOMPLETE"){
        $id->setIsDeleted(true);
        $this->persistAndFlush($id);
        }
        else {
            return new JsonResponse(array(Constant::RESULT_NAMESPACE => "Error, you tried to remove an invoice that was already send"), 200);
        }
        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $id), 200);
    }

    /**
     * @Method("GET")
     * @Route("/date")
     */
    function returnDate(){
        $date = new \DateTime();
        $year = $date->format('Y');
        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $year), 200);
    }

    /**
     * @Method("PUT")
     * @Route("/{id}/date")
     * @ParamConverter("id", class="")
     */
    function setDate(Invoice $id) {
        $id->setInvoiceDate(new \DateTime());
        $this->persistAndFlush($id);
        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $id), 200);
    }

}