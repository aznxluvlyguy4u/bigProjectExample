<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Doctrine\Common\Collections\ArrayCollection;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/api/v1/auth")
 */
class AuthAPIContoller extends APIController {

  /**
   * Register new user, send invite link.
   *
   * @ApiDoc(
   *   resource = true,
   *   description = "Register a user, will send an invite link to given email address",
   *   input = "AppBundle\Entity\Client",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   * @param Request $request the request object
   * @return JsonResponse

   *
   * @Route("/register")
   * @Method("POST")
   */
  public function registerUser(Request $request)
  {

    return new JsonResponse("OK", 200);
  }

  /**
   * Retrieve a valid access token.
   *
   * @ApiDoc(
   *   parameters={
   *      {
   *        "name"="Authorization",
   *        "dataType"="string",
   *        "required"=true,
   *        "description"=" Basic Authentication header - Base64 encoded, concatenated key & secret, with delimiter",
   *        "format"="Authorization: Basic xxxxxxx=="
   *      }
   *   },
   *   resource = true,
   *   description = "Retrieve a valid access token for a registered and activated user",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   * @param Request $request the request object
   * @return JsonResponse

   *
   * @Route("/authorize")
   * @Method("GET")
   */
  public function authorizeUser(Request $request)
  {
    $this->loginUser($request);

    return new JsonResponse("OK", 200);
  }
}