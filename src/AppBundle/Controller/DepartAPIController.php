<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Client;
use AppBundle\Enumerator\RequestType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/api/v1/departs")
 */
class DepartAPIController extends APIController {

  const REQUEST_TYPE  = RequestType::DECLARE_DEPART;
  const MESSAGE_CLASS = RequestType::DECLARE_DEPART_ENTITY;

  /**
   *
   * Get a DeclareBirth, found by it's ID.
   *
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareDepartRepository")
   * @Method("GET")
   */
  public function getDepartById(Request $request,$Id)
  {
    return new JsonResponse("ok", 200);
  }

  /**
   * @var Client
   */
  private $user;
  /**
   *
   * Get a list of DeclareDeparts with a given state:{OPEN, CLOSED, DECLINED}.
   *
   *
   * @Route("/status")
   * @Method("GET")
   */
  public function getDepartByState(Request $request)
  {
    return new JsonResponse("ok", 200);
  }


  /**
   *
   * Create a DeclareDepart Request.
   *
   * @Route("")
   * @Method("POST")
   */
  public function createDepart(Request $request)
  {
    return new JsonResponse("ok", 200);
  }


  /**
   *
   * Update existing DeclareDepart Request.
   *
   * @Route("")
   * @Method("PUT")
   */
  public function editDepart(Request $request)
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