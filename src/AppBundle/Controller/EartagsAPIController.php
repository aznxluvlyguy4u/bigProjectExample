<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Client;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;

class EartagsAPIController extends APIController {

  const REQUEST_TYPE = 'DECLARE_BIRTH';

  /**
   * @var Client
   */
  private $user;
  /**
   *
   * Get a list of DeclareEartags with a given state:{OPEN, CLOSED, DECLINED}.
   *
   *
   * @Route("/eartags/status")
   * @Method("GET")
   */
  public function getEartagByState(Request $request)
  {
    $result = $this->isTokenValid($request);

    if($result instanceof JsonResponse){
      return $result;
    }


    return new JsonResponse("ok", 200);
  }

  /**
   *
   * Get a DeclareEartag, found by it's ID.
   *
   * @Route("/eartags/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\EartagRepository")
   * @Method("GET")
   */
  public function getEartagById(Request $request,$Id)
  {
    $result = $this->isTokenValid($request);

    if($result instanceof JsonResponse){
      return $result;
    }


    return new JsonResponse("ok", 200);
  }

  /**
   *
   * Create a DeclareEartag Request.
   *
   * @Route("/eartags")
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
   * @Route("/eartags/debug")
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