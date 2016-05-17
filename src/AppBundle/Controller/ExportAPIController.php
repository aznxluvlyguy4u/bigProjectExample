<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use AppBundle\Enumerator\RequestType;

/**
 * @Route("/api/v1/exports")
 */
class ExportAPIController extends APIController implements ExportAPIControllerInterface {

  /**
   * Retrieve a DeclareExport, found by it's ID.
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
   *   description = "Retrieve a DeclareExport by given ID",
   *   output = "AppBundle\Entity\DeclareExport"
   * )
   * @param Request $request the request object
   * @param int $Id Id of the DeclareExport to be returned
   * @return JsonResponse
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareExportRepository")
   * @Method("GET")
   */
  public function getExportById(Request $request, $Id) {
    //TODO for phase 2: read a location from the $request and find declareExports for that location
    $client = $this->getAuthenticatedUser($request);
    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_EXPORT_REPOSITORY);

    $export = $repository->getExportsById($client, $Id);

    return new JsonResponse($export, 200);
  }

  /**
   * Retrieve either a list of all DeclareExports or a subset of DeclareExports with a given state-type:
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
   *        "description"=" DeclareExportss to filter on",
   *        "format"="?state=state-type"
   *      }
   *   },
   *   resource = true,
   *   description = "Retrieve a a list of DeclareExports",
   *   output = "AppBundle\Entity\DeclareExport"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("GET")
   */
  public function getExports(Request $request) {
    //TODO for phase 2: read a location from the $request and find declareExports for that location
    $client = $this->getAuthenticatedUser($request);
    $stateExists = $request->query->has(Constant::STATE_NAMESPACE);
    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_EXPORT_REPOSITORY);

    if(!$stateExists) {
      $declareExports = $repository->getExports($client);

    } else { //A state parameter was given, use custom filter to find subset
      $state = $request->query->get(Constant::STATE_NAMESPACE);
      $declareExports = $repository->getExports($client, $state);
    }

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $declareExports), 200);
  }

  /**
   * Create a new DeclareExport request
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
   *   description = "Post a DeclareExport request",
   *   input = "AppBundle\Entity\DeclareExport",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("POST")
   */
  public function createExport(Request $request) {
    //Validate uln/pedigree code
    if(!$this->isUlnOrPedigreeCodeValid($request)) {
      return new JsonResponse(array("error_code" => 428, "error_message"=>"Given Uln & Country code is invalid, it is not registered to a known Tag"), 428);
    }
    //Get content to array
    $content = $this->getContentAsArray($request);

    //Convert the array into an object and add the mandatory values retrieved from the database
    $messageObject = $this->buildMessageObject(RequestType::DECLARE_EXPORT_ENTITY, $content, $this->getAuthenticatedUser($request));

    //First Persist object to Database, before sending it to the queue
    $this->persist($messageObject, RequestType::DECLARE_EXPORT_ENTITY);

    //Send it to the queue and persist/update any changed state to the database
    $this->sendMessageObjectToQueue($messageObject, RequestType::DECLARE_EXPORT_ENTITY, RequestType::DECLARE_EXPORT);

    return new JsonResponse($messageObject, 200);
  }

  /**
   * Update existing DeclareExport request
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
   *   description = "Update a DeclareExport request",
   *   input = "AppBundle\Entity\DeclareExport",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareExportRepository")
   * @Method("PUT")
   */
  public function updateExport(Request $request, $Id) {
    //Validate uln/pedigree code
    if(!$this->isUlnOrPedigreeCodeValid($request)) {
      return new JsonResponse(array("error_code" => 428, "error_message"=>"Given Uln & Country code is invalid, it is not registered to a known Tag"), 428);
    }

    //Convert the array into an object and add the mandatory values retrieved from the database
    $declareExportUpdate = $this->buildMessageObject(RequestType::DECLARE_EXPORT_ENTITY,
      $this->getContentAsArray($request), $this->getAuthenticatedUser($request));

    $entityManager = $this->getDoctrine()->getEntityManager()->getRepository(Constant::DECLARE_EXPORT_REPOSITORY);
    $declareExport = $entityManager->updateDeclareExportMessage($declareExportUpdate, $Id);

    if($declareExport == null) {
      return new JsonResponse(array("message"=>"No DeclareExport found with request_id:" . $Id), 204);
    } else {

      //First Persist object to Database, before sending it to the queue
      $this->persist($declareExport, RequestType::DECLARE_EXPORT_ENTITY);

      //Send it to the queue and persist/update any changed state to the database
      $this->sendMessageObjectToQueue($declareExport, RequestType::DECLARE_EXPORT_ENTITY, RequestType::DECLARE_EXPORT);
    }

    return new JsonResponse($declareExport, 200);
  }
}