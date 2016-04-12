<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Country;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use AppBundle\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/api/v1")
 */
class CountryAPIController extends APIController {

  /**
   * Retrieve a list of Country codes and corresponding full Country name.
   *
   * @ApiDoc(
   *   resource = true,
   *   description = "Retrieve a list of countries with ISO 3166-1 two letter codes",
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

    $result = $this->isTokenValid($request);

    if($result instanceof JsonResponse){
      return $result;
    }

    if(!$request->query->has('continent')) {
      $countries = $this->getDoctrine()->getRepository('AppBundle:Country')->findAll();
    } else {
      $continent = ucfirst($request->query->get('continent'));
      $countries = $this->getDoctrine()->getRepository('AppBundle:Country')->findBy(array('continent' => $continent));
    }

    $countries = $this->serializeToJSON(array("countries" => $countries));

    return new Response($countries);
  }
}