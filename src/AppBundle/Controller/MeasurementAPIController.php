<?php

namespace AppBundle\Controller;

use AppBundle\Constant\JsonInputConstant;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use AppBundle\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/api/v1/measurements")
 */
class MeasurementAPIController
{

  /**
 * Temporarily endpoint for decluttering error log
 *
 * @param Request $request the request object
 * @param $ulnNumber
 * @return JsonResponse
 * @Route("/{ulnNumber}/exteriors/kinds")
 * @Method("GET")
 */
  public function temporarilyMeasurementExteriorEndpoint(Request $request, $ulnNumber) {
    return new JsonResponse([JsonInputConstant::RESULT => array()], 200);
  }

  /**
   * Temporarily endpoint for decluttering error log
   *
   * @param Request $request the request object
   * @param $ulnNumber
   * @return JsonResponse
   * @Route("/{ulnNumber}/exteriors/inspectors")
   * @Method("GET")
   */
  public function temporarilyMeasurementInspectorEndpoint(Request $request, $ulnNumber) {
    return new JsonResponse([JsonInputConstant::RESULT => array()], 200);
  }
}