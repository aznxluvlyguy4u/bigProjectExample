<?php

namespace AppBundle\Controller;

use AppBundle\Cache\AnimalCacher;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Exterior;
use AppBundle\Entity\ExteriorRepository;
use AppBundle\Entity\Inspector;
use AppBundle\Entity\InspectorAuthorization;
use AppBundle\Entity\InspectorAuthorizationRepository;
use AppBundle\Entity\InspectorRepository;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\InspectorMeasurementType;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Util\MeasurementsUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Util\Validator;
use AppBundle\Validation\AdminValidator;
use AppBundle\Validation\AnimalDetailsValidator;
use AppBundle\Validation\ExteriorValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/api/v1/measurements")
 */
class MeasurementAPIController extends APIController implements MeasurementAPIControllerInterface
{

    /**
     *
     * Update an exterior measurement for a specific ULN and measurementDate. For example NL100029511721 and 2016-12-05
     *
     * @ApiDoc(
     *   section = "Measurements",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Update an exterior measurement for a specific ULN and measurementDate"
     * )
     *
     * @param Request $request the request object
     * @param String $ulnString
     * @return jsonResponse
     * @Route("/{ulnString}/exteriors")
     * @Method("POST")
     */
    public function createExteriorMeasurement(Request $request, $ulnString)
    {
        return $this->get('app.measurement')->createExteriorMeasurement($request, $ulnString);
    }


    /**
     *
     * Update or Deactivate an exterior measurement for a specific ULN and measurementDate. For example NL100029511721 and 2016-12-05
     *
     * @ApiDoc(
     *   section = "Measurements",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Update or Deactivate an exterior measurement for a specific ULN and measurementDate"
     * )
     *
     * @param Request $request the request object
     * @param String $ulnString
     * @param String $measurementDateString
     * @return jsonResponse
     * @Route("/{ulnString}/exteriors/{measurementDateString}")
     * @Method("PUT")
     */
    public function editExteriorMeasurement(Request $request, $ulnString, $measurementDateString)
    {
        return $this->get('app.measurement')->editExteriorMeasurement($request, $ulnString, $measurementDateString);
    }


    /**
     *
     * Return the allowed exterior measurement kinds for a specific ULN and measurementDate. For example NL100029511721 and 2016-12-05
     *
     * @ApiDoc(
     *   section = "Measurements",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Update an exterior measurement for a specific ULN and measurementDate"
     * )
     *
     * @param Request $request the request object
     * @param String $ulnString
     * @return jsonResponse
     * @Route("/{ulnString}/exteriors/kinds")
     * @Method("GET")
     */
    public function getAllowedExteriorKinds(Request $request, $ulnString)
    {
        return $this->get('app.measurement')->getAllowedExteriorKinds($request, $ulnString);
    }


    /**
     *
     * Return the allowed exterior measurement kinds for Edits for a specific ULN and measurementDate. For example NL100029511721 and 2016-12-05.
     * For edits the current kind is also allowed.
     *
     * @ApiDoc(
     *   section = "Measurements",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Update an exterior measurement for Edits for a specific ULN and measurementDate"
     * )
     *
     * @param Request $request the request object
     * @param String $ulnString
     * @param String $measurementDateString
     * @return jsonResponse
     * @Route("/{ulnString}/exteriors/kinds/{measurementDateString}")
     * @Method("GET")
     */
    public function getAllowedExteriorKindsForEdit(Request $request, $ulnString, $measurementDateString)
    {
        return $this->get('app.measurement')->getAllowedExteriorKindsForEdit($request, $ulnString, $measurementDateString);
    }


    /**
     *
     * Return the allowed exterior measurement kinds for a specific ULN and measurementDate. For example NL100029511721 and 2016-12-05
     *
     * @ApiDoc(
     *   section = "Measurements",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Update an exterior measurement for a specific ULN and measurementDate"
     * )
     *
     * @param Request $request the request object
     * @param string $ulnString
     * @return jsonResponse
     * @Route("/{ulnString}/exteriors/inspectors")
     * @Method("GET")
     */
    public function getAllowedInspectorsForExteriorMeasurements(Request $request, $ulnString)
    {
        return $this->get('app.measurement')->getAllowedInspectorsForExteriorMeasurements($request, $ulnString);
    }

}