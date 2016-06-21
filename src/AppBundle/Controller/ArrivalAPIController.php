<?php

namespace AppBundle\Controller;

use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Output\DeclareArrivalOutput;
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
    $client = $this->getAuthenticatedUser($request);
    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_ARRIVAL_REPOSITORY);

    $arrival = $repository->getArrivalByRequestId($client, $Id);

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
    $client = $this->getAuthenticatedUser($request);
    $stateExists = $request->query->has(Constant::STATE_NAMESPACE);
    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_ARRIVAL_REPOSITORY);

    if(!$stateExists) {
      $declareArrivals = $repository->getArrivals($client);

    } else if ($request->query->get(Constant::STATE_NAMESPACE) == Constant::HISTORY_NAMESPACE ) {

      $declareArrivals = new ArrayCollection();
      foreach($repository->getArrivals($client, RequestStateType::OPEN) as $arrival) {
        $declareArrivals->add($arrival);
      }
      foreach($repository->getArrivals($client, RequestStateType::REVOKING) as $arrival) {
        $declareArrivals->add($arrival);
      }
      foreach($repository->getArrivals($client, RequestStateType::FINISHED) as $arrival) {
        $declareArrivals->add($arrival);
      }

    } else { //A state parameter was given, use custom filter to find subset
      $state = $request->query->get(Constant::STATE_NAMESPACE);
      $declareArrivals = $repository->getArrivals($client, $state);
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

    //Only verify if pedigree exists in our database. Unknown ULNs are allowed
    $verification = $this->verifyOnlyPedigreeCodeInAnimal($content->get(Constant::ANIMAL_NAMESPACE));
    if(!$verification->get('isValid')){
      return new JsonResponse(array('code'=>428,
                               "pedigree" => $verification->get(Constant::PEDIGREE_NAMESPACE),
                                "message" => "PEDIGREE VALUE IS NOT REGISTERED WITH NSFO"), 428);
    }

    $client = $this->getAuthenticatedUser($request);
    $repository = $this->getDoctrine()->getRepository(Constant::ANIMAL_REPOSITORY);

    $isImportAnimal = $content->get('is_import_animal');

    //Convert the array into an object and add the mandatory values retrieved from the database
    if($isImportAnimal) {
      //TODO Phase 2: Filter between non-EU countries and EU countries. At the moment we only process sheep from EU countries
      $messageObject = $this->buildMessageObject(RequestType::DECLARE_IMPORT_ENTITY, $content, $client);

    } else {
      $messageObject = $this->buildMessageObject(RequestType::DECLARE_ARRIVAL_ENTITY, $content, $client);
    }

    //Send it to the queue and persist/update any changed state to the database
    $messageArray = $this->sendMessageObjectToQueue($messageObject);
    $messageObject->setAnimal(null);

    //Persist message without animal. That is done after a successful response
    $this->persist($messageObject);

    //Persist HealthStatus
    $this->getDoctrine()->getManager()->persist($messageObject->getLocation()->getHealths()->last());
    $this->getDoctrine()->getManager()->flush();

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

    //verify requestId for arrivals
    $messageObject = $this->getDoctrine()->getRepository(Constant::DECLARE_ARRIVAL_REPOSITORY)->getArrivalByRequestId($client, $requestId);

    if($messageObject == null) { //verify requestId for imports
      $messageObject = $this->getDoctrine()->getRepository(Constant::DECLARE_IMPORT_REPOSITORY)->getImportByRequestId($client, $requestId);
    }

    if($messageObject == null) {
      $errorMessage = "No DeclareArrival or DeclareImport found with request_id: " . $requestId;
      return new JsonResponse(array('code'=>428, "message" => $errorMessage), 428);
    }

    $isImportAnimal = $messageObject->getIsImportAnimal();

    if($isImportAnimal) {
      //Convert the array into an object and add the mandatory values retrieved from the database
      $messageObject = $this->buildEditMessageObject(RequestType::DECLARE_IMPORT_ENTITY,
          $content, $this->getAuthenticatedUser($request));

    } else {
      //Convert the array into an object and add the mandatory values retrieved from the database
      $messageObject = $this->buildEditMessageObject(RequestType::DECLARE_ARRIVAL_ENTITY,
          $content, $this->getAuthenticatedUser($request));
    }

    //Send it to the queue and persist/update any changed requestState to the database
    $messageArray = $this->sendEditMessageObjectToQueue($messageObject);

    //Persist the update
    $this->persist($messageObject);

    //Persist HealthStatus
    $this->getDoctrine()->getManager()->persist($messageObject->getLocation()->getHealths()->last());
    $this->getDoctrine()->getManager()->flush();

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
    $client = $this->getAuthenticatedUser($request);

    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_ARRIVAL_RESPONSE_REPOSITORY);
    $declareArrivals = $repository->getArrivalsWithLastErrorResponses($client);

    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_IMPORT_RESPONSE_REPOSITORY);
    $declareImports = $repository->getImportsWithLastErrorResponses($client);

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
    $client = $this->getAuthenticatedUser($request);

    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_ARRIVAL_RESPONSE_REPOSITORY);
    $declareArrivals = $repository->getArrivalsWithLastHistoryResponses($client);

    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_IMPORT_RESPONSE_REPOSITORY);
    $declareImports = $repository->getImportsWithLastHistoryResponses($client);

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => array('arrivals' => $declareArrivals, 'imports' => $declareImports)), 200);
  }

}