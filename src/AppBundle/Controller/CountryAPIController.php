<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use AppBundle\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/api/v1/countries")
 */
class CountryAPIController extends APIController implements CountryAPIControllerInterface
{

  /**
   * Retrieve a list of Country codes and corresponding full Country name
   *
   * @ApiDoc(
   *   section = "Countries",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   parameters={
   *      {
   *        "name"="continent",
   *        "dataType"="string",
   *        "required"=false,
   *        "description"=" Continents to filter on: { Asia, Africa, Antarctica, Australia, Europe, North-America, South-America }, note that some countries are transcontinental",
   *        "format"="?continent=continent-name"
   *      }
   *   },
   *   resource = true,
   *   description = "Retrieve a list of countries with ISO 3166-1 two letter codes and corresponding full country name"
   * )
   * @param Request $request the request object
   * @return Response
   * @Route("")
   * @Method("GET")
   */
  public function getCountryCodes(Request $request)
  {
      return $this->get('app.country')->getCountryCodes($request);
  }


  /**
   * @param Request $request the request object
   * @return Response
   * @Route("")
   * @Method("POST")
   */
  function getCountries(Request $request)
  {
      return $this->get('app.country')->getCountries($request);
  }

  
  /**
   * Get list of Dutch provinces with their full name and code.
   *
   * @ApiDoc(
   *   section = "Countries",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Get list of Dutch provinces with their full name and code"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/nl/provinces")
   * @Method("GET")
   */
  function getDutchProvinces(Request $request)
  {
      return $this->get('app.country')->getDutchProvinces($request);
  }
}