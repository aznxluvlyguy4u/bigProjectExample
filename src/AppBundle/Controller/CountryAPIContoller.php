<?php

namespace AppBundle\Controller;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/api/v1")
 */
class LandCodeAPIContoller extends APIController {

  /**
   * @var ArrayCollection
   */
  private $countryCodes;

  public function __construct() {
    $this->countryCodes  = $this->getDoctrine()->getRepository('AppBundle:Country')->findAll();
  }

  /**
   * @Route("/country/status")
   * @Method("GET")
   */
  public function getCountryCodes(Request $request) {

    $result = $this->isTokenValid($request);

    if($result instanceof JsonResponse){
      return $result;
    }

    return new JsonResponse($this->countryCodes, 200);
  }
}