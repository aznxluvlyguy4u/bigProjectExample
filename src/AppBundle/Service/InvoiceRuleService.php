<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Controller\InvoiceRuleAPIControllerInterface;
use AppBundle\Entity\InvoiceRule;
use AppBundle\Entity\LedgerCategory;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\InvoiceRuleType;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\Validator;
use AppBundle\Validation\AdminValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InvoiceRuleService extends ControllerServiceBase implements InvoiceRuleAPIControllerInterface
{
    /**
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getInvoiceRules(Request $request)
    {
        $type = $request->query->get(QueryParameter::TYPE_QUERY);
        $isBatch = $request->query->get(QueryParameter::IS_BATCH);
        $activeOnly = RequestUtil::getBooleanQuery($request,QueryParameter::ACTIVE_ONLY,false);
        if (!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN))
        { return AdminValidator::getStandardErrorResponse(); }

        if ($isBatch) {
            $rules = $this->getManager()->getRepository(InvoiceRule::class)->findBatchRules();
            return ResultUtil::successResult($rules);
        }
        $rules = $this->getManager()->getRepository(InvoiceRule::class)->findByType($type, $activeOnly);
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

        /** @var InvoiceRule $rule */
        $rule = $this->getBaseSerializer()->deserializeToObject($request->getContent(), InvoiceRule::class);

        $validationResult = $this->validateInsertValues($rule);
        if ($validationResult instanceof JsonResponse) {
            return $validationResult;
        }

        $ledgerCategory = $this->getManager()->getRepository(LedgerCategory::class)
            ->find($rule->getLedgerCategory()->getId());
        if(!$ledgerCategory) {
            return ResultUtil::errorResult('LEDGER CATEGORY IS NOT FOUND.', Response::HTTP_PRECONDITION_REQUIRED);
        }

        $rule->setLedgerCategory($ledgerCategory);
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

        /** @var InvoiceRule $updatedRule */
        $updatedRule = $this->getBaseSerializer()->deserializeToObject($request->getContent(), InvoiceRule::class);

        $validationResult = $this->validateUpdateValues($updatedRule);
        if ($validationResult instanceof JsonResponse) {
            return $validationResult;
        }

        $currentRule = $this->getManager()->getRepository(InvoiceRule::class)->find($updatedRule->getId());
        if(!$currentRule) {
            return ResultUtil::errorResult('THE INVOICE RULE IS NOT FOUND.', Response::HTTP_PRECONDITION_REQUIRED);
        }

        $ledgerCategory = $this->getManager()->getRepository(LedgerCategory::class)
            ->find($updatedRule->getLedgerCategory()->getId());
        if(!$ledgerCategory) {
            return ResultUtil::errorResult('LEDGER CATEGORY IS NOT FOUND.', Response::HTTP_PRECONDITION_REQUIRED);
        }

        $currentRule->copyValues($updatedRule);
        $currentRule->setLedgerCategory($ledgerCategory);

        $this->persistAndFlush($currentRule);

        $output = $this->getBaseSerializer()->getDecodedJson($updatedRule, JmsGroup::INVOICE_RULE);
        return ResultUtil::successResult($output);
    }


    /**
     * @param InvoiceRule $newRule
     * @return JsonResponse|bool
     */
    private function validateInsertValues(InvoiceRule $newRule)
    {
        if ($newRule === null) {
            return ResultUtil::errorResult('REQUEST BODY IS EMPTY', Response::HTTP_PRECONDITION_REQUIRED);
        }

        if ($newRule->getLedgerCategory() === null || $newRule->getLedgerCategory()->getId() === null) {
            return ResultUtil::errorResult('LEDGER CATEGORY IS MISSING', Response::HTTP_PRECONDITION_REQUIRED);
        }

        if ($newRule->getDescription() === null || $newRule->getDescription() === '') {
            return ResultUtil::errorResult('DESCRIPTION IS MISSING', Response::HTTP_PRECONDITION_REQUIRED);
        }

        if ($newRule->getPriceExclVat() === null) {
            return ResultUtil::errorResult('PRICE EXCL. VAT IS MISSING', Response::HTTP_PRECONDITION_REQUIRED);

        } elseif (!Validator::hasValidNumberOfCurrencyDecimals($newRule->getPriceExclVat())) {
            return ResultUtil::errorResult('CURRENCY CANNOT EXCEED '.Validator::MAX_NUMBER_OF_CURRENCY_INPUT_DECIMALS.' DECIMAL SPACES', Response::HTTP_PRECONDITION_REQUIRED);
        }

        if ($newRule->getVatPercentageRate() === null) {
            return ResultUtil::errorResult('VAT PERCENTAGE RATE IS MISSING', Response::HTTP_PRECONDITION_REQUIRED);
        }

        return true;
    }


    /**
     * @param InvoiceRule $updatedRule
     * @return JsonResponse|boolean
     */
    private function validateUpdateValues(InvoiceRule $updatedRule)
    {
        if ($updatedRule && $updatedRule->getId() === null) {
            return ResultUtil::errorResult('INVOICE RULE ID IS EMPTY', Response::HTTP_PRECONDITION_REQUIRED);
        }

        return $this->validateInsertValues($updatedRule);
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

        $repository = $this->getManager()->getRepository(InvoiceRule::class);
        $rule = $repository->find($invoiceRule);

        if(!$rule) { return ResultUtil::errorResult('THE INVOICE RULE IS NOT FOUND.', Response::HTTP_PRECONDITION_REQUIRED); }

        if ($rule->getType() === InvoiceRuleType::CUSTOM) {
            $this->getManager()->remove($rule);
        } else {
            $rule->setIsDeleted(true);
            $this->getManager()->persist($rule);
        }
        $this->getManager()->flush();

        $output = $this->getBaseSerializer()->getDecodedJson($rule, JmsGroup::INVOICE_RULE);
        return ResultUtil::successResult($output);
    }

}