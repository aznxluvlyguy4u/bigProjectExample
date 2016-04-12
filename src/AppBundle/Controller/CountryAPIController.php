<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Country;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/api/v1")
 */
class CountryAPIController extends APIController {

  /**
   * Retrieve a list of Country codes and corresponding full Country name.
   *
   * @ApiDoc(
   *   resource = true,
   *   description = "Retrieve a DeclareArrival by given ID",
   *   output = "AppBundle\Entity\Country"
   * )
   *
   *
   * @return Response
   *
   *
   * @Route("/countries")
   * @Method("GET")
   */
  public function getCountryCodes(Request $request) {
    $countries = $this->getDoctrine()->getRepository('AppBundle:Country')->findAll();
    $countries = $this->serializeToJSON(array("countries" => $countries));

    return new Response($countries);
  }
}