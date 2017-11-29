<?php

namespace AppBundle\Controller;

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
  public function getDashBoard(Request $request)
  {
      return $this->get('app.dashboard')->getDashBoard($request);
  }

}