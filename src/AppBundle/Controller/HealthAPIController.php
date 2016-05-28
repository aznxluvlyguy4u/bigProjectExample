<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use AppBundle\Output\HealthOutput;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/api/v1/health")
 */
class HealthAPIController extends APIController {


  /**
   *
   * Get Health status found by location
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
   *   parameters={
   *      {
   *        "name"="ubn",
   *        "dataType"="string",
   *        "required"=false,
   *        "description"="the ubn number of a location belonging to the client",
   *        "format"="?ubn=number"
   *      },
   *   },
   *   resource = true,
   *   description = "Retrieve a Health status for the given ubn",
   *   output = "AppBundle\Entity\Health"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("GET")
   */
  public function getHealthByLocation(Request $request) {

    $client = $this->getAuthenticatedUser($request);

    if($request->query->has(Constant::UBN_NAMESPACE)) {
      $ubn = $request->query->get(Constant::UBN_NAMESPACE);
      $location = $this->getLocationByUbn($client, $ubn);
    } else {
      //by default get the first location in the first company
      $location = $client->getCompanies()->get(0)->getLocations()->get(0);
    }

    $outputArray = HealthOutput::create($client, $location);

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $outputArray), 200);
  }

}