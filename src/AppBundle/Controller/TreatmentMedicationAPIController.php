<?php

namespace AppBundle\Controller;

use AppBundle\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/api/v1/treatment-medications")
 */
class TreatmentMedicationAPIController extends APIController implements TreatmentMedicationAPIControllerInterface
{
    /**
     * Get all active treatment medications by query
     *
     * ### Request body ###
     *
     *  none
     *
     * ### Response body ###
     *
     *  {
     *      "result": [
     *          {
     *              "id": 1,
     *              "name": "Codichol",
     *              "is_active": true
     *          },
     *          {
     *              "id": 2,
     *              "name": "Kamfer ichtyo zalf",
     *              "is_active": true
     *          },
     *      ]
     *  }
     *
     * @ApiDoc(
     *   section = "Treatment Medication",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"=true,
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
     *      }
     *   },
     *   resource = true,
     *   description = "Get all active treatment medications",
     *   statusCodes={200="Returned when successful"},
     *   input="json",
     *   output="json"
     *
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("")
     * @Method("GET")
     */
    function getByQuery(Request $request)
    {
        return $this->get('app.treatment.medication')->getByQuery($request);
    }

    /**
     * Post treatment medication
     *
     * ### Request body ###
     *
     *  {
     *      "name": "Codichol"
     *  }
     *
     * ### Response body ###
     *
     *  {
     *      "result": {
     *          "id": 3,
     *          "name": "Codichol"
     *      }
     *  }
     *
     * @ApiDoc(
     *   section = "Treatment Medication",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"=true,
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Post treatment medication",
     *   statusCodes={200="Returned when successful", 428="Returned for invalid input"},
     *   input="json",
     *   output="json"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("")
     * @Method("POST")
     */
    function create(Request $request)
    {
        return $this->get('app.treatment.medication')->create($request);
    }

    /**
     * Edit treatment medication description
     *
     * ### Request body ###
     *
     *  {
     *      "name": "Codichol"
     *  }
     *
     * ### Response body ###
     *
     *  {
     *      "result": {
     *          "id": 3,
     *          "name": "Codichol"
     *      }
     *  }
     *
     * @ApiDoc(
     *   section = "Treatment Medication",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"=true,
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Edit treatment medication name",
     *   statusCodes={200="Returned when successful", 428="Returned for invalid input"},
     *   input="json",
     *   output="json"
     * )
     * @param Request $request the request object
     * @param int $treatmentMedicationId
     * @return JsonResponse
     * @Route("/{treatmentMedicationId}")
     * @Method("PUT")
     */
    function edit(Request $request, $treatmentMedicationId)
    {
        return $this->get('app.treatment.medication')->edit($request, $treatmentMedicationId);
    }

    /**
     * Delete treatment medication.
     *
     * ### Request body ###
     *
     *     none
     *
     * ### Response body ###
     *
     *  {
     *      "result": {
     *          "id": 3,
     *          "name": "Codichol"
     *      }
     *  }
     *
     *
     * @ApiDoc(
     *   section = "Treatment Medication",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"=true,
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Delete treatment medication",
     *   statusCodes={200="Returned when successful", 428="Returned for invalid input"},
     *   input="json",
     *   output="json"
     * )
     * @param Request $request the request object
     * @param int $treatmentMedicationId
     * @return JsonResponse
     * @Route("/{treatmentMedicationId}")
     * @Method("DELETE")
     */
    function delete(Request $request, $treatmentMedicationId)
    {
        return $this->get('app.treatment.medication')->delete($request, $treatmentMedicationId);
    }
}