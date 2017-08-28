<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/api/v1/contacts")
 */
class ContactAPIController extends APIController implements ContactAPIControllerInterface {

  /**
   *
   * Create a Client
   *
   * @Route("")
   * @Method("POST")
   */
  public function postContactEmail(Request $request)
  {
      return $this->get('app.contact')->postContactEmail($request);
  }



}