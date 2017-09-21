<?php


namespace AppBundle\Controller;

use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/api/v1/treatments")
 */
class TreatmentAPIController extends APIController implements TreatmentAPIControllerInterface
{
    /**
     * Get treatments of the individual scope
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
     *   resource = true,
     *   description = "Get treatments of the individual scope"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/individual")
     * @Method("GET")
     */
    function getIndividualTreatments(Request $request)
    {
        return $this->get('app.treatment')->getIndividualTreatments($request);
    }

    /**
     * Get treatments of the location scope
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
     *   resource = true,
     *   description = "Get treatments of the location scope"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/location")
     * @Method("GET")
     */
    function getLocationTreatments(Request $request)
    {
        return $this->get('app.treatment')->getLocationTreatments($request);
    }

    /**
     * Create treatments of the individual scope
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
     *   resource = true,
     *   description = "Create treatments of the individual scope"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/individual")
     * @Method("POST")
     */
    function createIndividualTreatments(Request $request)
    {
        return $this->get('app.treatment')->createIndividualTreatments($request);
    }

    /**
     * Create treatments of the location scope
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
     *   resource = true,
     *   description = "Create treatments of the location scope"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/location")
     * @Method("POST")
     */
    function createLocationTreatments(Request $request)
    {
        return $this->get('app.treatment')->getLocationTreatments($request);
    }

    /**
     * Edit treatments of the individual scope
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
     *   resource = true,
     *   description = "Edit treatments of the individual scope"
     * )
     * @param Request $request the request object
     * @param int $treatmentId
     * @return JsonResponse
     * @Route("/individual/{treatmentId}")
     * @Method("PUT")
     */
    function editIndividualTreatments(Request $request, $treatmentId)
    {
        return $this->get('app.treatment')->editIndividualTreatments($request, $treatmentId);
    }

    /**
     * Edit treatments of the location scope
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
     *   resource = true,
     *   description = "Edit treatments of the location scope"
     * )
     * @param Request $request the request object
     * @param int $treatmentId
     * @return JsonResponse
     * @Route("/location/{treatmentId}")
     * @Method("PUT")
     */
    function editLocationTreatments(Request $request, $treatmentId)
    {
        return $this->get('app.treatment')->editLocationTreatments($request, $treatmentId);
    }

    /**
     * Deactivate treatments of the individual scope
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
     *   resource = true,
     *   description = "Deactivate treatments of the individual scope"
     * )
     * @param Request $request the request object
     * @param int $treatmentId
     * @return JsonResponse
     * @Route("/individual/{treatmentId}")
     * @Method("DELETE")
     */
    function deleteIndividualTreatments(Request $request, $treatmentId)
    {
        return $this->get('app.treatment')->deleteIndividualTreatments($request, $treatmentId);
    }

    /**
     * Deactivate treatments of the location scope
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
     *   resource = true,
     *   description = "Deactivate treatments of the location scope"
     * )
     * @param Request $request the request object
     * @param int $treatmentId
     * @return JsonResponse
     * @Route("/location/{treatmentId}")
     * @Method("DELETE")
     */
    function deleteLocationTreatments(Request $request, $treatmentId)
    {
        return $this->get('app.treatment')->deleteLocationTreatments($request, $treatmentId);
    }


}