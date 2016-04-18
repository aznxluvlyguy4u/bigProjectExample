<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Location;
use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\Ram;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Doctrine\Common\Collections\ArrayCollection;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * @Route("/api/v1")
 */
class ArrivalAPIController extends APIController
{
  const REQUEST_TYPE = 'DECLARE_ARRIVAL';
  const MESSAGE_CLASS = 'DeclareArrival';

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
   * @Route("/arrivals/{Id}")
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
   * @Route("/arrivals/status")
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
   * @Route("/arrivals")
   * @Method("POST")
   */
  public function postNewArrival(Request $request)
  {
    //Authentication
    $result = $this->isTokenValid($request);

    if($result instanceof JsonResponse) {
        return $result;
    }

    //Generate new requestId
    $requestId = $this->getNewRequestId();

    //Build the complete message and get it back in JSON
    $jsonMessage = $this->getRequestMessageBuilder()->build("DeclareArrival", $request, $requestId);

    //First Persist object to Database, before sending it to the queue
    $messageObject = $this->persist($jsonMessage, $this::MESSAGE_CLASS);

    //Send serialized message to Queue
    $sendToQresult = $this->getQueueService()->send($requestId, $jsonMessage, $this::REQUEST_TYPE);

    //If send to Queue, failed, it needs to be resend, set state to failed
    if($sendToQresult['statusCode'] == '200') {
      $messageObject->setRequestState('failed');
      $messageObject = $this->getDoctrine()->getRepository('AppBundle:DeclareArrival')->persist($messageObject);
    }

    return new JsonResponse(array('status' => '200 OK'), 200);
  }

  /**
   *
   * Debug endpoint
   *
   * @Route("/arrivals/test/debug")
   * @Method("GET")
   */
  public function debugAPI(Request $request)
  {


    $message = '{
   "import_animal": true,
   "ubn_previous_owner": "7654321",
   "animal": {
     "pedigree_country_code": "NL",
     "pedigree_number": "12345",
     "uln_country_code": "UK",
     "uln_number": "0123456789",
     "type":"Ram"
   },
   "location" : {
     "ubn" : "0031079"
   },
   "arrival_date": "2016-04-04T12:55:43-05:00",
   "log_date": "2016-04-04T12:55:43-05:00",
   "type":"DeclareArrival"
}';


    $content = new ArrayCollection(json_decode($message, true));
    $message = $this->serializeToJSON($content);


   $request = $this->deserializeToObject($message, 'AppBundle\Entity\DeclareArrival');


    //Generate new requestId
    $requestId = $this->getNewRequestId();

    //Build the complete message and get it back in JSON
    $jsonMessage = $this->getRequestMessageBuilder()->build("DeclareArrival", $content, $requestId);

    //dump($jsonMessage); die();

//    //First Persist object to Database, before sending it to the queue
//    $messageObject = $this->persist($jsonMessage, $this::MESSAGE_CLASS);
//
//    //Send serialized message to Queue
//    $sendToQresult = $this->getQueueService()->send($requestId, $jsonMessage, $this::REQUEST_TYPE);
//
//    //If send to Queue, failed, it needs to be resend, set state to failed
//    if($sendToQresult['statusCode'] == '200') {
//      $messageObject->setRequestState('failed');
//      $messageObject = $this->getDoctrine()->getRepository('AppBundle:DeclareArrival')->persist($messageObject);
//    }

    return new JsonResponse(array('status' => $jsonMessage), 200);
  }

  /**
   *
   * Temporary route for testing code
   *
   * @Route("/arrivals/test/code")
   * @Method("POST")
   *
   */
   public function testingStuff(Request $request)
   {
     //Authentication
     $result = $this->isTokenValid($request);

     if($result instanceof JsonResponse) {
       return $result;
     }


     $requestMessage = $this->getMessageBuilder()->build("declareArrival", $request);

     return new JsonResponse("OK", 200);

   }


  /**
   *
   * Add a mock client to the database
   *
   * @Route("/arrivals/test/client")
   * @Method("GET")
   */
  public function addAClient()
  {


    $user = new Client();
    $user->setFirstName("Frank");
    $user->setLastName("de Boer");
    $user->setEmailAddress("frank@deboer.com");
    $user->setRelationNumberKeeper("9991111");

    $location = new Location();
    $location->setUbn("9999999");
    $user->addLocation($location);

    $user = $this->getDoctrine()->getRepository('AppBundle:Person')->persist($user);

    return new JsonResponse($user, 200);
  }
}
