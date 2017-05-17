<?php

namespace AppBundle\Controller;

use AppBundle\Util\Validator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Enumerator\RequestType;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use AppBundle\Constant\Constant;

/**
 * @Route("/api/v1/tags-sync")
 */
class TagsSyncAPIController extends APIController implements TagsSyncAPIControllerInterface
{

  /**
   *
   * Retrieve a RetrieveTags request, found by its ID.
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
   *   description = "Retrieve a RetrieveTag request, found by its ID"
   * )
   * @param Request $request
   * @param int $Id Id of the RetrieveTags to be returned
   * @return JsonResponse
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\RetrieveTagsRepository")
   * @Method("GET")
   */
  public function getRetrieveTagsById(Request $request, $Id)
  {
    $retrieveTagsRequest = $this->getDoctrine()->getRepository(Constant::RETRIEVE_TAGS_REPOSITORY)->findOneBy(array(Constant::REQUEST_ID_NAMESPACE=>$Id));
    return new JsonResponse($retrieveTagsRequest, 200);
  }

  /**
   * Retrieve either a list of all RetrieveTags requests or a subset of RetrieveTags requests with a given state-type:
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
   *        "description"=" RetrieveTags requests to filter on",
   *        "format"="?state=state-type"
   *      }
   *   },
   *   resource = true,
   *   description = "Retrieve a list of RetrieveTags"
   * )
   * @param Request $request
   * @return JsonResponse
   * @Route("")
   * @Method("GET")
   */
  public function getRetrieveTags(Request $request)
  {
    //No explicit filter given, thus find all
    if(!$request->query->has(Constant::STATE_NAMESPACE)) {
      $retrieveEartags = $this->getDoctrine()->getRepository(Constant::RETRIEVE_TAGS_REPOSITORY)->findAll();
    } else { //A state parameter was given, use custom filter to find subset
      $state = $request->query->get(Constant::STATE_NAMESPACE);
      $retrieveEartags = $this->getDoctrine()->getRepository(Constant::RETRIEVE_TAGS_REPOSITORY)->findBy(array(Constant::REQUEST_STATE_NAMESPACE => $state));
    }

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $retrieveEartags), 200);
  }

  /**
   *
   * Create a new RetrieveTags request
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
   *   description = "Create a new RetrieveTags request"
   * )
   * @param Request $request
   * @return JsonResponse
   * @Route("")
   * @Method("POST")
   */
  public function createRetrieveTags(Request $request)
  {
    //Get content to array
    $content = $this->getContentAsArray($request);
    $client = $this->getAuthenticatedUser($request);
    $loggedInUser = $this->getLoggedInUser($request);
    $location = $this->getSelectedLocation($request);

    if($client == null) { return Validator::createJsonResponse('Client cannot be null', 428); }
    if($location == null) { return Validator::createJsonResponse('Location cannot be null', 428); }

    //Convert the array into an object and add the mandatory values retrieved from the database
    $retrieveEartagsRequest = $this->buildMessageObject(RequestType::RETRIEVE_TAGS_ENTITY, $content, $client, $loggedInUser, $location);

    //First Persist object to Database, before sending it to the queue
    $this->persist($retrieveEartagsRequest);

    //Send it to the queue and persist/update any changed state to the database
    $messageArray = $this->sendMessageObjectToQueue($retrieveEartagsRequest);

    return new JsonResponse($messageArray, 200);
  }
}