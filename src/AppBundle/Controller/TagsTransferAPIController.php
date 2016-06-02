<?php

namespace AppBundle\Controller;

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
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Post a new DeclareTagsTransfer request, containing multiple Tags to be transferred",
   *   input = "AppBundle\Entity\DeclareTagsTransfer",
   *   output = "AppBundle\Entity\DeclareTagsTransfer"
   * )
   * @param Request $request
   * @return JsonResponse
   * @Route("")
   * @Method("POST")
   */
  public function createTagsTransfer(Request $request)
  {
    $content = $this->getContentAsArray($request);
    $client = $this->getAuthenticatedUser($request);

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
    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_TAGS_TRANSFER_REPOSITORY);
    $validation = $repository->validateTags($client, $content);
    $isValid = $validation[Constant::VALIDITY_NAMESPACE];

    if($isValid == false) {
      return new JsonResponse(array("code" => 404, "message" => $validation[Constant::MESSAGE_NAMESPACE]), 404);
    }

    //Convert the array into an object and add the mandatory values retrieved from the database
    $declareTagsTransfer = $this->buildMessageObject(RequestType::DECLARE_TAGS_TRANSFER_ENTITY, $content, $this->getAuthenticatedUser($request));

    //First Persist object to Database, before sending it to the queue
    $this->persist($declareTagsTransfer);

    //Send it to the queue and persist/update any changed state to the database
    $messageArray = $this->sendMessageObjectToQueue($declareTagsTransfer);

    return new JsonResponse($messageArray, 200);
  }

}