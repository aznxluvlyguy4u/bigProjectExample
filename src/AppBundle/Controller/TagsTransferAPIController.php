<?php

namespace AppBundle\Controller;

use AppBundle\Entity\DeclareTagReplace;
use AppBundle\Entity\DeclareTagsTransfer;
use AppBundle\Entity\DeclareTagsTransferRepository;
use AppBundle\Entity\TagTransferItemRequest;
use AppBundle\Entity\TagTransferItemResponse;
use AppBundle\Entity\TagTransferItemResponseRepository;
use AppBundle\Util\ActionLogWriter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Enumerator\RequestType;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use AppBundle\Constant\Constant;

/**
 * Class TransferTagsAPI
 * @Route("/api/v1/tags-transfers")
 */
class TagsTransferAPIController extends APIController implements TagsTransferAPIControllerInterface
{

  /**
   *
   * Create a new DeclareTagsTransfer request for multiple Tags
   *
   * @ApiDoc(
   *   section = "Tag Transfers",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Post a new DeclareTagsTransfer request, containing multiple Tags to be transferred"
   * )
   * @param Request $request
   * @return JsonResponse
   * @Route("")
   * @Method("POST")
   */
  public function createTagsTransfer(Request $request)
  {
    $om = $this->getDoctrine()->getManager();
    
    $content = $this->getContentAsArray($request);
    $client = $this->getAuthenticatedUser($request);
    $loggedInUser = $this->getLoggedInUser($request);
    $location = $this->getSelectedLocation($request);

    $log = ActionLogWriter::declareTagTransferPost($om, $client, $loggedInUser, $content);
    
    //Validate if ubn is in database and retrieve the relationNumberKeeper owning that ubn
    $ubnVerification = $this->isUbnValid($content->get(Constant::UBN_NEW_OWNER_NAMESPACE));
    if(!$ubnVerification['isValid']) {
      $code = $ubnVerification[Constant::CODE_NAMESPACE];
      $message = $ubnVerification[Constant::MESSAGE_NAMESPACE];
      return new JsonResponse(array("code" => $code, "message" => $message), $code);
    }
    $content->set("relation_number_acceptant", $ubnVerification['relationNumberKeeper']);

    //TODO Phase 2, with history and error tab in front-end, we can do a less strict filter. And only remove the incorrect tags and process the rest. However for proper feedback to the client we need to show the successful and failed TagTransfer history.

    //Check if ALL tags are unassigned and in the database, else don't send any TagTransfer
    /** @var DeclareTagsTransferRepository $repository */
    $repository = $this->getDoctrine()->getRepository(DeclareTagsTransfer::class);
    $validation = $repository->validateTags($client, $location, $content);
    if($validation[Constant::IS_VALID_NAMESPACE] == false) {
      return new JsonResponse($validation[Constant::MESSAGE_NAMESPACE], $validation[Constant::CODE_NAMESPACE]);
    }

    //Convert the array into an object and add the mandatory values retrieved from the database
    $declareTagsTransfer = $this->buildMessageObject(RequestType::DECLARE_TAGS_TRANSFER_ENTITY, $content, $client, $loggedInUser, $location);

    //First Persist object to Database, before sending it to the queue
    $this->persist($declareTagsTransfer);

    //Send it to the queue and persist/update any changed state to the database
    $messageArray = $this->sendMessageObjectToQueue($declareTagsTransfer);

    $log = ActionLogWriter::completeActionLog($om, $log);
    
    return new JsonResponse($messageArray, 200);
  }


  /**
   *
   * Get TagTransferItemRequests which have failed last responses.
   *
   * @ApiDoc(
   *   section = "Tag Transfers",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Get TagTransferItemRequests which have failed last responses"
   * )
   *
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-errors")
   * @Method("GET")
   */
  public function getTagTransferItemErrors(Request $request)
  {
    $client = $this->getAuthenticatedUser($request);
    $location = $this->getSelectedLocation($request);

    /** @var TagTransferItemResponseRepository $repository */
    $repository = $this->getDoctrine()->getRepository(TagTransferItemResponse::class);
    $tagTransfers = $repository->getTagTransferItemRequestsWithLastErrorResponses($client, $location);

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $tagTransfers), 200);
  }


  /**
   *
   * For the history view, get TagTransferItemRequests which have the following requestState:
   * OPEN, REVOKING, REVOKED, FINISHED or FINISHED_WITH_WARNING.
   *
   * @ApiDoc(
   *   section = "Tag Transfers",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Get TagTransferItemRequests which have the following requestState: OPEN, REVOKING, REVOKED, FINISHED or FINISHED_WITH_WARNING"
   * )
   *
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-history")
   * @Method("GET")
   */
  public function getTagTransferItemHistory(Request $request)
  {
    $client = $this->getAuthenticatedUser($request);
    $location = $this->getSelectedLocation($request);

    /** @var TagTransferItemResponseRepository $repository */
    $repository = $this->getDoctrine()->getRepository(TagTransferItemResponse::class);
    $tagTransfers = $repository->getTagTransferItemRequestsWithLastHistoryResponses($client, $location);

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $tagTransfers),200);
  }
}