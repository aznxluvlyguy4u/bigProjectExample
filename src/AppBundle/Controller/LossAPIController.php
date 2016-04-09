<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Client;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/api/v1")
 */
class LossAPIController extends APIController {

  const REQUEST_TYPE = 'DECLARE_BIRTH';

  /**
   * @var Client
   */
  private $user;
  /**
   *
   * Get a list of DeclareLosses with a given state:{OPEN, CLOSED, DECLINED} or all
   * losses when no state is given.
   *
   *
   * @Route("/losses/status")
   * @Method("GET")
   */
  public function getLossByState(Request $request)
  {
  /*  $result = $this->isTokenValid($request);

    if($result instanceof JsonResponse){
      return $result;
    }*/


    return new JsonResponse("ok", 200);
  }

  /**
   *
   * Get a DeclareLoss, found by it's ID.
   *
   * @Route("/losses/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareLossRepository")
   * @Method("GET")
   */
  public function getLossById(Request $request,$Id)
  {
    /*$result = $this->isTokenValid($request);

    if($result instanceof JsonResponse){
      return $result;
    }*/


    return new JsonResponse("ok", 200);
  }

  /**
   *
   * Create a DeclareLoss Request.
   *
   * @Route("/losses")
   * @Method("POST")
   */
  public function postNewLoss(Request $request)
  {
  /*  $result = $this->isTokenValid($request);

    if($result instanceof JsonResponse){
      return $result;
    }*/


    return new JsonResponse("ok", 200);
  }

  /**
   *
   * Debug endpoint
   *
   * @Route("/losses/debug")
   * @Method("GET")
   */
  public function debugAPI(Request $request)
  {
  /*  $result = $this->isTokenValid($request);

    if($result instanceof JsonResponse){
      return $result;
    }*/


    return new JsonResponse("ok", 200);
  }

}