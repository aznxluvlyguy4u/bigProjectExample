<?php
/**
 * Created by IntelliJ IDEA.
 * User: werner
 * Date: 5-4-17
 * Time: 14:45
 */

namespace AppBundle\Controller;


use AppBundle\Constant\Constant;
use AppBundle\Entity\InvoiceRuleTemplate;
use AppBundle\Entity\Invoice;
use AppBundle\Enumerator\JMSGroups;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Util\Validator;
use AppBundle\Validation\AdminValidator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;


/**
 * Class InvoiceRuleTemplateAPIController
 * @package AppBundle\Controller
 * @Route("/invoice-rules")
 */
class InvoiceRuleTemplateAPIController extends APIController implements InvoiceRuleTemplateAPIControllerInterface
{
    /**
     * @Route("")
     * @param Request $request
     * @Method("GET")
     * @return jsonResponse
     */
    public function getInvoiceRules(Request $request)
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) { return $validationResult->getJsonResponse(); }

        $repository = $this->getDoctrine()->getRepository(InvoiceRuleTemplate::class);
        $rules = $repository->findAll();
        $output = $this->getDecodedJson($rules, JMSGroups::INVOICE_RULE);

        return new JsonResponse([Constant::RESULT_NAMESPACE => $output], 200);
    }

    /**
     * @Route("")
     * @param Request $request
     * @Method("POST")
     * @return jsonResponse
     */
    public function createInvoiceRule(Request $request)
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) { return $validationResult->getJsonResponse(); }

        $content = $this->getContentAsArray($request);

        $rule = $this->getObjectFromContent($content, InvoiceRuleTemplate::class);
        $this->persistAndFlush($rule);

        $output = $this->getDecodedJson($rule, JMSGroups::INVOICE_RULE);
        return new JsonResponse([Constant::RESULT_NAMESPACE => $output], 200);
    }

    /**
     * @Route("")
     * @param Request $request
     * @Method("PUT")
     * @return jsonResponse
     */
    public function updateInvoiceRule(Request $request)
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) { return $validationResult->getJsonResponse(); }

        $content = $this->getContentAsArray($request);

        /** @var InvoiceRuleTemplate $updatedRule */
        $updatedRule = $this->getObjectFromContent($content, InvoiceRuleTemplate::class);

        $repository = $this->getDoctrine()->getRepository(InvoiceRuleTemplate::class);
        /** @var InvoiceRuleTemplate $currentRule */
        $currentRule = $repository->find($updatedRule->getId());
        if(!$currentRule) { return Validator::createJsonResponse('THE INVOICE RULE  IS NOT FOUND.', 428); }

        $currentRule->copyValues($updatedRule);
        $this->persistAndFlush($currentRule);

        $output = $this->getDecodedJson($updatedRule, JMSGroups::INVOICE_RULE);
        return new JsonResponse([Constant::RESULT_NAMESPACE => $output], 200);
    }

    /**
     * @Route("/{id}")
     * @param Request $request
     * @Method("DELETE")
     * @return jsonResponse
     */
    public function deleteInvoiceRule(Request $request, InvoiceRuleTemplate $invoiceRule)
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) { return $validationResult->getJsonResponse(); }

        $repository = $this->getDoctrine()->getRepository(InvoiceRuleTemplate::class);
        $rule = $repository->find($invoiceRule);

        if(!$rule) { return Validator::createJsonResponse('THE INVOICE RULE  IS NOT FOUND.', 428); }

        $this->getDoctrine()->getManager()->remove($rule);
        $this->getDoctrine()->getManager()->flush();

        $output = $this->getDecodedJson($rule, JMSGroups::INVOICE_RULE);
        return new JsonResponse([Constant::RESULT_NAMESPACE => $output], 200);
    }

    /**
     * @param InvoiceRuleTemplate $invoiceRule
     * @param Invoice $invoice
     * @Method("PUT")
     * @Route("{invoice}/{invoiceRule}")
     */
    public function linkInvoiceRuleToInvoice(InvoiceRuleTemplate $invoiceRule, Invoice $invoice)
    {
        // TODO: Implement linkInvoiceRuleToInvoice() method.
    }

}