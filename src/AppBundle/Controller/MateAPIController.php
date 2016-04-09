<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Client;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;

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
   * @Route("/mates/status")
   * @Method("GET")
   */
  public function getMateByState(Request $request)
  {
    $result = $this->isTokenValid($request);

    if($result instanceof JsonResponse){
      return $result;
    }


    return new JsonResponse("ok", 200);
  }

  /**
   *
   * Get a DeclareMate, found by it's ID.
   *
   * @Route("/mates/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\MateRepository")
   * @Method("GET")
   */
  public function getMateById(Request $request,$Id)
  {
    $result = $this->isTokenValid($request);

    if($result instanceof JsonResponse){
      return $result;
    }


    return new JsonResponse("ok", 200);
  }

  /**
   *
   * Create a DeclareMate Request.
   *
   * @Route("/mates")
   * @Method("POST")
   */
  public function postNewMate(Request $request)
  {
    $result = $this->isTokenValid($request);

    if($result instanceof JsonResponse){
      return $result;
    }


    return new JsonResponse("ok", 200);
  }

  /**
   *
   * Debug endpoint
   *
   * @Route("/mates/debug")
   * @Method("GET")
   */
  public function debugAPI(Request $request)
  {
    $result = $this->isTokenValid($request);

    if($result instanceof JsonResponse){
      return $result;
    }


    return new JsonResponse("ok", 200);
  }
}