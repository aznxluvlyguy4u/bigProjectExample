<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Client;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/api/v1/mates")
 */
class MateAPIController extends APIController {

  const REQUEST_TYPE = 'DECLARE_BIRTH';

  /**
   * @var Client
   */
  private $user;
  /**
   *
   * Get a list of DeclareMates with a given state:{OPEN, CLOSED, DECLINED}.
   *
   *
   * @Route("/status")
   * @Method("GET")
   */
  public function getMateByState(Request $request)
  {
    return new JsonResponse("ok", 200);
  }

  /**
   *
   * Get a DeclareMate, found by it's ID.
   *
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareMateRepository")
   * @Method("GET")
   */
  public function getMateById(Request $request,$Id)
  {
    return new JsonResponse("ok", 200);
  }

  /**
   *
   * Create a DeclareMate Request.
   *
   * @Route("")
   * @Method("POST")
   */
  public function postNewMate(Request $request)
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