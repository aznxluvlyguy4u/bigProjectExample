<?php

namespace AppBundle\Controller;

use AppBundle\Component\MessageBuilderBase;
use AppBundle\Entity\Company;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationAddress;
use AppBundle\Entity\BillingAddress;
use AppBundle\Entity\CompanyAddress;
use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Neuter;
use AppBundle\Enumerator\MessageClass;
use AppBundle\Enumerator\RequestType;
use AppBundle\Enumerator\AnimalType;
use AppBundle\Service\EntityGetter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Doctrine\Common\Collections\ArrayCollection;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * @Route("/api/v1/arrivals")
 */
class ArrivalAPIController extends APIController
{
  const MESSAGE_CLASS = MessageClass::DeclareArrival;
  const REQUEST_TYPE = 'DECLARE_ARRIVAL';
  const STATE_NAMESPACE = 'state';
  const REQUEST_STATE_NAMESPACE = 'requestState';
  const DECLARE_ARRIVAL_REPOSITORY = 'AppBundle:DeclareArrival';
  const DECLARE_ARRIVAL_RESULT_NAMESPACE = "result";

  /**
   * Retrieve a DeclareArrival, found by it's ID.
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
   *   description = "Retrieve a DeclareArrival by given ID",
   *   output = "AppBundle\Entity\DeclareArrival"
   * )
   * @param Request $request the request object
   * @param int $Id Id of the DeclareArrival to be returned
   * @return JsonResponse
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareArrivalRepository")
   * @Method("GET")
   */
  public function getArrivalById(Request $request, $Id)
  {
    $arrival = $this->getDoctrine()->getRepository($this::DECLARE_ARRIVAL_REPOSITORY)->find($Id);
    return new JsonResponse($arrival, 200);
  }

  /**
   * Retrieve either a list of all DeclareArrivals or a sublist of DeclareArrivals with a given state-type:
   * {
   *    OPEN,
   *    FINISHED,
   *    FAILED,
   *    CANCELLED
   * }
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
   *        "name"="state",
   *        "dataType"="string",
   *        "required"=false,
   *        "description"=" DeclareArrivals to filter on",
   *        "format"="?state=state-type"
   *      }
   *   },
   *   resource = true,
   *   description = "Retrieve a a list of DeclareArrivals",
   *   output = "AppBundle\Entity\DeclareArrival"
   * )
   * @param Request $request the request object
   * @param string $state
   * @return JsonResponse
   * @Route("/status")
   * @Method("GET")
   */
  public function getArrivalByState(Request $request)
  {
    //Initialize default state to filter on declare arrivals
    $state = 'open';

    //No explicit filter given, thus use default state to filter on
    if(!$request->query->has($this::STATE_NAMESPACE)) {
      $declareArrivals = $this->getDoctrine()->getRepository($this::DECLARE_ARRIVAL_REPOSITORY)->findBy(array($this::REQUEST_STATE_NAMESPACE => $state));
    } else { //A state parameter was given, use custom filter
      $state = $request->query->get($this::STATE_NAMESPACE);
      $declareArrivals = $this->getDoctrine()->getRepository($this::DECLARE_ARRIVAL_REPOSITORY)->findBy(array($this::REQUEST_STATE_NAMESPACE => $state));
    }

    return new JsonResponse(array($this::DECLARE_ARRIVAL_RESULT_NAMESPACE => $declareArrivals), 200);
  }

  /**
   * Create a new DeclareArrival request
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
   *   description = "Post a DeclareArrival request",
   *   input = "AppBundle\Entity\DeclareArrival",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("POST")
   */
  public function postNewArrival(Request $request)
  {
    //Authentication
    $result = $this->isTokenValid($request);

    if($result instanceof JsonResponse) {
      return $result;
    } else {
      $user = $result;
    }

    //Convert front-end message into an array
    //Get content to array
    $content = $this->getContentAsArray($request);

    //Convert the array into an object and add the mandatory values retrieved from the database
    $messageObject = $this->buildMessageObject(MessageClass::DeclareArrival, $content, $user);


    //FIXME issue with persisting to the db not working because cascade is missing
    //First Persist object to Database, before sending it to the queue
//    $this->persist($messageObject, MessageClass::DeclareArrival);

    //Send it to the queue and persist/update any changed state to the database
    $this->sendMessageObjectToQueue($messageObject, MessageClass::DeclareArrival, RequestType::DECLARE_ARRIVAL);

    return new JsonResponse($messageObject, 200);
  }

  /**
   *
   * Debug endpoint
   *
   * @Route("/test/debug")
   * @Method("GET")
   */
  public function debugAPI()
  {
    $entityManager = $this->getDoctrine()->getEntityManager();

    $mockClient = new Client();
    $mockClient->setFirstName("Bart");
    $mockClient->setLastName("de Boer");
    $mockClient->setEmailAddress("bart@deboer.com");
    $mockClient->setRelationNumberKeeper("77777444");

    $entityManager->persist($mockClient, "Person" );

    //Setup mock message as JSON
    $content = '{
   "import_animal": true,
   "ubn_previous_owner": "123456",
   "animal": {
    "pedigree_country_code": "NL",
     "pedigree_number": "12345",
     "uln_country_code": "UK",
     "uln_number": "333333333"
   },
   "location" : {
     "ubn" : "0031079"
   },
   "arrival_date": "2016-04-04T12:55:43-05:00"

  }';

    //Convert mock message into an array
    $content = new ArrayCollection(json_decode($content, true));

    $messageObject = $this->buildMessageObject(MessageClass::DeclareArrival, $content, $mockClient);

    //First Persist object to Database, before sending it to the queue
    $this->persist($messageObject, MessageClass::DeclareArrival);

    //Send it to the queue and persist/update any changed state to the database
    $this->sendMessageObjectToQueue($messageObject, MessageClass::DeclareArrival, RequestType::DECLARE_ARRIVAL);

    return new JsonResponse(array('status' => "OK",
        MessageClass::DeclareArrival => $messageObject,
        'sent to queue with request type' => RequestType::DECLARE_ARRIVAL), 200);

//    return new JsonResponse("OK", 200);
  }

  /**
   *
   * Temporary route for testing code
   *
   * @Route("/test/code")
   * @Method("GET")
   *
   */
  public function testingStuff(Request $request)
  {
    //Authentication
    $result = $this->isTokenValid($request);

    if($result instanceof JsonResponse) {
      return $result;
    } else {
      $user = $result;
    }

    return new JsonResponse(array('status' => "OK",
        'User' => $user), 200);

//    return new JsonResponse("OK", 200);
  }

  /**
   *
   * Temporary route for testing code
   *
   * @Route("/test/setup")
   * @Method("GET")
   *
   */
  public function setupTest(Request $request)
  {
    $entityManager = $this->getDoctrine()->getEntityManager();
    $encoder = $this->get('security.password_encoder');

    $mockClient = new Client();
    $mockClient->setFirstName("Bart");
    $mockClient->setLastName("de Boer");
    $mockClient->setEmailAddress("bart@deboer.com");
    $mockClient->setRelationNumberKeeper("77777444");
    $mockClient->setUsername("Bartje");
    $mockClient->setPassword($encoder->encodePassword($mockClient, "blauwetexelaar"));

    $locationAddress = new LocationAddress();
    $locationAddress->setAddressNumber("1");
    $locationAddress->setCity("Den Haag");
    $locationAddress->setPostalCode("1111AZ");
    $locationAddress->setState("ZH");
    $locationAddress->setStreetName("Boederij");
    $locationAddress->setCountry("Nederland");

    $billingAddress = new BillingAddress();
    $billingAddress->setAddressNumber("2");
    $billingAddress->setCity("Den Haag");
    $billingAddress->setPostalCode("2222GG");
    $billingAddress->setState("ZH");
    $billingAddress->setStreetName("Raamweg");
    $billingAddress->setCountry("Nederland");

    $companyAddress = new CompanyAddress();
    $companyAddress->setAddressNumber("3");
    $companyAddress->setCity("Rotterdam");
    $companyAddress->setPostalCode("3333XX");
    $companyAddress->setState("ZH");
    $companyAddress->setStreetName("Papierengeldweg");
    $companyAddress->setCountry("Nederland");

    $company = new Company();
    $company->setAddress($companyAddress);
    $company->setBillingAddress($billingAddress);
    $company->setCompanyName("Boederij de weiland");
    $company->setOwner($mockClient);

    $location = new Location();
    $location->setAddress($locationAddress);
    $location->setCompany($company);
    $location->setUbn("98989898");

    $company->addLocation($location);
    $mockClient->addCompany($company);

    $entityManager->persist($mockClient);

    $father = new Ram();
    $father->setUlnCountryCode("NL");
    $father->setUlnNumber("11111111");
    $father->setAnimalType(AnimalType::sheep);

    $mother = new Ewe();
    $mother->setUlnCountryCode("NL");
    $mother->setUlnNumber("222222222");
    $mother->setAnimalType(AnimalType::sheep);

    $child = new Ram();
    $child->setUlnCountryCode("UK");
    $child->setUlnNumber("333333333");
    $child->setPedigreeNumber("12345");
    $child->setPedigreeCountryCode("NL");
    $child->setAnimalType(AnimalType::sheep);
    $child->setDateOfBirth(new \DateTime());
    $child->setParentFather($father);
    $child->setParentMother($mother);


    $this->persist($father, "Ram");
    $this->persist($mother, "Ewe");
    $this->persist($child, "Neuter");

    return new JsonResponse("MOCK ENTITIES LOADED IN THE DB", 200);
  }
}
