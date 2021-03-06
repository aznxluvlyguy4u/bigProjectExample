<?php

namespace AppBundle\Controller;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Service\WeightService;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/api/v1/animals-weights")
 */
class WeightAPIController extends APIController
{

    /**
     *
     * Create new weight measurements for the given animals.
     *
     * @ApiDoc(
     *   section = "Weights",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Create new weight measurements for the given animals"
     * )
     *
     * @param Request $request the request object
     * @return jsonResponse
     * @Route("")
     * @Method("POST")
     */
    public function createWeightMeasurements(Request $request)
    {
        return $this->get(WeightService::class)->createWeightMeasurements($request);
    }


    /**
     *
     * Edit DeclareWeight and WeightMeasurements
     *
     * @ApiDoc(
     *   section = "Weights",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Edit Mate"
     * )
     *
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/{messageId}")
     * @Method("PUT")
     */
    public function editWeightMeasurements(Request $request, $messageId)
    {
        return $this->get(WeightService::class)->editWeightMeasurements($request, $messageId);
    }


    /**
     *
     * For the history view, get DeclareWeights which have the following requestState: FINISHED or REVOKED
     *
     * @ApiDoc(
     *   section = "Weights",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Get DeclareWeights which have the following requestState: FINISHED or REVOKED"
     * )
     *
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("-history")
     * @Method("GET")
     */
    public function getDeclareWeightHistory(Request $request)
    {
        return $this->get(WeightService::class)->getDeclareWeightHistory($request);
    }
}