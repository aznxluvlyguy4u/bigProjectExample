<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Client;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/api/v1/clients")
 */
class ClientAPIController extends APIController {

  /**
   * @var Client
   */
  private $user;


  /**
   *
   * Get Client, found by it's ID
   *
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\ClientRepository")
   * @Method("GET")
   */
  public function getClientById(Request $request, $Id) {
    return new JsonResponse("ok", 200);
  }

  /**
   *
   * Create a Client
   *
   * @Route("")
   * @Method("POST")
   */
  public function postNewClient(Request $request) {
    return new JsonResponse("ok", 200);
  }

  /**
   *
   * Debug endpoint
   *
   * @Route("/debug")
   * @Method("GET")
   */
  public function debugAPI(Request $request) {
    return new JsonResponse("ok", 200);
  }
}