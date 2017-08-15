<?php

namespace AppBundle\Service;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Controller\TreatmentTemplateAPIControllerInterface;
use AppBundle\Util\ResultUtil;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class TreatmentTemplateService
 * @package AppBundle\Service
 */
class TreatmentTemplateService extends ControllerServiceBase implements TreatmentTemplateAPIControllerInterface
{

    public function __construct(EntityManagerInterface $em, IRSerializer $serializer,
                                CacheService $cacheService, UserService $userService)
    {
        parent::__construct($em, $serializer, $cacheService, $userService);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    function getIndividualDefaultTemplates(Request $request)
    {
        // TODO: Implement getIndividualDefaultTemplates() method.

        return ResultUtil::successResult('ok');
    }

    /**
     * @param Request $request
     * @param $ubn
     * @return JsonResponse
     */
    function getIndividualSpecificTemplates(Request $request, $ubn)
    {
        // TODO: Implement getIndividualSpecificTemplates() method.

        return ResultUtil::successResult('ok');
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    function getLocationDefaultTemplates(Request $request)
    {
        // TODO: Implement getLocationDefaultTemplates() method.

        return ResultUtil::successResult('ok');
    }

    /**
     * @param Request $request
     * @param $ubn
     * @return JsonResponse
     */
    function getLocationSpecificTemplates(Request $request, $ubn)
    {
        // TODO: Implement getLocationSpecificTemplates() method.

        return ResultUtil::successResult('ok');
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    function createIndividualTemplate(Request $request)
    {
        // TODO: Implement createIndividualTemplate() method.

        return ResultUtil::successResult('ok');
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    function createLocationTemplate(Request $request)
    {
        // TODO: Implement createLocationTemplate() method.

        return ResultUtil::successResult('ok');
    }

    /**
     * @param Request $request
     * @param $templateId
     * @return JsonResponse
     */
    function editIndividualTemplate(Request $request, $templateId)
    {
        // TODO: Implement editIndividualTemplate() method.

        return ResultUtil::successResult('ok');
    }

    /**
     * @param Request $request
     * @param $templateId
     * @return JsonResponse
     */
    function editLocationTemplate(Request $request, $templateId)
    {
        // TODO: Implement editLocationTemplate() method.

        return ResultUtil::successResult('ok');
    }

    /**
     * @param Request $request
     * @param $templateId
     * @return JsonResponse
     */
    function deleteIndividualTemplate(Request $request, $templateId)
    {
        // TODO: Implement deleteIndividualTemplate() method.

        return ResultUtil::successResult('ok');
    }

    /**
     * @param Request $request
     * @param $templateId
     * @return JsonResponse
     */
    function deleteLocationTemplate(Request $request, $templateId)
    {
        // TODO: Implement deleteLocationTemplate() method.

        return ResultUtil::successResult('ok');
    }


}