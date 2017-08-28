<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\InvoiceRule;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use Symfony\Component\HttpFoundation\Request;

class InvoiceRuleTemplateService extends ControllerServiceBase
{
    
    /**
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getInvoiceRuleTemplate(Request $request)
    {
        if (!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN))
        { return AdminValidator::getStandardErrorResponse(); }

        $repository = $this->getManager()->getRepository(InvoiceRule::class);
        $ruleTemplates = $repository->findAll();
        $output = $this->getBaseSerializer()->getDecodedJson($ruleTemplates, JmsGroup::INVOICE);

        return ResultUtil::successResult($output);
    }


    /**
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createInvoiceRuleTemplate(Request $request)
    {
        if (!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN))
        { return AdminValidator::getStandardErrorResponse(); }

        $content = RequestUtil::getContentAsArray($request);

        $ruleTemplate = $this->getBaseSerializer()->deserializeToObject($content, InvoiceRule::class);
        $this->persistAndFlush($ruleTemplate);

        $output = $this->getBaseSerializer()->getDecodedJson($ruleTemplate, JmsGroup::INVOICE);
        return ResultUtil::successResult($output);
    }


    /**
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function changeInvoiceRuleTemplate(Request $request)
    {
        if (!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN))
        { return AdminValidator::getStandardErrorResponse(); }

        $content = RequestUtil::getContentAsArray($request);

        /** @var InvoiceRule $updatedRuleTemplate */
        $updatedRuleTemplate = $this->getBaseSerializer()->deserializeToObject($content, InvoiceRule::class);

        $repository = $this->getManager()->getRepository(InvoiceRule::class);
        /** @var InvoiceRule $currentRuleTemplate */
        $currentRuleTemplate = $repository->find($updatedRuleTemplate->getId());
        if(!$currentRuleTemplate) { return ResultUtil::errorResult('THE INVOICE RULE TEMPLATE IS NOT FOUND.', 428); }

        $currentRuleTemplate->copyValues($updatedRuleTemplate);
        $this->persistAndFlush($currentRuleTemplate);

        $output = $this->getBaseSerializer()->getDecodedJson($updatedRuleTemplate, JmsGroup::INVOICE);
        return ResultUtil::successResult($output);
    }


    /**
     * @param Request $request
     * @param $id
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteInvoiceRuleTemplate(Request $request, $id)
    {
        if (!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN))
        { return AdminValidator::getStandardErrorResponse(); }

        $repository = $this->getManager()->getRepository(InvoiceRule::class);
        $ruleTemplate = $repository->find($id);

        if(!$ruleTemplate) { return ResultUtil::errorResult('THE INVOICE RULE TEMPLATE IS NOT FOUND.', 428); }

        $this->getManager()->remove($ruleTemplate);
        $this->getManager()->flush();

        $output = $this->getBaseSerializer()->getDecodedJson($ruleTemplate, JmsGroup::INVOICE);
        return ResultUtil::successResult($output);
    }
}