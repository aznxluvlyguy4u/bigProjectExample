<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Company;
use AppBundle\Entity\Location;
use AppBundle\Entity\Client;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Doctrine\Common\Collections\ArrayCollection;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/api/v1/arrivals")
 */
class ArrivalAPIController extends APIController
{
  const REQUEST_TYPE = 'DECLARE_ARRIVAL';
  const STATE_NAMESPACE = 'state';
  const REQUEST_STATE_NAMESPACE = 'requestState';
  const DECLARE_ARRIVAL_REPOSITORY = 'AppBundle:DeclareArrival';
  const DECLARE_ARRIVAL_RESULT_NAMESPACE = "result";

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
   * @Route("")
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
   *   resource = true,
   *   description = "Post a DeclareArrival request",
   *   input = "AppBundle\Entity\DeclareArrival",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   * @param Request $request the request object
   * @return JsonResponse

   *
   * @Route("")
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
   * @Route("/test/debug")
   * @Method("GET")
   */
  public function debugAPI(Request $request)
  {
    $user = $this->getAuthenticatedUser($request);

    return new JsonResponse($user, 200);
  }
}
