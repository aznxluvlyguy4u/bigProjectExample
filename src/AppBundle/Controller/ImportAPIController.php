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
 * @Route("/api/v1/imports")
 */
class ImportAPIController extends APIController implements ImportAPIControllerInterface {

  /**
   * Retrieve a DeclareImport, found by it's ID.
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
   *   description = "Retrieve a DeclareImport by given ID",
   *   output = "AppBundle\Entity\DeclareArrival"
   * )
   * @param Request $request the request object
   * @param int $Id Id of the DeclareImport to be returned
   * @return JsonResponse
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareImportRepository")
   * @Method("GET")
   */
  public function getImportById(Request $request, $Id) {
    $arrival = $this->getDoctrine()->getRepository(Constant::DECLARE_IMPORT_REPOSITORY)->findOneBy(array(Constant::REQUEST_ID_NAMESPACE=>$Id));
    return new JsonResponse($arrival, 200);
  }

  /**
   * Retrieve either a list of all DeclareImports or a subset of DeclareImports with a given state-type:
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
   *        "description"=" DeclareImports to filter on",
   *        "format"="?state=state-type"
   *      }
   *   },
   *   resource = true,
   *   description = "Retrieve a a list of DeclareImports",
   *   output = "AppBundle\Entity\DeclareImport"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("GET")
   */
  public function getImports(Request $request) {
    //No explicit filter given, thus find all
    if(!$request->query->has(Constant::STATE_NAMESPACE)) {
      $declareImports = $this->getDoctrine()->getRepository(Constant::DECLARE_IMPORT_REPOSITORY)->findAll();
    } else { //A state parameter was given, use custom filter to find subset
      $state = $request->query->get(Constant::STATE_NAMESPACE);
      $declareImports = $this->getDoctrine()->getRepository(Constant::DECLARE_IMPORT_REPOSITORY)->findBy(array(Constant::REQUEST_STATE_NAMESPACE => $state));
    }

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $declareImports), 200);
  }

  /**
   * Create a new DeclareImport request
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
   *   description = "Post a DeclareImport request",
   *   input = "AppBundle\Entity\DeclareImport",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("POST")
   */
  public function createImport(Request $request) {
    //Validate uln/pedigree code
    if(!$this->isUlnOrPedigreeCodeValid($request)) {
      return new JsonResponse(Constant::RESPONSE_ULN_NOT_FOUND, Constant::RESPONSE_ULN_NOT_FOUND[Constant::CODE_NAMESPACE]);
    }

    //Convert front-end message into an array
    //Get content to array
    $content = $this->getContentAsArray($request);

    //Convert the array into an object and add the mandatory values retrieved from the database
    $messageObject = $this->buildMessageObject(RequestType::DECLARE_IMPORT_ENTITY, $content, $this->getAuthenticatedUser($request));

    //First Persist object to Database, before sending it to the queue
    $this->persist($messageObject, RequestType::DECLARE_IMPORT_ENTITY);

    //Send it to the queue and persist/update any changed state to the database
    $this->sendMessageObjectToQueue($messageObject, RequestType::DECLARE_IMPORT_ENTITY, RequestType::DECLARE_IMPORT);

    return new JsonResponse($messageObject, 200);
  }

  /**
   * Update existing DeclareImport request
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
   *   description = "Update a DeclareImport request",
   *   input = "AppBundle\Entity\DeclareImport",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareImportRepository")
   * @Method("PUT")
   */
  public function editImport(Request $request, $Id) {
    //Validate uln/pedigree code
    if(!$this->isUlnOrPedigreeCodeValid($request)) {
      return new JsonResponse(Constant::RESPONSE_ULN_NOT_FOUND, Constant::RESPONSE_ULN_NOT_FOUND[Constant::CODE_NAMESPACE]);
    }

    //Convert the array into an object and add the mandatory values retrieved from the database
    $declareImportUpdate = $this->buildMessageObject(RequestType::DECLARE_IMPORT_ENTITY,
      $this->getContentAsArray($request), $this->getAuthenticatedUser($request));

    $entityManager = $this->getDoctrine()
      ->getEntityManager()
      ->getRepository(Constant::DECLARE_IMPORT_REPOSITORY);
    $declareImport = $entityManager->findOneBy(array (Constant::REQUEST_ID_NAMESPACE => $Id));

    if($declareImport == null) {
      return new JsonResponse(array("message"=>"No DeclareImport found with request_id:" . $Id), 204);
    }

    if ($declareImportUpdate->getAnimal() != null) {
      $declareImport->setAnimal($declareImportUpdate->getAnimal());
    }

    if ($declareImportUpdate->getImportDate() != null) {
      $declareImport->setImportDate($declareImportUpdate->getImportDate());
    }

    if ($declareImportUpdate->getLocation() != null) {
      $declareImport->setLocation($declareImportUpdate->getLocation());
    }

    if ($declareImportUpdate->getImportAnimal() != null) {
      $declareImport->setImportAnimal($declareImportUpdate->getImportAnimal());
    }

    if($declareImportUpdate->getUbnPreviousOwner() != null) {
      $declareImport->setUbnPreviousOwner($declareImportUpdate->getUbnPreviousOwner());
    }

    $declareImport = $entityManager->update($declareImport);

    return new JsonResponse($declareImport, 200);
  }
}