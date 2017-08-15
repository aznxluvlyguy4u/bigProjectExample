<?php

namespace AppBundle\Controller;

use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Company;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\FormInput\LocationHealthEditor;
use AppBundle\Output\HealthOutput;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\AdminActionLogWriter;
use AppBundle\Validation\AdminValidator;
use AppBundle\Validation\HealthEditValidator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/api/v1/health")
 */
class HealthAPIController extends APIController implements HealthAPIControllerInterface {


  /**
   *
   * Get Health status found by ubn
   *
   * @ApiDoc(
   *   section = "Healths",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Retrieve a Health status for the given ubn"
   * )
   * @param Request $request the request object
   * @param String $ubn
   * @return JsonResponse
   * @Route("/ubn/{ubn}")
   * @Method("GET")
   */
  public function getHealthByLocation(Request $request, $ubn) {

    $admin = $this->getEmployee();
    $adminValidator = new AdminValidator($admin, AccessLevelType::SUPER_ADMIN);
    if(!$adminValidator->getIsAccessGranted()) { //validate if user is at least a SUPER_ADMIN
      return $adminValidator->createJsonErrorResponse();
    }

    $em = $this->getDoctrine()->getManager();
    $location = $em->getRepository(Location::class)->findOneByActiveUbn($ubn);

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
   * @Route("/ubn/{ubn}")
   * @Method("PUT")
   */
  public function updateHealthStatus(Request $request, $ubn) {

    $om = $this->getDoctrine()->getManager();

    //Admin validation
    $admin = $this->getEmployee();
    $adminValidator = new AdminValidator($admin, AccessLevelType::SUPER_ADMIN);
    if(!$adminValidator->getIsAccessGranted()) { //validate if user is at least a SUPER_ADMIN
      return $adminValidator->createJsonErrorResponse();
    }

    $em = $this->getDoctrine()->getManager();
    $location = $em->getRepository(Location::class)->findOneByActiveUbn($ubn);

    //UBN Validation
    if($location == null) {
      $errorMessage = "No Location found with ubn: " . $ubn;
      return new JsonResponse(array('code'=>428, "message" => $errorMessage), 428);
    }

    $content = $this->getContentAsArray($request);

    //Status and check date validation
    $healthEditValidator = new HealthEditValidator($em, $content);
    if(!$healthEditValidator->getIsValid()) {
      return $healthEditValidator->createJsonResponse();
    }

    $location = LocationHealthEditor::edit($em, $location, $content); //includes persisting changes
    
    $outputArray = HealthOutput::create($em, $location);
    $log = AdminActionLogWriter::updateHealthStatus($om, $admin, $location, $content);

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $outputArray), 200);
    
  }

    /**
     * @param Request $request the request object
     * @param String $companyId
     * @return JsonResponse
     * @Route("/company/{companyId}")
     * @Method("GET")
     */
    public function getHealthByCompany(Request $request, $companyId) {

        $admin = $this->getEmployee();
        $adminValidator = new AdminValidator($admin, AccessLevelType::SUPER_ADMIN);
        if(!$adminValidator->getIsAccessGranted()) { //validate if user is at least a SUPER_ADMIN
            return $adminValidator->createJsonErrorResponse();
        }

        /**
         * @var Company $company
         */
        $em = $this->getDoctrine()->getManager();
        $company = $em->getRepository(Company::class)->findOneByCompanyId($companyId);
        $outputArray = HealthOutput::createCompanyHealth($em, $company);

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $outputArray), 200);
    }
}