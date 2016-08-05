<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\FormInput\LocationHealth;
use AppBundle\Output\HealthOutput;
use AppBundle\Validation\AdminValidator;
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

    $admin = $this->getAuthenticatedEmployee($request);
    $adminValidator = new AdminValidator($admin, AccessLevelType::SUPER_ADMIN);
    if(!$adminValidator->getIsAccessGranted()) { //validate if user is at least a SUPER_ADMIN
      return $adminValidator->createJsonErrorResponse();
    }

    $em = $this->getDoctrine()->getEntityManager();
    $location = $em->getRepository(Location::class)->findByUbn($ubn);

    if($location == null) {
      $errorMessage = "No Location found with ubn: " . $ubn;
      return new JsonResponse(array('code'=>428, "message" => $errorMessage), 428);
    }
    $outputArray = HealthOutput::create($em, $location);

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $outputArray), 200);
  }

  /**
   *
   * Edit Health statuses of the given ubn.
   *
   * @param Request $request the request object
   * @param String $ubn
   * @return JsonResponse
   * @Route("/{ubn}/health")
   * @Method("PUT")
   */
  public function updateHealthStatus(Request $request, $ubn) {

    $admin = $this->getAuthenticatedEmployee($request);
    $adminValidator = new AdminValidator($admin, AccessLevelType::SUPER_ADMIN);
    if(!$adminValidator->getIsAccessGranted()) { //validate if user is at least a SUPER_ADMIN
      return $adminValidator->createJsonErrorResponse();
    }

    $em = $this->getDoctrine()->getEntityManager();
    $location = $em->getRepository(Location::class)->findByUbn($ubn);

    if($location == null) {
      $errorMessage = "No Location found with ubn: " . $ubn;
      return new JsonResponse(array('code'=>428, "message" => $errorMessage), 428);
    }
    
    $content = $this->getContentAsArray($request);    
    $location = LocationHealth::update($em, $location, $content); //includes persisting changes
    
    $outputArray = HealthOutput::create($em, $location);

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $outputArray), 200);
    
  }
}