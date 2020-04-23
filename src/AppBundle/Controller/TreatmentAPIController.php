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
     * Create a treatment for a location
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
     *   description = "Create a treatment for a location",
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/location")
     * @Method("POST")
     */
    function getCreateLocationTreatment(Request $request)
    {
        return $this->get('app.treatment')->createLocationTreatment($request);
    }

    /**
     * Create a treatment for an individual
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
     *   description = "Create a treatment for an individual",
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/individual")
     * @Method("POST")
     */
    function getCreateIndividualTreatment(Request $request)
    {
        return $this->get('app.treatment')->createIndividualTreatment($request);
    }

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
    function createIndividualTreatment(Request $request)
    {
        return $this->get('app.treatment')->createIndividualTreatment($request);
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
    function createLocationTreatment(Request $request)
    {
        return $this->get('app.treatment')->getLocationTreatment($request);
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
    function editIndividualTreatment(Request $request, $treatmentId)
    {
        return $this->get('app.treatment')->editIndividualTreatment($request, $treatmentId);
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
    function editLocationTreatment(Request $request, $treatmentId)
    {
        return $this->get('app.treatment')->editLocationTreatment($request, $treatmentId);
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
    function deleteIndividualTreatment(Request $request, $treatmentId)
    {
        return $this->get('app.treatment')->deleteIndividualTreatment($request, $treatmentId);
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
    function deleteLocationTreatment(Request $request, $treatmentId)
    {
        return $this->get('app.treatment')->deleteLocationTreatment($request, $treatmentId);
    }


}