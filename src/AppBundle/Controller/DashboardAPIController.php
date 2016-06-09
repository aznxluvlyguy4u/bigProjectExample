<?php

namespace AppBundle\Controller;

use AppBundle\Component\Count;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use AppBundle\Enumerator\RequestType;
use AppBundle\Output\CompanyProfileOutput;
use AppBundle\Output\DashboardOutput;
use AppBundle\Output\LoginOutput;
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

    $declarationLogDate = $this->getDoctrine()->getRepository(Constant::DECLARE_BASE_REPOSITORY)->getLatestLogDatesForDashboardDeclarations($client);

    $outputArray = DashboardOutput::create($client, $declarationLogDate);

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $outputArray), 200);
  }

}