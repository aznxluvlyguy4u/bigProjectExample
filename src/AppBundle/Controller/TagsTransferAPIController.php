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
   *  Retrieve either a list of all DeclareTagsTransfers, or a subset of DeclareTagsTransfers with a given state-type:
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
   *   resource = true,
   *   description = "Retrieve a list of DeclareTagsTransfers",
   *   output = "array"
   * )
   * @param Request $request
   * @param int $Id Id of the DeclareTagsTransfer to be returned
   * @return JsonResponse
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareTagsTransferRepository")
   * @Method("GET")
   */
  public function getTagsTransferById(Request $request, $Id)
  {
    $tagTransfer = $this->getDoctrine()->getRepository(Constant::DECLARE_TAGS_TRANSFER_REPOSITORY)->findOneBy(array(Constant::REQUEST_ID_NAMESPACE=>$Id));
    return new JsonResponse($tagTransfer, 200);
  }

  /**
   * Retrieve either a list of all DeclareTransferTags requests, or a subset of DeclareTransferTags requests with a given state-type:
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
   *   description = "Retrieve a list of DeclareTagsTransfers",
   *   output = "array"
   * )
   * @param Request $request
   * @param int $Id Id of the DeclareTagsTransfer to be returned
   * @return JsonResponse
   * @Route("")
   * @Method("GET")
   */
  public function getTagsTransfers(Request $request)
  {
    //No explicit filter given, thus find all
    if(!$request->query->has(Constant::STATE_NAMESPACE)) {
      $tagTransfers = $this->getDoctrine()->getRepository(Constant::DECLARE_TAGS_TRANSFER_REPOSITORY)->findAll();
    } else { //A state parameter was given, use custom filter to find subset
      $state = $request->query->get(Constant::STATE_NAMESPACE);
      $tagTransfers = $this->getDoctrine()->getRepository(Constant::DECLARE_TAGS_TRANSFER_REPOSITORY)->findBy(array(Constant::REQUEST_STATE_NAMESPACE => $state));
    }

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $tagTransfers), 200);
  }

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
    $declareTagsRetrieval = $this->buildMessageObject(RequestType::DECLARE_TAGS_TRANSFER_ENTITY, $content, $this->getAuthenticatedUser($request));

    //First Persist object to Database, before sending it to the queue
    $this->persist($declareTagsRetrieval, RequestType::DECLARE_TAGS_TRANSFER_ENTITY);

    //Send it to the queue and persist/update any changed state to the database
    $this->sendMessageObjectToQueue($declareTagsRetrieval, RequestType::DECLARE_TAGS_TRANSFER_ENTITY, RequestType::DECLARE_EARTAGS_TRANSFER);

    return new JsonResponse($declareTagsRetrieval, 200);
  }

  /**
   * Update existing DeclareTagsTranfer request
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
   *   description = "Update DeclareTagsTranfer request",
   *   input = "AppBundle\Entity\DeclareTagsTransfer",
   *   output = "AppBundle\Entity\DeclareTagsTransfer"
   * )
   * @param Request $request
   * @param int $Id Id of the DeclareTagsTransfer to be updated
   * @return JsonResponse
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareTagsTransferRepository")
   * @Method("PUT")
   */
  public function updateTagsTransfer(Request $request, $Id)
  {
    //Validate uln/pedigree code
    if(!$this->isUlnOrPedigreeCodeValid($request)) {
      return new JsonResponse(Constant::RESPONSE_ULN_NOT_FOUND, Constant::RESPONSE_ULN_NOT_FOUND[Constant::CODE_NAMESPACE]);
    }

    //Convert the array into an object and add the mandatory values retrieved from the database
    $declareTagsTransferUpdate = $this->buildMessageObject(RequestType::DECLARE_TAGS_TRANSFER_ENTITY,
      $this->getContentAsArray($request), $this->getAuthenticatedUser($request));

    $entityManager = $this->getDoctrine()->getEntityManager()->getRepository(Constant::DECLARE_TAGS_TRANSFER_REPOSITORY);
    $declareTagsTransfer = $entityManager->updateDeclareTagsTransferMessage($declareTagsTransferUpdate, $Id);

    if($declareTagsTransfer == null) {
      return new JsonResponse(array("message"=>"No DeclareTagsTransfer found with request_id:" . $Id), 204);
    }

    return new JsonResponse($declareTagsTransfer, 200);
  }
}