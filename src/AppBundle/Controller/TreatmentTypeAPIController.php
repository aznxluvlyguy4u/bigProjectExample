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
     *   section = "Treatment",
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

}