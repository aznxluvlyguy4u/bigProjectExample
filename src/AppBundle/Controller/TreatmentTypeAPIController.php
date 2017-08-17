<?php


namespace AppBundle\Controller;

use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/api/v1/treatment-types")
 */
class TreatmentTypeAPIController extends APIController implements TreatmentTypeAPIControllerInterface
{
    /**
     * Get all active treatment types by query
     *
     * @ApiDoc(
     *   section = "Treatment Type",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   parameters={
     *      {
     *        "name"="active_only",
     *        "dataType"="string",
     *        "required"=false,
     *        "description"="set to false to include deactivated entities",
     *        "format"="?active_only=false"
     *      },
     *      {
     *        "name"="type",
     *        "dataType"="string",
     *        "required"=false,
     *        "description"="include if only one type (INDIVIDUAL or LOCATION) needs to be returned",
     *        "format"="?type=individual"
     *      },
     *   },
     *   resource = true,
     *   description = "Get all active treatment types"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("")
     * @Method("GET")
     */
    function getByQuery(Request $request)
    {
        return $this->getTreatmentTypeService()->getByQuery($request);
    }


    /**
     * Post treatment type
     *
     * @ApiDoc(
     *   section = "Treatment Type",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Post treatment type"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("")
     * @Method("POST")
     */
    function create(Request $request)
    {
        return $this->getTreatmentTypeService()->create($request);
    }


    /**
     * Edit treatment type
     *
     * @ApiDoc(
     *   section = "Treatment Type",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Edit treatment type"
     * )
     * @param Request $request the request object
     * @param int $treatmentTypeId
     * @return JsonResponse
     * @Route("/{treatmentTypeId}")
     * @Method("PUT")
     */
    function edit(Request $request, $treatmentTypeId)
    {
        return $this->getTreatmentTypeService()->edit($request, $treatmentTypeId);
    }


    /**
     * Delete treatment type
     *
     * @ApiDoc(
     *   section = "Treatment Type",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Delete treatment type"
     * )
     * @param Request $request the request object
     * @param int $treatmentTypeId
     * @return JsonResponse
     * @Route("/{treatmentTypeId}")
     * @Method("DELETE")
     */
    function delete(Request $request, $treatmentTypeId)
    {
        return $this->getTreatmentTypeService()->delete($request, $treatmentTypeId);
    }


}