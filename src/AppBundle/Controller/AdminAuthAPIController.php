<?php

namespace AppBundle\Controller;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Enumerator\DashboardType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/api/v1/admins/auth")
 */
class AdminAuthAPIController extends APIController {

  /**
   * Retrieve a valid access token.
   *
   *   {
   *     "name"="Authorization header",
   *     "dataType"="string",
   *     "requirement"="Base64 encoded",
   *     "format"="Authorization: Basic xxxxxxx==",
   *     "description"="Basic Authentication, Base64 encoded string with delimiter"
   *   }

   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/authorize")
   * @Method("GET")
   */
  public function authorizeUser(Request $request)
  {
      return $this->get('app.security.admin_auth')->authorizeUser($request);
  }


  /**
   * Reset password when not logged in.
   *
   * {
   *    "name"="Authorization header",
   *    "dataType"="string",
   *    "requirement"="Base64 encoded",
   *    "description"="Basic Authentication header with a Base64 encoded secret, semicolon separated value, with delimiter"
   * }
   *
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/password-reset")
   * @Method("PUT")
   */
  public function resetPassword(Request $request)
  {
      return $this->get('app.security.admin_auth')->passwordResetRequest($request, DashboardType::ADMIN);
  }

}
