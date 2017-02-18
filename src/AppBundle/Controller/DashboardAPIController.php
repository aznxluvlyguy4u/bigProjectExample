<?php

namespace AppBundle\Controller;

use AppBundle\Component\Count;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareBaseRepository;
use AppBundle\Enumerator\RequestType;
use AppBundle\Output\CompanyProfileOutput;
use AppBundle\Output\DashboardOutput;
use AppBundle\Output\LoginOutput;
use AppBundle\Util\Validator;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/api/v1/dashboard")
 */
class DashboardAPIController extends APIController {

  /**
   *
   * Get data for dashboard view
   *
   * @Route("")
   * @Method("GET")
   */
  public function getDashBoard(Request $request) {
    $client = $this->getAuthenticatedUser($request);
    $location = $this->getSelectedLocation($request);

    if($client == null) { return Validator::createJsonResponse('Client cannot be null', 428); }
    if($location == null) { return Validator::createJsonResponse('Location cannot be null', 428); }
    
    $errorMessageForDateIsNull = "";
    
    /** @var DeclareBaseRepository $declareBaseRepository */
    $declareBaseRepository = $this->getDoctrine()->getRepository(Constant::DECLARE_BASE_REPOSITORY);
    $declarationLogDate = $declareBaseRepository->getLatestLogDatesForDashboardDeclarationsPerLocation($location, $errorMessageForDateIsNull);

    $em = $this->getDoctrine()->getManager();
    
    $outputArray = DashboardOutput::create($em, $client, $declarationLogDate, $location);

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $outputArray), 200);
  }

}