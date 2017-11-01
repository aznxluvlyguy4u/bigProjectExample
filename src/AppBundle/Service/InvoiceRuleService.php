<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Controller\InvoiceRuleTemplateAPIControllerInterface;
use AppBundle\Entity\InvoiceRuleTemplate;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use Symfony\Component\HttpFoundation\Request;

class InvoiceRuleService extends ControllerServiceBase implements InvoiceRuleTemplateAPIControllerInterface
{
    /**
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getInvoiceRules(Request $request)
    {
        if (!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN))
        { return AdminValidator::getStandardErrorResponse(); }

        $repository = $this->getManager()->getRepository(InvoiceRuleTemplate::class);
        $category = $request->get('category');
        if ($category != null) {
            $rules = $repository->findBy(array('category' => $category));
        }
        else{
            $rules = $repository->findAll();
        }
        $output = $this->getBaseSerializer()->getDecodedJson($rules, JmsGroup::INVOICE_RULE);

        return ResultUtil::successResult($output);
    }


    /**
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createInvoiceRule(Request $request)
    {
        if (!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN))
        { return AdminValidator::getStandardErrorResponse(); }

        $rule = $this->getBaseSerializer()->deserializeToObject($request->getContent(), InvoiceRuleTemplate::class);
        $this->persistAndFlush($rule);

        $output = $this->getBaseSerializer()->getDecodedJson($rule, JmsGroup::INVOICE_RULE);
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

        /** @var InvoiceRuleTemplate $updatedRule */
        $updatedRule = $this->getBaseSerializer()->deserializeToObject($content, InvoiceRuleTemplate::class);
        $repository = $this->getManager()->getRepository(InvoiceRuleTemplate::class);
        /** @var InvoiceRuleTemplate $currentRule */
        $currentRule = $repository->find($content['id']);
        if(!$currentRule) { return ResultUtil::errorResult('THE INVOICE RULE  IS NOT FOUND.', 428); }

        $currentRule->copyValues($updatedRule);
        $this->persistAndFlush($currentRule);

        $output = $this->getBaseSerializer()->getDecodedJson($updatedRule, JmsGroup::INVOICE_RULE);
        return ResultUtil::successResult($output);
    }


    /**
     * @param Request $request
     * @param InvoiceRuleTemplate $invoiceRule
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteInvoiceRule(Request $request, InvoiceRuleTemplate $invoiceRule)
    {
        if (!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN))
        { return AdminValidator::getStandardErrorResponse(); }

        $repository = $this->getManager()->getRepository(InvoiceRuleTemplate::class);
        $rule = $repository->find($invoiceRule);

        if(!$rule) { return ResultUtil::errorResult('THE INVOICE RULE  IS NOT FOUND.', 428); }

        $this->getManager()->remove($rule);
        $this->getManager()->flush();

        $output = $this->getBaseSerializer()->getDecodedJson($rule, JmsGroup::INVOICE_RULE);
        return ResultUtil::successResult($output);
    }

}