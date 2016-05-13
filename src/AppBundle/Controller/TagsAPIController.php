<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Client;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use AppBundle\Constant\Constant;

/**
 * @Route("/api/v1/tags")
 */
class TagsAPIController extends APIController implements TagsAPIControllerInterface {

  /**
   *
   * Retrieve a Tag by its ulnCountryCode and ulnNumber, concatenated.
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
   *   description = "Retrieve a Tag by its ulnCountryCode and ulnNumber, concatenated.",
   *   output = "AppBundle\Entity\Tag"
   * )
   * @param Request $request the request object
   * @param $Id
   * @return JsonResponse
   * @Route("{Id}")
   * @Method("GET")
   */
  public function getTagById(Request $request, $Id) {
    $tagRepository = $this->getDoctrine()->getRepository(Constant::TAG_REPOSITORY);

    //fixme
    if(strlen($Id) > 2) {
      //Strip countryCode
      $countryCode = mb_substr($Id, 0, 2, 'utf-8');

      //Strip ulnCode or pedigreeCode
      $ulnOrPedigreeCode = mb_substr($Id, 2, strlen($Id));

      $tag = $tagRepository->findByUlnNumberAndCountryCode($countryCode, $ulnOrPedigreeCode);
      return new JsonResponse($tag, 200);
    }

    return new JsonResponse(
      array("errorCode" => 428,
      "errorMessage" => "Given tagId is invalid, supply tagId in the following format: AA123456789"), 200);
  }

  /**
   *
   * Retrieve either a list of all Tags, or a subset of Tags with a given state-type:
   * {
   *    ASSIGNED,
   *    UNASSIGNED
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
   *        "description"=" Tags to filter on",
   *        "format"="?state=state-type"
   *      }
   *   },
   *   resource = true,
   *   description = "Retrieve a list of Tags",
   *   output = "array"
   * )
   * @param Request $request
   * @return JsonResponse
   * @Route("")
   * @Method("GET")
   */
  public function getTags(Request $request) {
    //No explicit filter given, thus find all
    if(!$request->query->has(Constant::STATE_NAMESPACE)) {
      $tags = $tagRepository = $this->getDoctrine()->getRepository(Constant::TAG_REPOSITORY)->findAll();
    } else { //A state parameter was given, use custom filter to find subset
      $state = $request->query->get(Constant::STATE_NAMESPACE);
      $tags = $this->getDoctrine()->getRepository(Constant::TAG_REPOSITORY)->findBy(array(Constant::TAG_STATUS_NAMESPACE => $state));
    }

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $tags), 200);
  }

  /**
   *
   * Create a new RetrieveEartags request
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
   *   description = "Create a new RetrieveEartags request",
   *   input = "AppBundle\Entity\RetrieveEartags",
   *   output = "AppBundle\Entity\RetrieveEartags"
   * )
   * @param Request $request
   * @return JsonResponse
   * @Route("/sync")
   * @Method("POST")
   */
  public function createRetrieveTags(Request $request) {
    //Get content to array
    $content = $this->getContentAsArray($request);

    //Convert the array into an object and add the mandatory values retrieved from the database
    $retrieveEartagsRequest = $this->buildMessageObject(RequestType::RETRIEVE_EARTAGS_ENTITY, $content, $this->getAuthenticatedUser($request));

    //First Persist object to Database, before sending it to the queue
    $this->persist($retrieveEartagsRequest, RequestType::RETRIEVE_EARTAGS_ENTITY);

    //Send it to the queue and persist/update any changed state to the database
    $this->sendMessageObjectToQueue($retrieveEartagsRequest, RequestType::RETRIEVE_EARTAGS_ENTITY, RequestType::RETRIEVE_EARTAGS);

    return new JsonResponse($retrieveEartagsRequest, 200);
  }

  /**
   *
   * Retrieve a RetrieveEartags request, found by its ID.
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
   *   description = "Retrieve a RetrieveEartag request, found by its ID",
   *   output = "AppBundle\Entity\RetrieveEartags"
   * )
   * @param Request $request
   * @param int $Id Id of the RetrieveTags to be returned
   * @return JsonResponse
   * @Route("/sync/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\RetrieveTagsRepository")
   * @Method("GET")
   */
  public function getRetrieveTagsById(Request $request, $Id) {
    $retrieveTagsRequest = $this->getDoctrine()->getRepository(Constant::RETRIEVE_EARTAGS_REPOSITORY)->findOneBy(array(Constant::REQUEST_ID_NAMESPACE=>$Id));
    return new JsonResponse($retrieveTagsRequest, 200);
  }

  /**
   * Retrieve either a list of all RetrieveEartags, or a subset of RetrieveEartags with a given state-type:
   * {
   *    ASSIGNED,
   *    UNASSIGNED
   * }
   *   *
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
   *        "description"=" RetrieveEartags to filter on",
   *        "format"="?state=state-type"
   *      }
   *   },
   *   resource = true,
   *   description = "Retrieve a list of RetrieveEartags",
   *   output = "array"
   * )
   * @param Request $request
   * @return JsonResponse
   * @Route("/sync")
   * @Method("GET")
   */
  public function getRetrieveTags(Request $request) {
    //No explicit filter given, thus find all
    if(!$request->query->has(Constant::STATE_NAMESPACE)) {
      $retrieveEartags = $this->getDoctrine()->getRepository(Constant::RETRIEVE_EARTAGS_REPOSITORY)->findAll();
    } else { //A state parameter was given, use custom filter to find subset
      $state = $request->query->get(Constant::STATE_NAMESPACE);
      $retrieveEartags = $this->getDoctrine()->getRepository(Constant::RETRIEVE_EARTAGS_REPOSITORY)->findBy(array(Constant::REQUEST_STATE_NAMESPACE => $state));
    }

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $retrieveEartags), 200);
  }

  /**
   *
   * Create a new DeclareEartagsTransfer request for multiple Tags
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
   *   description = "Post a new DeclareEartagsTransfer request, containing multiple Tags to be transferred",
   *   input = "AppBundle\Entity\DeclareEartagsTransfer",
   *   output = "AppBundle\Entity\DeclareEartagsTransfer"
   * )
   * @param Request $request
   * @return JsonResponse
   * @Route("/transfers")
   * @Method("POST")
   */
  public function createTagsTransfer(Request $request) {
    //Get content to array
    $content = $this->getContentAsArray($request);

    //Convert the array into an object and add the mandatory values retrieved from the database
    $declareTagsRetrieval = $this->buildMessageObject(RequestType::DECLARE_EARTAGS_TRANSFER_ENTITY, $content, $this->getAuthenticatedUser($request));

    //First Persist object to Database, before sending it to the queue
    $this->persist($declareTagsRetrieval, RequestType::DECLARE_EARTAGS_TRANSFER_ENTITY);

    //Send it to the queue and persist/update any changed state to the database
    $this->sendMessageObjectToQueue($declareTagsRetrieval, RequestType::DECLARE_EARTAGS_TRANSFER_ENTITY, RequestType::DECLARE_EARTAGS_TRANSFER);

    return new JsonResponse($declareTagsRetrieval, 200);
  }

  /**
   *  Retrieve either a list of all DeclareEartagsTransfers, or a subset of DeclareEartagsTransfers with a given state-type:
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
   *   description = "Retrieve a list of DeclareEartagsTransfers",
   *   output = "array"
   * )
   * @param Request $request
   * @param int $Id Id of the DeclareEarTagsTransfer to be returned
   * @return JsonResponse
   * @Route("/transfers/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareEartagsTransfer")
   * @Method("GET")
   */
  public function getTagsTransferById(Request $request, $Id) {
    $tagTransfer = $this->getDoctrine()->getRepository(Constant::DECLARE_EARTAGS_TRANSFER_REPOSITORY)->findOneBy(array(Constant::REQUEST_ID_NAMESPACE=>$Id));
    return new JsonResponse($tagTransfer, 200);
  }

  /**
   * Retrieve a TagsTransfer request, found by its ID.
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
   *   description = "Retrieve a list of DeclareEartagsTransfers",
   *   output = "array"
   * )
   * @param Request $request
   * @param int $Id Id of the DeclareEarTagsTransfer to be returned
   * @return JsonResponse
   * @Route("/transfers/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareEartagsTransfer")
   * @Method("GET")
   */
  public function getTagsTransfers(Request $request) {
    //No explicit filter given, thus find all
    if(!$request->query->has(Constant::STATE_NAMESPACE)) {
      $tagTransfers = $this->getDoctrine()->getRepository(Constant::DECLARE_EARTAGS_TRANSFER_REPOSITORY)->findAll();
    } else { //A state parameter was given, use custom filter to find subset
      $state = $request->query->get(Constant::STATE_NAMESPACE);
      $tagTransfers = $this->getDoctrine()->getRepository(Constant::DECLARE_EARTAGS_TRANSFER_REPOSITORY)->findBy(array(Constant::REQUEST_STATE_NAMESPACE => $state));
    }

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $tagTransfers), 200);
  }

  /**
   * Update existing DeclareEartagsTranfer request
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
   *   description = "Update DeclareEartagsTranfer request",
   *   input = "AppBundle\Entity\DeclareEartagsTransfer",
   *   output = "AppBundle\Entity\DeclareEartagsTransfer"
   * )
   * @param Request $request
   * @param int $Id Id of the DeclareEarTagsTransfer to be updated
   * @return JsonResponse
   * @Route("/transfers/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareEartagsTransfer")
   * @Method("PUT")
   */
  public function updateTagsTransfer(Request $request, $Id) {
    //Validate uln/pedigree code
    if(!$this->isUlnOrPedigreeCodeValid($request)) {
      return new JsonResponse(Constant::RESPONSE_ULN_NOT_FOUND, Constant::RESPONSE_ULN_NOT_FOUND[Constant::CODE_NAMESPACE]);
    }

    //Convert the array into an object and add the mandatory values retrieved from the database
    $declareTagsTransferUpdate = $this->buildMessageObject(RequestType::DECLARE_EARTAGS_TRANSFER_ENTITY,
      $this->getContentAsArray($request), $this->getAuthenticatedUser($request));

    $entityManager = $this->getDoctrine()->getEntityManager()->getRepository(Constant::DECLARE_EARTAGS_TRANSFER_REPOSITORY);
    $declareTagsTransfer = $entityManager->updateDeclareTagsTransferMessage($declareTagsTransferUpdate, $Id);

    if($declareTagsTransfer == null) {
      return new JsonResponse(array("message"=>"No DeclareTagsTransfer found with request_id:" . $Id), 204);
    }

    return new JsonResponse($declareTagsTransfer, 200);
  }
}