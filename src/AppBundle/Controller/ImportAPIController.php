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
    //TODO for phase 2: read a location from the $request and find declareImports for that location
    $client = $this->getAuthenticatedUser($request);
    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_IMPORT_REPOSITORY);

    $import = $repository->getImportsById($client, $Id);

    return new JsonResponse($import, 200);
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
    //TODO for phase 2: read a location from the $request and find declareImports for that location
    $client = $this->getAuthenticatedUser($request);
    $stateExists = $request->query->has(Constant::STATE_NAMESPACE);
    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_IMPORT_REPOSITORY);

    if(!$stateExists) {
      $declareImports = $repository->getImports($client);

    } else { //A state parameter was given, use custom filter to find subset
      $state = $request->query->get(Constant::STATE_NAMESPACE);
      $declareImports = $repository->getImports($client, $state);
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
    $validityCheckUlnOrPedigiree= $this->isUlnOrPedigreeCodeValid($request);
    $isValid = $validityCheckUlnOrPedigiree['isValid'];

    if(!$isValid) {
      $keyType = $validityCheckUlnOrPedigiree['keyType']; // uln  of pedigree
      $animalKind = $validityCheckUlnOrPedigiree['animalKind'];
      $message = $keyType . ' of ' . $animalKind . ' not found.';
      $messageArray = array('code'=>428, "message" => $message);

      return new JsonResponse($messageArray, 428);
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
  public function updateImport(Request $request, $Id) {
    $validityCheckUlnOrPedigiree= $this->isUlnOrPedigreeCodeValid($request);
    $isValid = $validityCheckUlnOrPedigiree['isValid'];

    if(!$isValid) {
      $keyType = $validityCheckUlnOrPedigiree['keyType']; // uln  of pedigree
      $animalKind = $validityCheckUlnOrPedigiree['animalKind'];
      $message = $keyType . ' of ' . $animalKind . ' not found.';
      $messageArray = array('code'=>428, "message" => $message);

      return new JsonResponse($messageArray, 428);
    }

    //Convert the array into an object and add the mandatory values retrieved from the database
    $declareImportUpdate = $this->buildMessageObject(RequestType::DECLARE_IMPORT_ENTITY,
      $this->getContentAsArray($request), $this->getAuthenticatedUser($request));

    $entityManager = $this->getDoctrine()->getEntityManager()->getRepository(Constant::DECLARE_IMPORT_REPOSITORY);
    $declareImport = $entityManager->updateDeclareImportMessage($declareImportUpdate, $Id);

    if($declareImport == null) {
      return new JsonResponse(array("message"=>"No DeclareImport found with request_id:" . $Id), 204);
    }

    //First Persist object to Database, before sending it to the queue
    $this->persist($declareImport, RequestType::DECLARE_IMPORT_ENTITY);

    //Send it to the queue and persist/update any changed state to the database
    $this->sendMessageObjectToQueue($declareImport, RequestType::DECLARE_IMPORT_ENTITY, RequestType::DECLARE_IMPORT);


    return new JsonResponse($declareImport, 200);
  }
}