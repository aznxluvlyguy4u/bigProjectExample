<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/api/v1/auth")
 */
class AuthAPIController extends APIController {

  /**
   * Register a user
   *
   * @ApiDoc(
   *   section = "Auth",
   *   requirements={
   *     {
   *       "name"="Authorization header",
   *       "dataType"="string",
   *       "requirement"="Base64 encoded",
   *       "description"="Basic Authentication header with a Base64 encoded secret, semicolon separated value, with delimiter"
   *     }
   *   },
   *   resource = true,
   *   description = "Register a user"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/signup")
   * @Method("POST")
   */
  public function signUpUser(Request $request)
  {
      return $this->get('app.security.auth')->signUpUser($request);
  }

  /**
   * Validate whether an accesstoken is valid or not.
   *
   * @ApiDoc(
   *   section = "Auth",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Validate whether an accesstoken is valid or not."
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/validate-token")
   * @Method("POST")
   */
  public function validateToken(Request $request)
  {
    return $this->get('app.security.auth')->isAccessTokenValid($request);
  }

  /**
   * Retrieve a valid access token.
   *
   * @ApiDoc(
   *   section = "Auth",
   *   requirements={
   *     {
   *       "name"="Authorization header",
   *       "dataType"="string",
   *       "requirement"="Base64 encoded",
   *       "format"="Authorization: Basic xxxxxxx==",
   *       "description"="Basic Authentication, Base64 encoded string with delimiter"
   *     }
   *   },
   *   resource = true,
   *   description = "Retrieve a valid access token for a registered and activated user"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/authorize")
   * @Method("GET")
   */
  public function authorizeUser(Request $request)
  {
      return $this->get('app.security.auth')->authorizeUser($request);
  }

  /**
   * Change password when already logged in.
   *
   * @ApiDoc(
   *   section = "Auth",
   *   requirements={
   *     {
   *       "name"="Authorization header",
   *       "dataType"="string",
   *       "requirement"="Base64 encoded",
   *       "description"="Basic Authentication header with a Base64 encoded secret, semicolon separated value, with delimiter"
   *     }
   *   },
   *   resource = true,
   *   description = "Reset login password"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/password-change")
   * @Method("PUT")
   */
  public function changePassword(Request $request)
  {
      return $this->get('app.security.auth')->changePassword($request);
  }

  /**
   * Reset password when not logged in.
   *
   * @ApiDoc(
   *   section = "Auth",
   *   requirements={
   *     {
   *       "name"="Authorization header",
   *       "dataType"="string",
   *       "requirement"="Base64 encoded",
   *       "description"="Basic Authentication header with a Base64 encoded secret, semicolon separated value, with delimiter"
   *     }
   *   },
   *   resource = true,
   *   description = "Reset login password"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/password-reset")
   * @Method("PUT")
   */
  public function resetPassword(Request $request)
  {
      return $this->get('app.security.auth')->resetPassword($request);
  }

  /**
   * Generate new passwords for new clients and store them.
   *
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/generate-passwords")
   * @Method("POST")
   *
   * @param Request $request
   */
  public function generatePasswordsForNewClients(Request $request)
  {
      return $this->get('app.security.auth')->generatePasswordsForNewClients($request);
  }


  /**
   * Validate whether a ubn in the header is valid or not.
   *
   * @ApiDoc(
   *   section = "Auth",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Validate whether a ubn in the header is valid or not."
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/validate-ubn")
   * @Method("GET")
   */
  public function validateUbnInHeader(Request $request)
  {
      return $this->get('app.security.auth')->validateUbnInHeader($request);
  }


    /**
     * Request password reset and get reset token by email.
     *
     * @ApiDoc(
     *   section = "Auth",
     *   requirements={
     *     {
     *       "name"="Authorization header",
     *       "dataType"="string",
     *       "requirement"="Base64 encoded",
     *       "description"="Basic Authentication header with a Base64 encoded secret, semicolon separated value, with delimiter"
     *     }
     *   },
     *   resource = true,
     *   description = "Request password reset and get reset token by email"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/password-reset-token")
     * @Method("POST")
     */
    public function passwordResetRequest(Request $request)
    {
        return $this->get('app.security.auth')->passwordResetRequest($request);
    }


    /**
     * Confirm password reset and get new password by email.
     *
     * @ApiDoc(
     *   section = "Auth",
     *   requirements={
     *     {
     *       "name"="Authorization header",
     *       "dataType"="string",
     *       "requirement"="Base64 encoded",
     *       "description"="Basic Authentication header with a Base64 encoded secret, semicolon separated value, with delimiter"
     *     }
     *   },
     *   resource = true,
     *   description = "Request password reset and get reset token by email"
     * )
     *
     * @param Request $request the request object
     * @return string
     * @Route("/password-reset-token/{resetToken}")
     * @Method("GET")
     */
    public function passwordResetConfirmation(Request $request, $resetToken)
    {
        return $this->get('app.security.auth')->passwordResetConfirmation($resetToken);
    }

}
