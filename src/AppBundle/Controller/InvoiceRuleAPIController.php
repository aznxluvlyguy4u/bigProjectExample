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
use AppBundle\Entity\InvoiceRuleLocked;
use AppBundle\Entity\InvoiceRule;
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
 * @Route("/api/v1/invoice_rule_templates")
 */
class InvoiceRuleAPIController extends APIController implements InvoiceRuleAPIControllerInterface
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

        $repository = $this->getDoctrine()->getRepository(InvoiceRule::class);
        $ruleTemplates = $repository->findBy(array('isDeleted' => false, 'type' => 'standard'));
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

        $ruleTemplate = $this->getObjectFromContent($content, InvoiceRule::class);
        $lockedRule = $this->getManager()->getRepository(InvoiceRuleLocked::class)
            ->findOneBy(array(
                'priceExclVat' => $ruleTemplate->getPriceExclVat(),
                'vatPercentageRate' => $ruleTemplate->getVatPercentageRate(),
                'description' => $ruleTemplate->getDescription()
            ));
        if ($lockedRule == null) {
            $lockedRule = new InvoiceRuleLocked();
            $lockedRule->copyValues($ruleTemplate);
            $this->persistAndFlush($lockedRule);
            $ruleTemplate->setLockedVersion($lockedRule);
        }
        else {
            $ruleTemplate->setLockedVersion($lockedRule);
        }
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

        /** @var InvoiceRule $updatedRuleTemplate */
        $updatedRuleTemplate = new InvoiceRule();
        $updatedRuleTemplate->setDescription($content['description']);
        $updatedRuleTemplate->setVatPercentageRate($content['vat_percentage_rate']);
        $updatedRuleTemplate->setPriceExclVat($content['price_excl_vat']);

        $repository = $this->getDoctrine()->getRepository(InvoiceRule::class);
        /** @var InvoiceRule $currentRuleTemplate */
        $currentRuleTemplate = $repository->findOneBy(array('id' => $content['id']));
        if(!$currentRuleTemplate) { return Validator::createJsonResponse('THE INVOICE RULE TEMPLATE IS NOT FOUND.', 428); }
        if ($currentRuleTemplate->getType() == 'standard'){
            $lockedRule = $this->getManager()->getRepository(InvoiceRuleLocked::class)
                ->findOneBy(array(
                    'priceExclVat' => $updatedRuleTemplate->getPriceExclVat(),
                    'vatPercentageRate' => $updatedRuleTemplate->getVatPercentageRate(),
                    'description' => $updatedRuleTemplate->getDescription()
                ));
            if ($lockedRule == null) {
                $lockedRule = new InvoiceRuleLocked();
                $lockedRule->copyValues($currentRuleTemplate);
                $this->persistAndFlush($lockedRule);
                $currentRuleTemplate->setLockedVersion($lockedRule);
            }
            else {
                $currentRuleTemplate->setLockedVersion($lockedRule);
            }
        }
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
    public function deleteInvoiceRuleTemplate(Request $request, InvoiceRule $invoiceRuleTemplate)
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) { return $validationResult->getJsonResponse(); }

        $repository = $this->getDoctrine()->getRepository(InvoiceRule::class);
        /** @var InvoiceRule $ruleTemplate */
        $ruleTemplate = $repository->find($invoiceRuleTemplate);

        if(!$ruleTemplate) { return Validator::createJsonResponse('THE INVOICE RULE TEMPLATE IS NOT FOUND.', 428); }

        $ruleTemplate->setIsDeleted(true);
        $invoices = $ruleTemplate->getInvoices();
        foreach ($invoices as $invoice){
            $invoice->removeInvoiceRule($ruleTemplate);
            if ($invoice->getStatus() == "NOT SEND" || $invoice->getStatus() == "INCOMPLETE"){
                $invoice->removeLockedInvoiceRule($ruleTemplate->getLockedVersion());
                $this->persistAndFlush($invoice);
            }
        }
        $this->persistAndFlush($ruleTemplate);

        $output = $this->getDecodedJson($ruleTemplate, JMSGroups::INVOICE_RULE_TEMPLATE);
        return new JsonResponse([Constant::RESULT_NAMESPACE => $output], 200);
    }
}