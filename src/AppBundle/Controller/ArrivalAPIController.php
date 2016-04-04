<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Animal;
use AppBundle\Entity\Location;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Arrival;
use AppBundle\Entity\Sheep;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @Route("/api/v1")
 */
class ArrivalAPIController extends APIController
{
  const REQUEST_TYPE = 'DECLARE_ARRIVAL';

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
    $state = '';

    if(!$request->query->has('state')) {
      return new BadRequestHttpException("State type: OPEN / FAILED / CLOSED not set.");
    }

    $state = $request->query->get('state');
    $user = $this->getDoctrine()->getRepository('AppBundle:Arrival')->findBy(['requestState' => $state]);


    return new JsonResponse($user);
  }

  /**
   *
   * Get a DeclareArrival, found by it's ID.
   *
   * @Route("/arrivals/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\ArrivalRepository")
   * @Method("GET")
   */
  public function getArrivalById($Id)
  {
    $arrival = $this->getDoctrine()->getRepository('AppBundle:Arrival')->find($Id);
    return new JsonResponse($arrival);
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
      array('type' => 'ram',
        'animal_type' => 3,
      ));

    $content->set('animal', $newAnimalDetails);


    //Serialize after added properties to JSON
    $declareArrivalJSON = $this->serializeToJSON($content);

    //Deserialize to Arrival
    $declareArrival = $this->deserializeToObject($declareArrivalJSON,'AppBundle\Entity\Arrival');

    //Send serialized message to Queue
    $sendToQresult = $this->getQueueService()->send($requestId, $declareArrivalJSON, $this::REQUEST_TYPE);

    //If send to Queue, failed, it needs to be resend, set state to failed
    if($sendToQresult['statusCode'] != '200') {
      $arrival['request_state'] = 'failed';
    }

    //Persist object to Database
    $arrival = $this->getDoctrine()->getRepository('AppBundle:Arrival')->persist($declareArrival);

    return new JsonResponse($content);
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
    return new JsonResponse("OK");
  }

}
