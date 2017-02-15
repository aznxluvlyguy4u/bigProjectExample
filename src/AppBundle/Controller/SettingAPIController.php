<?php

namespace AppBundle\Controller;


use AppBundle\Constant\Constant;
use AppBundle\Entity\InvoiceRuleTemplate;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Util\Validator;
use AppBundle\Validation\AdminValidator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;

/**
 * Class SettingAPIController
 * @package AppBundle\Controller
 * @Route("/api/v1/settings")
 */
class SettingAPIController extends APIController implements SettingAPIControllerInterface
{
    const INVOICE_JMS_GROUP = 'INVOICE';

    /**
     * @Route("/invoice-rules")
     * @param Request $request
     * @Method("GET")
     * @return jsonResponse
     */
    public function getInvoiceRuleTemplate(Request $request)
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) { return $validationResult->getJsonResponse(); }

        $repository = $this->getDoctrine()->getRepository(InvoiceRuleTemplate::class);
        $ruleTemplates = $repository->findAll();
        $output = $this->getDecodedJson($ruleTemplates, self::INVOICE_JMS_GROUP);

        return new JsonResponse([Constant::RESULT_NAMESPACE => $output], 200);
    }

    /**
     * @Route("/invoice-rules")
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

        $output = $this->getDecodedJson($ruleTemplate, self::INVOICE_JMS_GROUP);
        return new JsonResponse([Constant::RESULT_NAMESPACE => $output], 200);
    }


    /**
     * @Route("/invoice-rules")
     * @param Request $request
     * @Method("PUT")
     * @return jsonResponse
     */
    public function changeInvoiceRuleTemplate(Request $request)
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

        $output = $this->getDecodedJson($updatedRuleTemplate, self::INVOICE_JMS_GROUP);
        return new JsonResponse([Constant::RESULT_NAMESPACE => $output], 200);
    }

    /**
     * @Route("/invoice-rules/{id}")
     * @param Request $request
     * @Method("DELETE")
     * @return jsonResponse
     */
    public function deleteInvoiceRuleTemplate(Request $request, $id)
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) { return $validationResult->getJsonResponse(); }

        $repository = $this->getDoctrine()->getRepository(InvoiceRuleTemplate::class);
        $ruleTemplate = $repository->find($id);

        if(!$ruleTemplate) { return Validator::createJsonResponse('THE INVOICE RULE TEMPLATE IS NOT FOUND.', 428); }

        $this->getDoctrine()->getManager()->remove($ruleTemplate);
        $this->getDoctrine()->getManager()->flush();

        $output = $this->getDecodedJson($ruleTemplate, self::INVOICE_JMS_GROUP);
        return new JsonResponse([Constant::RESULT_NAMESPACE => $output], 200);
    }







    /**
     * Edit reasons of loss drop down options.
     *
     * @Route("/reasons-of-loss")
     * @param Request $request
     * @Method("PUT")
     * @return jsonResponse
     */
    public function editReasonsOfLoss(Request $request)
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) { return $validationResult->getJsonResponse(); }

        // TODO: Implement editReasonOfLoss() method.
        $outputArray = array();

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $outputArray), 200);
    }

    /**
     * Edit reasons of depart drop down options.
     *
     * @Route("/reasons-of-depart")
     * @param Request $request
     * @Method("PUT")
     * @return jsonResponse
     */
    public function editReasonsOfDepart(Request $request)
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) { return $validationResult->getJsonResponse(); }

        // TODO: Implement editReasonOfDepart() method.
        $outputArray = array();

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $outputArray), 200);
    }

    /**
     * Edit treatments drop down options.
     *
     * @Route("/treatment-options")
     * @param Request $request
     * @Method("PUT")
     * @return jsonResponse
     */
    public function editTreatmentOptions(Request $request)
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) { return $validationResult->getJsonResponse(); }

        // TODO: Implement editTreatmentOptions() method.
        $outputArray = array();

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $outputArray), 200);
    }

    /**
     * Edit contactform options.
     *
     * @Route("/contactform-options")
     * @param Request $request
     * @Method("PUT")
     * @return jsonResponse
     */
    public function editContactFormOptions(Request $request)
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) { return $validationResult->getJsonResponse(); }

        // TODO: Implement editContactFormOptions() method.
        $outputArray = array();

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $outputArray), 200);
    }

}