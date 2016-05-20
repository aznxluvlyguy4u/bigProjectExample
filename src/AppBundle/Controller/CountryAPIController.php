<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Country;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Enumerator\RequestType;

/**
 * @Route("/api/v1/countries")
 */
class CountryAPIController extends APIController implements CountryAPIControllerInterface
{

  /**
   * Retrieve a list of Country codes and corresponding full Country name
   *
   * @ApiDoc(
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
   *   description = "Retrieve a list of countries with ISO 3166-1 two letter codes and corresponding full country name",
   *   output = "AppBundle\Entity\Country"
   * )
   * @param Request $request the request object
   * @return Response
   * @Route("")
   * @Method("GET")
   */
  public function getCountryCodes(Request $request) {
    if (!$request->query->has(Constant::CONTINENT_NAMESPACE)) {
      $countries = $this->getDoctrine()
        ->getRepository(Constant::COUNTRY_REPOSITORY)
        ->findAll();
    }
    else {
      $continent = ucfirst($request->query->get(Constant::CONTINENT_NAMESPACE));
      if ($continent == Constant::ALL_NAMESPACE) {
        $countries = $this->getDoctrine()
          ->getRepository(Constant::COUNTRY_REPOSITORY)
          ->findAll();
      }
      else {
        $countries = $this->getDoctrine()
          ->getRepository(Constant::COUNTRY_REPOSITORY)
          ->findBy(array (Constant::CONTINENT_NAMESPACE => $continent));
      }
    }

    $countries = $this->getSerializer()
      ->serializeToJSON(array (Constant::RESULT_NAMESPACE => $countries));

    return new JsonResponse(array("result"=>$countries), 200);
  }

  /**
   * @param Request $request the request object
   * @return Response
   * @Route("")
   * @Method("POST")
   */
  function getCountries(Request $request)
  {
    //Get content to array
    $content = $this->getContentAsArray($request);

    //Convert the array into an object and add the mandatory values retrieved from the database
    $retrieveEartagsRequest = $this->buildMessageObject(RequestType::RETRIEVE_COUNTRIES_ENTITY, $content, $this->getAuthenticatedUser($request));

    //First Persist object to Database, before sending it to the queue
    $this->persist($retrieveEartagsRequest, RequestType::RETRIEVE_COUNTRIES_ENTITY);

    //Send it to the queue and persist/update any changed state to the database
    $this->sendMessageObjectToQueue($retrieveEartagsRequest, RequestType::RETRIEVE_COUNTRIES_ENTITY, RequestType::RETRIEVE_COUNTRIES);

    return new JsonResponse($retrieveEartagsRequest, 200);

  }
}