<?php

namespace AppBundle\Controller;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Service\BirthMeasurementService;
use AppBundle\Service\ScanMeasurementsService;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/api/v1/measurements")
 */
class MeasurementAPIController extends APIController
    implements
    BirthMeasurementAPIControllerInterface,
    MeasurementAPIControllerInterface,
    ScanMeasurementsAPIControllerInterface
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
     *   parameters={
     *      {
     *        "name"="full_output",
     *        "dataType"="boolean",
     *        "required"=false,
     *        "description"="set to true to return all active exteriors of animals, it is false by default",
     *        "format"="?full_output=true"
     *      }
     *   },
     *   resource = true,
     *   description = "Update an exterior measurement for a specific ULN and measurementDate"
     * )
     *
     * @param Request $request the request object
     * @param string $ulnString
     * @return jsonResponse
     * @Route("/{ulnString}/exteriors")
     * @Method("POST")
     * @throws \Exception
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
     *   parameters={
     *      {
     *        "name"="full_output",
     *        "dataType"="boolean",
     *        "required"=false,
     *        "description"="set to true to return all active exteriors of animals, it is false by default",
     *        "format"="?full_output=true"
     *      }
     *   },
     *   resource = true,
     *   description = "Update or Deactivate an exterior measurement for a specific ULN and measurementDate"
     * )
     *
     * @param Request $request the request object
     * @param string $ulnString
     * @param string $measurementDateString
     * @return jsonResponse
     * @Route("/{ulnString}/exteriors/{measurementDateString}")
     * @Method("PUT")
     * @throws \Exception
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
     * @param string $ulnString
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
     * @param string $ulnString
     * @param string $measurementDateString
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


    /**
     *
     * Edit the birth measurements of the given animal.
     *
     * If the animal already contains a weight and tail length record, the values in those records is overwritten.
     * If those records do not exist yet, new records are created.
     * The values in the declareBirth records are also updated.
     *
     * The animal identifier is the animalId, because there currently are issues where due to bugs,
     * two animals with the same ULN might exists in the database.
     *
     * RequestBody example:
     *  {
            "birth_weight": 0.526,
            "tail_length": 5.22,
            "reset_measurement_date_using_date_of_birth": false
        }
     * To remove the birth weight or tail length from the animal, just remove the key from the request body.
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
     *   description = "Edit the birth measurements of the given animal."
     * )
     *
     * @param Request $request the request object
     * @param int $animalId
     * @return JsonResponse
     * @Route("/{animalId}/birth-measurements", requirements={"animalId"="\d+"})
     * @Method("PUT")
     * @throws \Exception
     */
    function editBirthMeasurements(Request $request, $animalId)
    {
        return $this->get(BirthMeasurementService::class)->editBirthMeasurements($request, $animalId);
    }



    /**
     *
     * Edit the scan measurements of the given animal.
     *
     * If the animal already has scan measurements, the values in those records are overwritten.
     * If those records do not exist yet, new records are created.
     *
     * The animal identifier is the animalId, because there currently are issues where due to bugs,
     * two animals with the same ULN might exists in the database.
     *
     * RequestBody example:
            {
                "measurement_date": "2017-08-09T00:00:00+0200",
                "fat1": 0.1,
                "fat2": 1.2,
                "fat3": 2.3,
                "muscle_thickness": "22.1",
                "scan_weight": "34.7",
                "inspector_id": 112
            }
     * All values except for the inspector_id are mandatory.
     *
     * fats: between 0 and 9, max 1 decimal
     * muscleThickness: between 10 and 50 mm, max 1 decimal
     * scanWeight: between 10 and 99, max 2 decimals
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
     *   description = "Edit the scan measurements of the given animal"
     * )
     *
     * @param Request $request the request object
     * @param int $animalId
     * @return JsonResponse
     * @Route("/{animalId}/scan-measurements", requirements={"animalId"="\d+"})
     * @Method("PUT")
     * @throws \Exception
     */
    function modifyScanMeasurements(Request $request, $animalId)
    {
        return $this->get(ScanMeasurementsService::class)->modifyScanMeasurements($request, $animalId);
    }

    /**
     *
     * Get the scan measurements of the given animal.
     *
     * Codes
     * 200: returns scan measurement
     * 404: no scan measurements exists
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
     *   description = "Get the scan measurements of the given animal"
     * )
     *
     * @param Request $request the request object
     * @param int $animalId
     * @return JsonResponse
     * @Route("/{animalId}/scan-measurements", requirements={"animalId"="\d+"})
     * @Method("GET")
     * @throws \Exception
     */
    function getScanMeasurements(Request $request, $animalId)
    {
        return $this->get(ScanMeasurementsService::class)->getScanMeasurements($request, $animalId);
    }

    /**
     *
     * Delete the scan measurements of the given animal.
     *
     * Codes
     * 204: no content, delete was successful
     * 404: no scan measurements exists
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
     *   description = "Delete the scan measurements of the given animal"
     * )
     *
     * @param Request $request the request object
     * @param int $animalId
     * @return JsonResponse
     * @Route("/{animalId}/scan-measurements", requirements={"animalId"="\d+"})
     * @Method("DELETE")
     * @throws \Exception
     */
    function deleteScanMeasurements(Request $request, $animalId)
    {
        return $this->get(ScanMeasurementsService::class)->deleteScanMeasurements($request, $animalId);
    }


}
