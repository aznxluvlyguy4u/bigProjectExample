<?php

namespace AppBundle\Controller;

use AppBundle\Component\MessageBuilderBase;
use AppBundle\Entity\Location;
use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\Ram;
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

    // ***** FOR NOW USE THE DEBUG ROUTE ***** //

//TODO: Activate these when the messageObject is complete
//    //First Persist object to Database, before sending it to the queue
//    $this->persist($messageObject, $this::MESSAGE_CLASS);
//
//    //Send it to the queue and persist/update any changed state to the database
//    $this->sendMessageObjectToQueue($messageObject, $this::REQUEST_TYPE);

    return new JsonResponse("OK", 200);
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

    //Setup mock message as JSON
    $content = '{
   "import_animal": true,
   "ubn_previous_owner": "7654321",
   "animal": {
     "pedigree_country_code": "NL",
     "pedigree_number": "12345",
     "uln_country_code": "NL",
     "uln_number": "1234566"
   },
   "location" : {
     "ubn" : "0031079"
   },
   "arrival_date": "2016-04-04T12:55:43-05:00",
   "type":"DeclareArrival"
  }';

    $entityManager = $this->getDoctrine()->getEntityManager();

    //Get the first dude in the db
    $user = $entityManager->getRepository('AppBundle:Person')
        ->findOneBy(array('id' => 1));

    //Convert mock message into an array
    $content = new ArrayCollection(json_decode($content, true));

    $messageObject = $this->buildMessageObject($this::MESSAGE_CLASS, $content, $user);

//    $declareArrival = $this->getSerializer()->deserializeToObject($contentJson, 'AppBundle\Entity\DeclareArrival', $content);

//TODO: Activate these when the messageObject is complete
    //First Persist object to Database, before sending it to the queue
    $this->persist($messageObject, $this::MESSAGE_CLASS);

    //Send it to the queue and persist/update any changed state to the database
    $this->sendMessageObjectToQueue($messageObject, $this::REQUEST_TYPE);

//    return new JsonResponse(array('status' => $declareArrival), 200);
    return new JsonResponse($messageObject, 200);
//    return new JsonResponse("OK", 200);

  }

  /**
   *
   * Temporary route for testing code
   *
   * @Route("/test/code")
   * @Method("POST")
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


    //Setup mock message as JSON
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

    //$messageObject = $this->buildMessageObject($request, $this::MESSAGE_CLASS, null);

    $messageObject = $this->get('app.serializer.custom');

    $x =  $messageObject->serializeToJSON($message);
    $x = $this->getContentAsArray($x);
    $x =  $messageObject->deserializeToObject($x, "AppBundle\Entity\Ram");

    return new JsonResponse($x, 200);
//    return new JsonResponse("OK", 200);
  }
}
