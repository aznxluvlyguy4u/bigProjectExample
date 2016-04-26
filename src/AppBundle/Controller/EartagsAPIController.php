<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Client;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/api/v1/eartags")
 */
class EartagsAPIController extends APIController {

  /**
   * @var Client
   */
  private $user;
  /**
   *
   * Get a list of DeclareEartags with a given state:{OPEN, CLOSED, DECLINED}.
   *
   *
   * @Route("/status")
   * @Method("GET")
   */
  public function getEartagByState(Request $request)
  {
    return new JsonResponse("ok", 200);
  }

  /**
   *
   * Get a DeclareEartag, found by it's ID.
   *
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareEartagRepository")
   * @Method("GET")
   */
  public function getEartagById(Request $request,$Id)
  {
    return new JsonResponse("ok", 200);
  }

  /**
   *
   * Create a DeclareEartag Request.
   *
   * @Route("")
   * @Method("POST")
   */
  public function postNewDeclareEartag(Request $request)
  {
    return new JsonResponse("ok", 200);
  }

  /**
   *
   * Debug endpoint
   *
   * @Route("/debug")
   * @Method("GET")
   */
  public function debugAPI(Request $request)
  {
    return new JsonResponse("ok", 200);
  }
}