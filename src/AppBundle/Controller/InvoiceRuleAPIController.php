<?php
/**
 * Created by IntelliJ IDEA.
 * User: werner
 * Date: 5-4-17
 * Time: 14:45
 */

namespace AppBundle\Controller;


use AppBundle\Constant\Constant;
use AppBundle\Entity\InvoiceRule;
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
 * Class InvoiceRuleAPIController
 * @package AppBundle\Controller
 * @Route("/invoice-rules")
 */
class InvoiceRuleAPIController extends APIController implements InvoiceRuleAPIControllerInterface
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

        $repository = $this->getDoctrine()->getRepository(InvoiceRule::class);
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

        $rule = $this->getObjectFromContent($content, InvoiceRule::class);
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

        /** @var InvoiceRule $updatedRule */
        $updatedRule = $this->getObjectFromContent($content, InvoiceRule::class);

        $repository = $this->getDoctrine()->getRepository(InvoiceRule::class);
        /** @var InvoiceRule $currentRule */
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
    public function deleteInvoiceRule(Request $request, InvoiceRule $invoiceRule)
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) { return $validationResult->getJsonResponse(); }

        $repository = $this->getDoctrine()->getRepository(InvoiceRule::class);
        $rule = $repository->find($invoiceRule);

        if(!$rule) { return Validator::createJsonResponse('THE INVOICE RULE  IS NOT FOUND.', 428); }

        $this->getDoctrine()->getManager()->remove($rule);
        $this->getDoctrine()->getManager()->flush();

        $output = $this->getDecodedJson($rule, JMSGroups::INVOICE_RULE);
        return new JsonResponse([Constant::RESULT_NAMESPACE => $output], 200);
    }

    /**
     * @param InvoiceRule $invoiceRule
     * @param Invoice $invoice
     * @Method("PUT")
     * @Route("{invoice}/{invoiceRule}")
     */
    public function linkInvoiceRuleToInvoice(InvoiceRule $invoiceRule, Invoice $invoice)
    {
        // TODO: Implement linkInvoiceRuleToInvoice() method.
    }

}