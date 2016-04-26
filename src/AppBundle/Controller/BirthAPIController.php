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
 * @Route("/api/v1/births")
 */
class BirthAPIController extends APIController {

  const REQUEST_TYPE  = RequestType::DECLARE_BIRTH;
  const MESSAGE_CLASS = RequestType::DECLARE_BIRTH_ENTITY;

  /**
   * @var Client
   */
  private $user;
  /**
   *
   * Get a list of DeclareBirths with a given state:{OPEN, CLOSED, DECLINED}.
   *
   *
   * @Route("/status")
   * @Method("GET")
   */
  public function getBirthByState(Request $request)
  {
    /*$result = $this->isTokenValid($request);

    if($result instanceof JsonResponse){
      return $result;
    }*/


    return new JsonResponse("ok", 200);
  }

  /**
   *
   * Get a DeclareBirth, found by it's ID.
   *
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareBirthRepository")
   * @Method("GET")
   */
  public function getBirthById(Request $request,$Id)
  {
   /* $result = $this->isTokenValid($request);

    if($result instanceof JsonResponse){
      return $result;
    }*/


    return new JsonResponse("ok", 200);
  }

  /**
   *
   * Create a DeclareBirth Request.
   *
   * @Route("")
   * @Method("POST")
   */
  public function postNewBirth(Request $request)
  {
    /*$result = $this->isTokenValid($request);

    if($result instanceof JsonResponse){
      return $result;
    }*/


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
   /* $result = $this->isTokenValid($request);

    if($result instanceof JsonResponse){
      return $result;
    }*/


    return new JsonResponse("ok", 200);
  }
}