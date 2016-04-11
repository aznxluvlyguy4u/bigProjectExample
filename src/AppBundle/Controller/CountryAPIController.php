<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Country;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/api/v1")
 */
class CountryAPIController extends APIController {

  /**
   * @Route("/countries")
   * @Method("GET")
   */
  public function getCountryCodes(Request $request) {
    $countries = $this->getDoctrine()->getRepository('AppBundle:Country')->findAll();
    $countries = $this->serializeToJSON(array("countries" => $countries));

    return new Response($countries);
  }
}