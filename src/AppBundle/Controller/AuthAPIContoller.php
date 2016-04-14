<?php
/**
 * Created by IntelliJ IDEA.
 * User: c0d3
 * Date: 14/04/16
 * Time: 00:17
 */

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Doctrine\Common\Collections\ArrayCollection;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/api/v1")
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
   *   resource = true,
   *   description = "Retrieve a valid access token",
   *   input = "AppBundle\Entity\Client",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   * @param Request $request the request object
   * @return JsonResponse

   *
   * @Route("/login")
   * @Method("GET")
   */
  public function loginUser(Request $request)
  {
    return new JsonResponse("OK", 200);
  }
}