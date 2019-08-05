<?php

namespace AppBundle\Controller;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Service\AdminService;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/api/v1/admins")
 */
class AdminAPIController extends APIController implements AdminAPIControllerInterface
{

  /**
   * Retrieve a list of all Admins
   *
   * @ApiDoc(
   *   section = "Admins",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the admin that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Retrieve a list of all Admins"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("GET")
   */
  public function getAdmins(Request $request)
  {
      return $this->get(AdminService::class)->getAdmins($request);
  }


  /**
   *
   * Create new Admin
   *
   * @ApiDoc(
   *   section = "Admins",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the admin that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Create new Admin"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("POST")
   */
    public function createAdmin(Request $request)
    {
        return $this->get(AdminService::class)->createAdmin($request);
    }


  /**
   *
   * Edit Admins.
   *
   * @ApiDoc(
   *   section = "Admins",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the admin that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Edit Admins"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("PUT")
   */
    public function editAdmin(Request $request)
    {
        return $this->get(AdminService::class)->editAdmin($request);
    }


  /**
   * Deactivate a list of Admins
   *
   * @ApiDoc(
   *   section = "Admins",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the admin that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Deactivate a list of Admins"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-deactivate")
   * @Method("PUT")
   */
  public function deactivateAdmin(Request $request)
  {
      return $this->get(AdminService::class)->deactivateAdmin($request);
  }


  /**
   *
   * Get ghost accesstoken token
   *
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/ghost")
   * @Method("POST")
   */
  public function getTemporaryGhostToken(Request $request)
  {
      return $this->get(AdminService::class)->getTemporaryGhostToken($request);
  }

  /**
   *
   * Verify ghost token.
   *
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/verify-ghost-token")
   * @Method("PUT")
   */
  public function verifyGhostToken(Request $request)
  {
      return $this->get(AdminService::class)->verifyGhostToken($request);
  }


  /**
   * Retrieve a list of all Admin access level types
   *
   * @ApiDoc(
   *   section = "Admins",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the admin that is registered with the API"
   *     }
   *   },
   *
   *   resource = true,
   *   description = "Retrieve a list of all Admin access level types"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-access-levels")
   * @Method("GET")
   */
  public function getAccessLevelTypes(Request $request)
  {
      return $this->get(AdminService::class)->getAccessLevelTypes($request);
  }
}