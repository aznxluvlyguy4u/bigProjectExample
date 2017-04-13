<?php
/**
 * Created by IntelliJ IDEA.
 * User: werner
 * Date: 6-4-17
 * Time: 10:49
 */

namespace AppBundle\Controller;


use AppBundle\Constant\Constant;
use AppBundle\Entity\InvoiceRuleTemplate;
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
 * @Route("/api/v1/invoice_rule_templates")
 */
class InvoiceRuleTemplateAPIController extends APIController implements InvoiceRuleTemplateAPIControllerInterface
{

    /**
     * @Route("")
     * @param Request $request
     * @Method("GET")
     * @return jsonResponse
     */
    public function getInvoiceRuleTemplates(Request $request)
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) { return $validationResult->getJsonResponse(); }

        $repository = $this->getDoctrine()->getRepository(InvoiceRuleTemplate::class);
        $ruleTemplates = $repository->findAll();
        $output = $this->getDecodedJson($ruleTemplates, JMSGroups::INVOICE_RULE_TEMPLATE);

        return new JsonResponse([Constant::RESULT_NAMESPACE => $output], 200);
    }

    /**
     * @Route("")
     * @param Request $request
     * @Method("POST")
     * @return jsonResponse
     */
    public function createInvoiceRuleTemplate(Request $request)
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) { return $validationResult->getJsonResponse(); }

        $content = $this->getContentAsArray($request);

        $ruleTemplate = $this->getObjectFromContent($content, InvoiceRuleTemplate::class);
        $this->persistAndFlush($ruleTemplate);

        $output = $this->getDecodedJson($ruleTemplate, JMSGroups::INVOICE_RULE_TEMPLATE);
        return new JsonResponse([Constant::RESULT_NAMESPACE => $output], 200);
    }

    /**
     * @Route("")
     * @param Request $request
     * @Method("PUT")
     * @return jsonResponse
     */
    public function updateInvoiceRuleTemplate(Request $request)
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) { return $validationResult->getJsonResponse(); }

        $content = $this->getContentAsArray($request);

        /** @var InvoiceRuleTemplate $updatedRuleTemplate */
        $updatedRuleTemplate = $this->getObjectFromContent($content, InvoiceRuleTemplate::class);

        $repository = $this->getDoctrine()->getRepository(InvoiceRuleTemplate::class);
        /** @var InvoiceRuleTemplate $currentRuleTemplate */
        $currentRuleTemplate = $repository->find($updatedRuleTemplate->getId());
        if(!$currentRuleTemplate) { return Validator::createJsonResponse('THE INVOICE RULE TEMPLATE IS NOT FOUND.', 428); }

        $currentRuleTemplate->copyValues($updatedRuleTemplate);
        $this->persistAndFlush($currentRuleTemplate);

        $output = $this->getDecodedJson($updatedRuleTemplate, JMSGroups::INVOICE_RULE_TEMPLATE);
        return new JsonResponse([Constant::RESULT_NAMESPACE => $output], 200);
    }

    /**
     * @Route("/{id}")
     * @param Request $request
     * @Method("DELETE")
     * @return jsonResponse
     */
    public function deleteInvoiceRuleTemplate(Request $request, InvoiceRuleTemplate $invoiceRuleTemplate)
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) { return $validationResult->getJsonResponse(); }

        $repository = $this->getDoctrine()->getRepository(InvoiceRuleTemplate::class);
        $ruleTemplate = $repository->find($invoiceRuleTemplate);

        if(!$ruleTemplate) { return Validator::createJsonResponse('THE INVOICE RULE TEMPLATE IS NOT FOUND.', 428); }

        $this->getDoctrine()->getManager()->remove($ruleTemplate);
        $this->getDoctrine()->getManager()->flush();

        $output = $this->getDecodedJson($ruleTemplate, JMSGroups::INVOICE_RULE_TEMPLATE);
        return new JsonResponse([Constant::RESULT_NAMESPACE => $output], 200);
    }
}