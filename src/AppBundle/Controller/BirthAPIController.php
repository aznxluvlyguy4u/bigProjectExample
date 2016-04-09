<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Client;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;


class BirthAPIController extends APIController {

  const REQUEST_TYPE = 'DECLARE_BIRTH';

  /**
   * @var Client
   */
  private $user;
  /**
   *
   * Get a list of DeclareBirths with a given state:{OPEN, CLOSED, DECLINED}.
   *
   *
   * @Route("/births/status")
   * @Method("GET")
   */
  public function getBirthByState(Request $request)
  {
    $result = $this->isTokenValid($request);

    if($result instanceof JsonResponse){
      return $result;
    }


    return new JsonResponse("ok", 200);
  }

  /**
   *
   * Get a DeclareBirth, found by it's ID.
   *
   * @Route("/births/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\BirthRepository")
   * @Method("GET")
   */
  public function getBirthById(Request $request,$Id)
  {
    $result = $this->isTokenValid($request);

    if($result instanceof JsonResponse){
      return $result;
    }


    return new JsonResponse("ok", 200);
  }

  /**
   *
   * Create a DeclareBirth Request.
   *
   * @Route("/births")
   * @Method("POST")
   */
  public function postNewBirth(Request $request)
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
   * @Route("/births/debug")
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