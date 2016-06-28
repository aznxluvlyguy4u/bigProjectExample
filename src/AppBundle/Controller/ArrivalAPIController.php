<?php

namespace AppBundle\Controller;

use AppBundle\Component\LocationHealthMessageBuilder;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareImport;
use AppBundle\Output\DeclareArrivalOutput;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Util\HealthChecker;
use AppBundle\Util\LocationHealthUpdater;
use AppBundle\Validation\TagValidator;
use AppBundle\Validation\UbnValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use JMS\Serializer\SerializationContext;

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
  {
    $location = $this->getSelectedLocation($request);
    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_ARRIVAL_REPOSITORY);

    $arrival = $repository->getArrivalByRequestId($location, $Id);

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
  {
    $location = $this->getSelectedLocation($request);
    $stateExists = $request->query->has(Constant::STATE_NAMESPACE);
    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_ARRIVAL_REPOSITORY);

    if(!$stateExists) {
      $declareArrivals = $repository->getArrivals($location);

    } else if ($request->query->get(Constant::STATE_NAMESPACE) == Constant::HISTORY_NAMESPACE ) {

      $declareArrivals = new ArrayCollection();
      foreach($repository->getArrivals($location, RequestStateType::OPEN) as $arrival) {
        $declareArrivals->add($arrival);
      }
      foreach($repository->getArrivals($location, RequestStateType::REVOKING) as $arrival) {
        $declareArrivals->add($arrival);
      }
      foreach($repository->getArrivals($location, RequestStateType::FINISHED) as $arrival) {
        $declareArrivals->add($arrival);
      }

    } else { //A state parameter was given, use custom filter to find subset
      $state = $request->query->get(Constant::STATE_NAMESPACE);
      $declareArrivals = $repository->getArrivals($location, $state);
    }

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $declareArrivals), 200);
  }


  /**
   * Create a new DeclareArrival or DeclareImport request
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
   *   description = "Post a DeclareArrival or DeclareImport request",
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
    $content = $this->getContentAsArray($request);
    $client = $this->getAuthenticatedUser($request);
    $location = $this->getSelectedLocation($request);

    //Only verify if pedigree exists in our database. Unknown ULNs are allowed
    $ulnVerification = $this->verifyOnlyPedigreeCodeInAnimal($content->get(Constant::ANIMAL_NAMESPACE));
    if(!$ulnVerification->get('isValid')){
      return new JsonResponse(array('code'=>428,
                               "pedigree" => $ulnVerification->get(Constant::PEDIGREE_NAMESPACE),
                                "message" => "PEDIGREE VALUE IS NOT REGISTERED WITH NSFO"), 428);
    }

    $isImportAnimal = $content->get(Constant::IS_IMPORT_ANIMAL);

    //Convert the array into an object and add the mandatory values retrieved from the database
    if($isImportAnimal) { //DeclareImport
      //Validate if ulnNumber matches that of an unassigned Tag in the tag collection of the client
      $tagValidator = new TagValidator($this->getDoctrine()->getManager(), $client, $content);
      if($tagValidator->getIsTagCollectionEmpty() || !$tagValidator->getIsTagValid() || $tagValidator->getIsInputEmpty()) {
        return $tagValidator->createImportJsonErrorResponse();
      }

      //TODO Phase 2: Filter between non-EU countries and EU countries. At the moment we only process sheep from EU countries
      $messageObject = $this->buildMessageObject(RequestType::DECLARE_IMPORT_ENTITY, $content, $client, $location);

    } else { //DeclareArrival

      //TODO Validate if ubnPreviousOwner matches the ubn of the animal with the given ULN, if the animal is in our database
//      $ubnValidator = new UbnValidator($this->getDoctrine()->getManager(), $content);
//      if(!$ubnValidator->getIsUbnValid()) {
//        return $ubnValidator->createArrivalJsonErrorResponse();
//      }
      $content->set(JsonInputConstant::IS_ARRIVED_FROM_OTHER_NSFO_CLIENT, true);

      $messageObject = $this->buildMessageObject(RequestType::DECLARE_ARRIVAL_ENTITY, $content, $client, $location);
    }

    //Send it to the queue and persist/update any changed state to the database
    $messageArray = $this->sendMessageObjectToQueue($messageObject);
    $animal = $messageObject->getAnimal();
    $messageObject->setAnimal(null);

    //Persist message without animal. That is done after a successful response
    $this->persist($messageObject);
    $this->getDoctrine()->getManager()->flush();

    //Immediately update the locationHealth regardless or requestState type and persist a locationHealthMessage
    $this->getHealthService()->updateLocationHealth($messageObject);

//    return new JsonResponse(array("status"=>"sent"), 200);
    return new JsonResponse($messageArray, 200);
  }

  /**
   * Update existing DeclareArrival or DeclareImport request
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
   *   description = "Update a DeclareArrival or DeclareImport request",
   *   input = "AppBundle\Entity\DeclareArrival",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareArrivalRepository")
   * @Method("PUT")
   */
  public function updateArrival(Request $request, $Id) {

    $content = $this->getContentAsArray($request);
    $requestId = $Id;
    $content->set("request_id", $requestId);

    $client = $this->getAuthenticatedUser($request);
    $location = $this->getSelectedLocation($request);

    //verify requestId for arrivals
    $messageObject = $this->getDoctrine()->getRepository(Constant::DECLARE_ARRIVAL_REPOSITORY)->getArrivalByRequestId($location, $requestId);

    if($messageObject == null) { //verify requestId for imports
      $messageObject = $this->getDoctrine()->getRepository(Constant::DECLARE_IMPORT_REPOSITORY)->getImportByRequestId($location, $requestId);
    }

    if($messageObject == null) {
      $errorMessage = "No DeclareArrival or DeclareImport found with request_id: " . $requestId;
      return new JsonResponse(array('code'=>428, "message" => $errorMessage), 428);
    }

    $isImportAnimal = $messageObject->getIsImportAnimal();
    $isFailedMessage = $messageObject->getRequestState() == RequestStateType::FAILED;

    if($isImportAnimal) { //For DeclareImport
      //Convert the array into an object and add the mandatory values retrieved from the database
      $messageObject = $this->buildEditMessageObject(RequestType::DECLARE_IMPORT_ENTITY, $content, $client, $location);

    } else { //For DeclareArrival
      //TODO Validate if ubnPreviousOwner matches the ubn of the animal with the given ULN, if the animal is in our database
//      $ubnValidator = new UbnValidator($this->getDoctrine()->getManager(), $content, $messageObject);
//      if(!$ubnValidator->getIsUbnValid()) {
//        return $ubnValidator->createArrivalJsonErrorResponse();
//      }

      //Convert the array into an object and add the mandatory values retrieved from the database
      $messageObject = $this->buildEditMessageObject(RequestType::DECLARE_ARRIVAL_ENTITY, $content, $client, $location);
    }

    //Send it to the queue and persist/update any changed requestState to the database
    $messageArray = $this->sendEditMessageObjectToQueue($messageObject);

    //Persist the update
    $this->persist($messageObject);
    $this->getDoctrine()->getManager()->flush();


    /* LocationHealth status updates are not necessary */

    /*
     * Import: An import (POST & PUT) always leads to the same LocationHealth update.
     *
     * Arrival: Only the arrival date is editable for Animals from other NSFO clients. The ubnPreviousOwner is editable for unknown locations.
     * In both cases the health status change would be identical to the change by the original arrival.
     *
     * We do not discriminate between successful and failed requests at this moment.
     */

    //log Animal location history
    $this->getAnimalLocationHistoryService()->logAnimalResidenceInEdit($messageObject);

    return new JsonResponse($messageArray, 200);
  }


  /**
   *
   * Get DeclareArrivals & DeclareImports which have failed last responses.
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
   *   description = "Get DeclareArrivals & DeclareImports which have failed last responses",
   *   input = "AppBundle\Entity\DeclareArrival",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   *
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-errors")
   * @Method("GET")
   */
  public function getArrivalErrors(Request $request)
  {
    $location = $this->getSelectedLocation($request);

    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_ARRIVAL_RESPONSE_REPOSITORY);
    $declareArrivals = $repository->getArrivalsWithLastErrorResponses($location);

    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_IMPORT_RESPONSE_REPOSITORY);
    $declareImports = $repository->getImportsWithLastErrorResponses($location);

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => array('arrivals' => $declareArrivals, 'imports' => $declareImports)), 200);
  }


  /**
   *
   * For the history view, get DeclareArrivals & DeclareImports which have the following requestState: OPEN or REVOKING or REVOKED or FINISHED.
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
   *   description = "Get DeclareArrivals & DeclareImports which have the following requestState: OPEN or REVOKING or REVOKED or FINISHED",
   *   input = "AppBundle\Entity\DeclareArrival",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   *
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-history")
   * @Method("GET")
   */
  public function getArrivalHistory(Request $request)
  {
    $location = $this->getSelectedLocation($request);

    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_ARRIVAL_RESPONSE_REPOSITORY);
    $declareArrivals = $repository->getArrivalsWithLastHistoryResponses($location);

    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_IMPORT_RESPONSE_REPOSITORY);
    $declareImports = $repository->getImportsWithLastHistoryResponses($location);

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => array('arrivals' => $declareArrivals, 'imports' => $declareImports)), 200);
  }

}