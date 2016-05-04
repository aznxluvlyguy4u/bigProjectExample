<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use AppBundle\Entity\CompanyAddress;
use AppBundle\Entity\Employee;
use AppBundle\Entity\LocationAddress;
use AppBundle\Entity\Person;
use AppBundle\Entity\Location;
use AppBundle\Entity\Company;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Doctrine\Common\Collections\ArrayCollection;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/api/v1/auth")
 */
class AuthAPIContoller extends APIController {

  /**
   * Register a user
   *
   * @ApiDoc(
   *   requirements={
   *     {
   *       "name"="Authorization header",
   *       "dataType"="string",
   *       "requirement"="Base64 encoded",
   *       "description"="Basic Authentication header with a Base64 encoded secret, semicolon seperated value, with delimiter"
   *     }
   *   },
   *   resource = true,
   *   description = "Register a user",
   *   input = "AppBundle\Entity\Client",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/register")
   * @Method("POST")
   */
  public function registerUser(Request $request)
  {
    $credentials = $request->headers->get(Constant::AUTHORIZATION_HEADER_NAMESPACE);
    $credentials = str_replace('Basic ', '', $credentials);
    $credentials = base64_decode($credentials);

    list($username, $password) = explode(":", $credentials);

    $encoder = $this->get('security.password_encoder');

    /*
    {
        "ubn":"123",
        "email_address": "",
        "postal_code":"1234AB",
        "home_number":"12"
    }
    */

    //Get content to array
    $content = $this->getContentAsArray($request);

    $ubn = $content['ubn'];
    $emailAddress = $content['email_address'];
    $postalCode = $content['postal_code'];
    $homeNumber = $content['home_number'];

    $client = new Client();
    $client->setFirstName("Jane");
    $client->setLastName("Doe");
    $client->setRelationNumberKeeper("");
    $client->setEmailAddress($emailAddress);
    $client->setUsername($username);

    $encodedPassword = $encoder->encodePassword($client, $password);
    $client->setPassword($encodedPassword);

    $locationAddress = new LocationAddress();
    $locationAddress->setStreetName("Weiland");
    $locationAddress->setAddressNumber("1");
    $locationAddress->setAddressNumberSuffix("");
    $locationAddress->setCity("Groningen");
    $locationAddress->setCountry("Netherlands");
    $locationAddress->setPostalCode("1111ZZ");
    $locationAddress->setState("NH");

    $location = new Location();
    $location->setUbn($ubn);
    $location->setAddress($locationAddress);

    $companyAddress = new CompanyAddress();
    $companyAddress->setStreetName("Baxandall");
    $companyAddress->setAddressNumber($homeNumber);
    $companyAddress->setAddressNumberSuffix("A");
    $companyAddress->setCity("Groningen");
    $companyAddress->setCountry("Netherlands");
    $companyAddress->setPostalCode($postalCode);
    $companyAddress->setState("NH");

    $company = new Company();
    $company->setOwner($client);
    $company->addLocation($location);
    $company->setAddress($companyAddress);

    $client->addCompany($company);

    $client = $this->getDoctrine()->getRepository('AppBundle:Client')->persist($client);

    return new JsonResponse(array("access_token" => $client->getAccessToken()), 200);
  }

  /**
   * Validate whether an accesstoken is valid or not.
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
   *   resource = true,
   *   description = "Validate whether an accesstoken is valid or not.",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/validate-token")
   * @Method("GET")
   */
  public function validateToken(Request $request)
  {
    return $this->isAccessTokenValid($request);
  }

  /**
   * Retrieve a valid access token.
   *
   * @ApiDoc(
   *   requirements={
   *     {
   *       "name"="Authorization header",
   *       "dataType"="string",
   *       "requirement"="Base64 encoded",
   *       "format"="Authorization: Basic xxxxxxx==",
   *       "description"="Basic Authentication, Base64 encoded string with delimiter"
   *     }
   *   },
   *   resource = true,
   *   description = "Retrieve a valid access token for a registered and activated user",
   *   output = "AppBundle\Entity\Client"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/authorize")
   * @Method("GET")
   */
  public function authorizeUser(Request $request)
  {
    $credentials = $request->headers->get($this::AUTHORIZATION_HEADER_NAMESPACE);
    $credentials = str_replace('Basic ', '', $credentials);
    $credentials = base64_decode($credentials);

    list($username, $password) = explode(":", $credentials);
    $encoder = $this->get('security.password_encoder');

    $client = $this->getDoctrine()->getRepository('AppBundle:Client')->findOneBy(array("username"=>$username));

    if($encoder->isPasswordValid($client, $password)) {
      return new JsonResponse(array("access_token"=>$client->getAccessToken()), 200);
    }

    return new JsonResponse(array("errorCode" => 401, "errorMessage"=>"Unauthorized"), 401);

  }
}
