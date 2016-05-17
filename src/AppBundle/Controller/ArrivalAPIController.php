<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/api/v1/arrivals")
 */
class ArrivalAPIController extends APIController implements ArrivalAPIControllerInterface
{

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
  {//TODO for phase 2: read a location from the $request and find declareArrivals for that location

    $client = $this->getAuthenticatedUser($request);
    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_ARRIVAL_REPOSITORY);

    $arrival = $repository->getArrivalsById($client, $Id);

    return new JsonResponse($arrival, 200);
  }

  /**
   * Retrieve either a list of all DeclareArrivals or a subset of DeclareArrivals with a given state-type:
   * {
   *    OPEN,
   *    FINISHED,
   *    FAILED,
   *    CANCELLED,
   *    REVOKING,
   *    REVOKED
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
   * @Route("")
   * @Method("GET")
   */
  public function getArrivals(Request $request)
  { //TODO for phase 2: read a location from the $request and find declareArrivals for that location

    $client = $this->getAuthenticatedUser($request);
    $stateExists = $request->query->has(Constant::STATE_NAMESPACE);
    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_ARRIVAL_REPOSITORY);

    if(!$stateExists) {
      $declareArrivals = $repository->getArrivals($client);

    } else { //A state parameter was given, use custom filter to find subset
      $state = $request->query->get(Constant::STATE_NAMESPACE);
      $declareArrivals = $repository->getArrivals($client, $state);
    }

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $declareArrivals), 200);
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
    $validityCheckUlnOrPedigiree= $this->isUlnOrPedigreeCodeValid($request);
    $isValid = $validityCheckUlnOrPedigiree['isValid'];

    if(!$isValid) {
      $keyType = $validityCheckUlnOrPedigiree['keyType']; // uln  of pedigree
      $animalKind = $validityCheckUlnOrPedigiree['animalKind'];
      $message = $keyType . ' of ' . $animalKind . ' not found.';
      $messageArray = array('code'=>428, "message" => $message);

      return new JsonResponse($messageArray, 428);
    }

    //Get content to array
    $content = $this->getContentAsArray($request);

    $isImportAnimal = $content->get('is_import_animal');

    if($isImportAnimal) {
      //Convert the array into an object and add the mandatory values retrieved from the database
      $messageObject = $this->buildMessageObject(RequestType::DECLARE_IMPORT_ENTITY, $content, $this->getAuthenticatedUser($request));

      //First Persist object to Database, before sending it to the queue
      $this->persist($messageObject, RequestType::DECLARE_IMPORT_ENTITY);

      //Send it to the queue and persist/update any changed state to the database
      $this->sendMessageObjectToQueue($messageObject, RequestType::DECLARE_IMPORT_ENTITY, RequestType::DECLARE_IMPORT);

    } else {
      //Convert the array into an object and add the mandatory values retrieved from the database
      $messageObject = $this->buildMessageObject(RequestType::DECLARE_ARRIVAL_ENTITY, $content, $this->getAuthenticatedUser($request));

      //First Persist object to Database, before sending it to the queue
      $this->persist($messageObject, RequestType::DECLARE_ARRIVAL_ENTITY);

      //Send it to the queue and persist/update any changed state to the database
      $this->sendMessageObjectToQueue($messageObject, RequestType::DECLARE_ARRIVAL_ENTITY, RequestType::DECLARE_ARRIVAL);
    }

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
   * @Route("/update")
   * @Method("PUT")
   */
  public function updateArrival(Request $request) {

    //Valitidy check
    $content = $this->getContentAsArray($request);
    $requestId = $content->get('request_id');

      if(array_key_exists("uln_country_code", $content['animal']) &&
          array_key_exists("uln_number", $content['animal']) &&
          array_key_exists("pedigree_country_code", $content['animal']) &&
          array_key_exists("pedigree_number", $content['animal'])) {

          $ulnSize = sizeof($content['animal']['uln_country_code']) + sizeof($content['animal']['uln_number']);
          $pedigreeSize = sizeof($content['animal']['pedigree_country_code']) + sizeof($content['animal']['pedigree_number']);

          if ($ulnSize > 0 && $pedigreeSize > 0) {
              return new JsonResponse(array('code' => 428, "message" => "fill in either pedigree or uln, not both"), 428);
          }
      }

    //Validity check
    $validityCheckUlnOrPedigiree = $this->isUlnOrPedigreeCodeValid($request);
    $isValid = $validityCheckUlnOrPedigiree['isValid'];

    if(!$isValid) {
      $keyType = $validityCheckUlnOrPedigiree['keyType']; // uln  of pedigree
      $animalKind = $validityCheckUlnOrPedigiree['animalKind'];
      $message = $keyType . ' of ' . $animalKind . ' not found.';
      $messageArray = array('code'=>428, "message" => $message);

      return new JsonResponse($messageArray, 428);
    }

    $isImportAnimal = $content->get('is_import_animal');

    if($isImportAnimal) {
      //Convert the array into an object and add the mandatory values retrieved from the database
      $declareImportUpdate = $this->buildMessageObject(RequestType::DECLARE_IMPORT_ENTITY,
          $this->getContentAsArray($request), $this->getAuthenticatedUser($request));

      $entityManager = $this->getDoctrine()->getEntityManager()->getRepository(Constant::DECLARE_IMPORT_REPOSITORY);
      $declareImport = $entityManager->updateDeclareImportMessage($declareImportUpdate, $requestId);

      if($declareImport == null) {
        return new JsonResponse(array("message"=>"No DeclareImport found with request_id:" . $requestId), 204);
      }

      //First Persist object to Database, before sending it to the queue
      $this->persist($declareImport, RequestType::DECLARE_IMPORT_ENTITY);

      //Send it to the queue and persist/update any changed state to the database
      $this->sendMessageObjectToQueue($declareImport, RequestType::DECLARE_IMPORT_ENTITY, RequestType::DECLARE_IMPORT);

      return new JsonResponse($declareImport, 200);

    } else {
      //Convert the array into an object and add the mandatory values retrieved from the database
      $declareArrivalUpdate = $this->buildMessageObject(RequestType::DECLARE_ARRIVAL_ENTITY,
          $this->getContentAsArray($request), $this->getAuthenticatedUser($request));

      $entityManager = $this->getDoctrine()->getManager()->getRepository(Constant::DECLARE_ARRIVAL_REPOSITORY);
      $declareArrival = $entityManager->updateDeclareArrivalMessage($declareArrivalUpdate, $requestId);

      if($declareArrival == null) {
        return new JsonResponse(array("message"=>"No DeclareArrival found with request_id:" . $requestId), 204);
      }

      //First Persist object to Database, before sending it to the queue
      $this->persist($declareArrival, RequestType::DECLARE_ARRIVAL_ENTITY);

      //Send it to the queue and persist/update any changed state to the database
      $this->sendMessageObjectToQueue($declareArrival, RequestType::DECLARE_ARRIVAL_ENTITY, RequestType::DECLARE_ARRIVAL);

      return new JsonResponse($declareArrival, 200);
    }
  }


  /**
   * Hide an error a user does not want to see anymore,
   * by updating the existing DeclareArrivalResponse's isRemovedByUser to true.
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
   * @Route("/error")
   * @Method("PUT")
   */
  public function updateArrivalError(Request $request) {
    $content = $this->getContentAsArray($request);
    $messageNumber = $content->get("message_number");

    if($messageNumber != null) {

      $client = $this->getAuthenticatedUser($request);
      $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_ARRIVAL_RESPONSE_REPOSITORY);

      $response = $repository->getArrivalResponseByMessageNumber($client, $messageNumber);

      $response->setIsRemovedByUser($content['is_removed_by_user']);

      //First Persist object to Database, before sending it to the queue
      $this->persist($response, RequestType::DECLARE_ARRIVAL_RESPONSE_ENTITY);

      return new JsonResponse(array("code"=>200, "message"=>"saved"), 200);
    }

    return new JsonResponse(array('code' => 428, "message" => "fill in message number"), 428);
  }
}