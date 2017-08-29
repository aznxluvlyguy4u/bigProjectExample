<?php

namespace AppBundle\Service;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Controller\TreatmentAPIControllerInterface;
use AppBundle\Util\ResultUtil;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class TreatmentTemplateService
 * @package AppBundle\Service
 */
class TreatmentService extends TreatmentServiceBase implements TreatmentAPIControllerInterface
{

    /**
     * @param Request $request
     * @return JsonResponse
     */
    function getIndividualTreatments(Request $request)
    {
        return ResultUtil::successResult('ok');
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    function getLocationTreatments(Request $request)
    {
        return ResultUtil::successResult('ok');
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    function createIndividualTreatments(Request $request)
    {
        return ResultUtil::successResult('ok');
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    function createLocationTreatments(Request $request)
    {
        return ResultUtil::successResult('ok');
    }

    /**
     * @param Request $request
     * @param $treatmentId
     * @return JsonResponse
     */
    function editIndividualTreatments(Request $request, $treatmentId)
    {
        return ResultUtil::successResult('ok');
    }

    /**
     * @param Request $request
     * @param $treatmentId
     * @return JsonResponse
     */
    function editLocationTreatments(Request $request, $treatmentId)
    {
        return ResultUtil::successResult('ok');
    }

    /**
     * @param Request $request
     * @param $treatmentId
     * @return JsonResponse
     */
    function deleteIndividualTreatments(Request $request, $treatmentId)
    {
        return ResultUtil::successResult('ok');
    }

    /**
     * @param Request $request
     * @param $treatmentId
     * @return JsonResponse
     */
    function deleteLocationTreatments(Request $request, $treatmentId)
    {
        return ResultUtil::successResult('ok');
    }


}