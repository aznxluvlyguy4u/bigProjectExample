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
   * Retrieve a list of Country codes and corresponding full Country name, with default continent Europe.
   *
   * @ApiDoc(
   *   parameters={
   *      {
   *        "name"="continent",
   *        "dataType"="string",
   *        "required"=false,
   *        "description"=" Continent to filter on",
   *        "format"="?continent=continent-name"
   *      }
   *   },
   *   resource = true,
   *   description = "Retrieve a list of countries with ISO 3166-1 two letter codes, default continent is Europe",
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

    if(!$request->query->has('continent')) {
      $countries = $this->getDoctrine()->getRepository('AppBundle:Country')->findBy(array('continent' => 'Europe'));
    } else {
      $continent = ucfirst($request->query->get('continent'));
      if($continent == 'all'){
        $countries = $this->getDoctrine()->getRepository('AppBundle:Country')->findAll();
      } else {
        $countries = $this->getDoctrine()->getRepository('AppBundle:Country')->findBy(array('continent' => $continent));
      }
    }

    $countries = $this->serializeToJSON(array("countries" => $countries));

    return new Response($countries);
  }
}