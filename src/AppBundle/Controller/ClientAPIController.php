<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Client;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;

class ClientAPIController extends APIController {

  const REQUEST_TYPE = 'DECLARE_BIRTH';

  /**
   * @var Client
   */
  private $user;

  /**
   *
   * Get a list of Clients
   *
   *
   * @Route("/clients")
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
   * Get Client, found by it's ID
   *
   * @Route("/clients/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\ClientRepository")
   * @Method("GET")
   */
  public function getClientById(Request $request, $Id) {
    $result = $this->isTokenValid($request);

    if ($result instanceof JsonResponse) {
      return $result;
    }


    return new JsonResponse("ok", 200);
  }

  /**
   *
   * Create a Client
   *
   * @Route("/clients")
   * @Method("POST")
   */
  public function postNewClient(Request $request) {
    $result = $this->isTokenValid($request);

    if ($result instanceof JsonResponse) {
      return $result;
    }


    return new JsonResponse("ok", 200);
  }

  /**
   *
   * Debug endpoint
   *
   * @Route("/clients/debug")
   * @Method("GET")
   */
  public function debugAPI(Request $request) {
    $result = $this->isTokenValid($request);

    if ($result instanceof JsonResponse) {
      return $result;
    }


    return new JsonResponse("ok", 200);
  }
}