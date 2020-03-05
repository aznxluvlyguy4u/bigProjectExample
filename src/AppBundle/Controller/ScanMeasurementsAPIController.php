<?php


namespace AppBundle\Controller;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Service\ScanMeasurementsService;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/api/v1/scan-measurements")
 */
class ScanMeasurementsAPIController extends APIController implements ScanMeasurementsAPIControllerInterface
{
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
     * @Route("/{animalId}", requirements={"animalId"="\d+"})
     * @Method("GET")
     * @throws \Exception
     */
    function getScanMeasurements(Request $request, $animalId)
    {
        return $this->get(ScanMeasurementsService::class)->getScanMeasurements($request, $animalId);
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
     * @Route("/{animalId}", requirements={"animalId"="\d+"})
     * @Method("PUT")
     * @throws \Exception
     */
    function modifyScanMeasurements(Request $request, $animalId)
    {
        return $this->get(ScanMeasurementsService::class)->modifyScanMeasurements($request, $animalId);
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
     * @Route("/{animalId}", requirements={"animalId"="\d+"})
     * @Method("DELETE")
     * @throws \Exception
     */
    function deleteScanMeasurements(Request $request, $animalId)
    {
        return $this->get(ScanMeasurementsService::class)->deleteScanMeasurements($request, $animalId);
    }
}
