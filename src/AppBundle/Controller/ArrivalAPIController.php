<?php

namespace AppBundle\Controller;

use AppBundle\Enumerator\MessageClass;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use AppBundle\Entity\DeclareArrival;

/**
 * @Route("/api/v1/arrivals")
 */
class ArrivalAPIController extends APIController implements ArrivalAPIControllerInterface
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
   * @return JsonResponse
   * @Route("/status/")
   * @Method("GET")
   */
  public function getArrivalByState(Request $request)
  {
    //Initialize default state to filter on declare arrivals
    $state = RequestStateType::OPEN;

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
  public function createArrival(Request $request)
  {
    //Convert front-end message into an array
    //Get content to array
    $content = $this->getContentAsArray($request);

    //Convert the array into an object and add the mandatory values retrieved from the database
    $messageObject = $this->buildMessageObject(MessageClass::DeclareArrival, $content, $this->getAuthenticatedUser($request));

    //First Persist object to Database, before sending it to the queue
    $this->persist($messageObject, MessageClass::DeclareArrival);

    //Send it to the queue and persist/update any changed state to the database
    $this->sendMessageObjectToQueue($messageObject, MessageClass::DeclareArrival, RequestType::DECLARE_ARRIVAL);

    return new JsonResponse($messageObject, 200);
  }

  /**
   * Update existing DeclareArrival request
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
   *   description = "Update a DeclareArrival request",
   *   input = "AppBundle\Entity\DeclareArrival",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareArrivalRepository")
   * @Method("PUT")
   */
  public function editArrival(Request $request, $Id)
  {
    //Convert the array into an object and add the mandatory values retrieved from the database
    $declareArrivalUpdate = $this->buildMessageObject(MessageClass::DeclareArrival,
      $this->getContentAsArray($request), $this->getAuthenticatedUser($request));

    $entityManager = $this->getDoctrine()->getEntityManager()->getRepository('AppBundle:DeclareArrival');
    $declareArrival = $entityManager->findOneBy(array('requestId'=> $Id));

    $declareArrival->setAnimal($declareArrivalUpdate->getAnimal());
    $declareArrival->setArrivalDate(new \DateTime());
    $declareArrival->setLocation($declareArrivalUpdate->getLocation());
    $declareArrival->setImportAnimal($declareArrivalUpdate->getImportAnimal());

    $declareArrival = $entityManager->update($declareArrival);

    return new JsonResponse($declareArrival, 200);
  }
}