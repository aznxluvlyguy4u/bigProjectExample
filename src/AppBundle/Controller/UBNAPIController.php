<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/api/v1/ubns")
 */
class UBNAPIController extends APIController implements UBNAPIControllerInterface
{

  /**
   * @param Request $request the request object
   * @return Response
   * @Route("")
   * @Method("POST")
   */
  function getUBNDetails(Request $request)
  {
    // TODO: Implement getUBNDetails() method.
  }
}