<?php

namespace AppBundle\Controller;

use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/api/v1/components")
 */
class ComponentAPIController extends APIController {

  /**
   * Get data for menu bar at the top.
   *
   * @ApiDoc(
   *   section = "Components",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Get data for menu bar at the top."
   * )
   * @param Request $request
   * @return JsonResponse
   * @Route("/menu-bar")
   * @Method("GET")
   */
    public function getMenuBar(Request $request)
    {
        return $this->get('app.component')->getMenuBar($request);
    }

    /**
    * @param Request $request
    * @return JsonResponse
    * @Route("/admin-menu-bar")
    * @Method("GET")
    */
    public function getAdminMenuBar(Request $request)
    {
        return $this->get('app.component')->getAdminMenuBar($request);
    }
}