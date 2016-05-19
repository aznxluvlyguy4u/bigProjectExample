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
    //Get content to array
    $content = $this->getContentAsArray($request);

    //Convert the array into an object and add the mandatory values retrieved from the database
    $declareTagsTransfer = $this->buildMessageObject(RequestType::DECLARE_TAGS_TRANSFER_ENTITY, $content, $this->getAuthenticatedUser($request));

    //First Persist object to Database, before sending it to the queue
    $this->persist($declareTagsTransfer, RequestType::DECLARE_TAGS_TRANSFER_ENTITY);

    //Send it to the queue and persist/update any changed state to the database
    $this->sendMessageObjectToQueue($declareTagsTransfer, RequestType::DECLARE_TAGS_TRANSFER_ENTITY, RequestType::DECLARE_TAGS_TRANSFER);

    return new JsonResponse($declareTagsTransfer, 200);
  }

}