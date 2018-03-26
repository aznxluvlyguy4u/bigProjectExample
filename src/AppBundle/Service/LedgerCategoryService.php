<?php


namespace AppBundle\Service;


use AppBundle\Entity\LedgerCategory;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use Symfony\Component\HttpFoundation\Request;

class LedgerCategoryService extends ControllerServiceBase
{
    /**
     * @param Request $request
     * @return \AppBundle\Component\HttpFoundation\JsonResponse
     */
    public function getAllByRequest(Request $request)
    {
        $activeOnly = RequestUtil::getBooleanQuery($request, QueryParameter::ACTIVE_ONLY, true);
        $ledgerCategories = $this->getAll($activeOnly);

        return ResultUtil::successResult($this->getBaseSerializer()->getDecodedJson($ledgerCategories, [JmsGroup::BASIC], true));
    }

    /**
     * @param bool $activeOnly
     * @return LedgerCategory[]
     */
    public function getAll($activeOnly)
    {
        if ($activeOnly) {
            return $this->getManager()->getRepository(LedgerCategory::class)->findBy(['isActive' => true]);
        }
        return $this->getManager()->getRepository(LedgerCategory::class)->findAll();
    }
}