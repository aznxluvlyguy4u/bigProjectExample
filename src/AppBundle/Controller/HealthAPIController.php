<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use AppBundle\Enumerator\HealthStatus;
use AppBundle\Output\HealthOutput;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/api/v1/ubns")
 */
class HealthAPIController extends APIController {


  /**
   *
   * Get Health status found by ubn
   *
   * @ApiDoc(
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Retrieve a Health status for the given ubn",
   *   output = "AppBundle\Entity\HealthOutput"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/{ubn}/health")
   * @Method("GET")
   */
  public function getHealthByLocation(Request $request, $ubn) {

    $client = $this->getAuthenticatedUser($request);
    $location = $this->getLocationByUbn($client, $ubn);

    if($location == null) {
      $errorMessage = "No Location found with ubn: " . $ubn;
      return new JsonResponse(array('code'=>428, "message" => $errorMessage), 428);
    }
    $outputArray = HealthOutput::create($client, $location);

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $outputArray), 200);
  }

  /**
   *
   * @ApiDoc(
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Update health status of Location and Animals on that Location after a successful Arrival or Import response",
   *   output = "AppBundle\Entity\HealthOutput"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/{ubn}/health")
   * @Method("PUT")
   */ //TODO Phase 2+ A LOT of conditions need to be added to checked for
  public function updateHealthStatusByUbn(Request $request, $ubn)
  {
    $client = $this->getAuthenticatedUser($request);
    $location = $this->getLocationByUbn($client, $ubn);

    if($location == null) {
      $errorMessage = "No Location found with ubn: " . $ubn;
      return new JsonResponse(array('code'=>428, "message" => $errorMessage), 428);
    }

    $content = $this->getContentAsArray($request);

    /*
    {
      "animal_healths": [
        {
          "maedi_visna_status": "HEALTH_LEVEL_1",
          "scrapie_status": "HEALTH_LEVEL_1"
        },
        {
          "maedi_visna_status": "",
          "scrapie_status": ""
        }
      ]
    }
    */

    $this->updateHealthStatusOfLocationByGivenAnimalHealths($location, $content);
    $outputArray = HealthOutput::create($client, $location);

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $outputArray), 200);
  }

  

}