<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
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
class HealthAPIController extends APIController implements HealthAPIControllerInterface {


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
   * @param String $ubn
   * @return JsonResponse
   * @Route("/{ubn}/health")
   * @Method("GET")
   */
  public function getHealthByLocation(Request $request, $ubn) {

    $client = $this->getAuthenticatedUser($request);
    //TODO if ubn is in header change route and use $location = $this->getSelectedLocation($request);
    $location = $this->getLocationByUbn($client, $ubn);
    $em = $this->getDoctrine()->getEntityManager();

    if($location == null) {
      $errorMessage = "No Location found with ubn: " . $ubn;
      return new JsonResponse(array('code'=>428, "message" => $errorMessage), 428);
    }
    $outputArray = HealthOutput::create($em, $client, $location);

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $outputArray), 200);
  }


}