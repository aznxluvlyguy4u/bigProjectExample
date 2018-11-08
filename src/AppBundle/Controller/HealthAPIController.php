<?php

namespace AppBundle\Controller;

use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

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
   * @param string $ubn
   * @return JsonResponse
   * @Route("/ubn/{ubn}")
   * @Method("GET")
   */
  public function getHealthByLocation(Request $request, $ubn)
  {
      return $this->get('app.health.location')->getHealthByLocation($request, $ubn);
  }

  /**
   *
   * Edit Health statuses of the given ubn.
   *
   * @param Request $request the request object
   * @param string $ubn
   * @return JsonResponse
   * @Route("/ubn/{ubn}")
   * @Method("PUT")
   */
  public function updateHealthStatus(Request $request, $ubn)
  {
      return $this->get('app.health.location')->updateHealthStatus($request, $ubn);
  }

    /**
     * @param Request $request the request object
     * @param string $companyId
     * @return JsonResponse
     * @Route("/company/{companyId}")
     * @Method("GET")
     */
    public function getHealthByCompany(Request $request, $companyId)
    {
        return $this->get('app.health.location')->getHealthByCompany($request, $companyId);
    }
}