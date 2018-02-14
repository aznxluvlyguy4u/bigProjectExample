<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Controller\InvoiceRuleAPIControllerInterface;
use AppBundle\Entity\InvoiceRule;
use AppBundle\Entity\InvoiceRuleRepository;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use Symfony\Component\HttpFoundation\Request;

class InvoiceRuleService extends ControllerServiceBase implements InvoiceRuleAPIControllerInterface
{
    /**
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getInvoiceRules(Request $request)
    {
        $category = $request->get('category');
        $type = $request->query->get("type");
        if (!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN))
        { return AdminValidator::getStandardErrorResponse(); }
        /** @var InvoiceRuleRepository $repository */
        $repository = $this->getManager()->getRepository(InvoiceRule::class); //TODO replace with InvoiceRule::class?
        $rules = $repository->findByTypeCategory($type, $category);
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

        $rule = $this->getBaseSerializer()->deserializeToObject($request->getContent(), InvoiceRule::class);  //TODO replace with InvoiceRule::class?
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

        /** @var InvoiceRule $updatedRule */
        $updatedRule = $this->getBaseSerializer()->deserializeToObject($content, InvoiceRule::class);
        $repository = $this->getManager()->getRepository(InvoiceRule::class);
        /** @var InvoiceRule $currentRule */
        $currentRule = $repository->find($content['id']);
        if(!$currentRule) { return ResultUtil::errorResult('THE INVOICE RULE  IS NOT FOUND.', 428); }

        $currentRule->copyValues($updatedRule);
        $this->persistAndFlush($currentRule);

        $output = $this->getBaseSerializer()->getDecodedJson($updatedRule, JmsGroup::INVOICE_RULE);
        return ResultUtil::successResult($output);
    }


    /**
     * @param Request $request
     * @param InvoiceRule $invoiceRule
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteInvoiceRule(Request $request, InvoiceRule $invoiceRule)
    {
        if (!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN))
        { return AdminValidator::getStandardErrorResponse(); }

        $repository = $this->getManager()->getRepository(InvoiceRule::class);  //TODO replace with InvoiceRule::class?
        $rule = $repository->find($invoiceRule);

        if(!$rule) { return ResultUtil::errorResult('THE INVOICE RULE IS NOT FOUND.', 428); }

        $this->getManager()->remove($rule);
        $this->getManager()->flush();

        $output = $this->getBaseSerializer()->getDecodedJson($rule, JmsGroup::INVOICE_RULE);
        return ResultUtil::successResult($output);
    }

}