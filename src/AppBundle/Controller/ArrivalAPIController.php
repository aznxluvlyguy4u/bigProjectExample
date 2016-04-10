<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Animal;
use AppBundle\Entity\Location;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Client;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * @Route("/api/v1")
 */
class ArrivalAPIController extends APIController
{
  const REQUEST_TYPE = 'DECLARE_ARRIVAL';

  /**
   * @var Client
   */
  private $user;
  /**
   *
   * Get a list of DeclareArrivals with a given state:{OPEN, CLOSED, DECLINED}.
   *
   *
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
   *
   * Get a DeclareArrival, found by it's ID.
   *
   * @Route("/arrivals/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareArrivalRepository")
   * @Method("GET")
   */
  public function getArrivalById(Request $request, $Id)
  {
    $result = $this->isTokenValid($request);

    if($result instanceof JsonResponse){
      return $result;
    }

    $arrival = $this->getDoctrine()->getRepository('AppBundle:DeclareArrival')->find($Id);
    return new JsonResponse($arrival, 200);
  }

  /**
   *
   * Create a DeclareArrival Request.
   *
   * @Route("/arrivals")
   * @Method("POST")
   */
  public function postNewArrival(Request $request)
  {

    $result = $this->isTokenValid($request);

    if($result instanceof JsonResponse){
      return $result;
    }

    //Get content to array
    $content = $this->getContentAsArray($request);

    /**
     * Create additional request properties.
     *
     * Strategy: get below User details based on token passed,
     * filter database to get user belonging to the given token.
     */
    //$user = $this->getUserByToken($request)[0];
    //$ubn = ($user->getLocations()->get(0)->getUbn());
    //$relationNumberKeeper = $user->getRelationNumberKeeper();

    //Generate new requestId
    $requestId = $this->getNewRequestId();

    $content->set('request_state', 'open');
    $content->set('request_id', $requestId);
    $content->set('message_id', $requestId);
    $content->set('log_date', new \DateTime());
    $content->set('relation_number_keeper', '123456789');
    $content->set('action', "C");
    $content->set('recovery_indicator', "N");

    $content->set('location', array('ubn' => '1234567'));

    $animal = $content['animal'];
    $newAnimalDetails = array_merge($animal,
      array('type' => 'Ram',
        'animal_type' => 3,
        'animal_category' => 1,
      ));

    $content->set('animal', $newAnimalDetails);

    //Serialize after added properties to JSON
    $declareArrivalJSON = $this->serializeToJSON($content);

    //Deserialize to Arrival
    $declareArrival = $this->deserializeToObject($declareArrivalJSON,'AppBundle\Entity\DeclareArrival');

    //Send serialized message to Queue
    $sendToQresult = $this->getQueueService()->send($requestId, $declareArrivalJSON, $this::REQUEST_TYPE);

    //If send to Queue, failed, it needs to be resend, set state to failed
    if($sendToQresult['statusCode'] != '200') {
      $arrival['request_state'] = 'failed';

      return new JsonResponse(array('status'=> 'failure','errorMessage' => 'Failed to send message to Queue'), 500);
    }

    //Persist object to Database
    $arrival = $this->getDoctrine()->getRepository('AppBundle:DeclareArrival')->persist($declareArrival);

    return new JsonResponse(array('status' => '200 OK'), 200);
  }

  /**
   *
   * Debug endpoint
   *
   * @Route("/arrivals/test/foo")
   * @Method("GET")
   */
  public function debugAPI(Request $request)
  {
    $result = $this->isTokenValid($request);

    if($result instanceof JsonResponse){
      return $result;
    }

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
