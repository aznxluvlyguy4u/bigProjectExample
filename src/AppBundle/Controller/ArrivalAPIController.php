<?php

namespace AppBundle\Controller;

use AppBundle\Component\MessageBuilderBase;
use AppBundle\Entity\Location;
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

  /**
   * @var Client
   */
  private $user;

  /**
   * Retrieve a DeclareArrival, found by it's ID.
   *
   * @ApiDoc(
   *   resource = true,
   *   description = "Retrieve a DeclareArrival by given ID",
   *   output = "AppBundle\Entity\DeclareArrival"
   * )
   *
   *
   * @param int $Id Id of the DeclareArrival to be returned
   *
   * @return JsonResponse
   *
   *
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareArrivalRepository")
   * @Method("GET")
   */
  public function getArrivalById($Id)
  {
    $arrival = $this->getDoctrine()->getRepository('AppBundle:DeclareArrival')->find($Id);
    return new JsonResponse($arrival, 200);
  }

  /**
   * Retrieve either a list of all DeclareArrivals or a sublist of DeclareArrivals with a given state-type:
   * {
   *    OPEN,
   *    FINISHED,
   *    FAILED,
   *    CANCELLED
   * },
   * @ApiDoc(
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
    $result = $this->isTokenValid($request);

    if($result instanceof JsonResponse){
      return $result;
    }

    $declareArrivalRequests = $this->getDoctrine()->getRepository('AppBundle:DeclareArrivalResponse')->findAll();
    $filteredResults = new ArrayCollection();

    if(!$request->query->has('state')) {
      return new JsonResponse($declareArrivalRequests, 200);
    } else {
      $state = $request->query->get('state');

      foreach($declareArrivalRequests as $arrivalRequest) {
        if($arrivalRequest->getDeclareArrivalRequestMessage()->getRequestState() == $state){
          $filteredResults->add($arrivalRequest);
        }
      }
    }

    return new JsonResponse($filteredResults, 200);
  }

  /**
   * Create a new DeclareArrival request
   *
   * @ApiDoc(
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

    $mockClient = new Client();
    $mockClient->setFirstName("Bart");
    $mockClient->setLastName("de Boer");
    $mockClient->setEmailAddress("bart@deboer.com");
    $mockClient->setRelationNumberKeeper("77777444");

    $this->persist($mockClient, "Person" );


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
