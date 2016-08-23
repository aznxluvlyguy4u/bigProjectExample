<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Enumerator\AnimalTransferStatus;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Util\ActionLogWriter;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/api/v1/departs")
 */
class DepartAPIController extends APIController implements DepartAPIControllerInterface {

  /**
   * Get a DeclareDepart, found by it's ID.
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
   *   description = "Retrieve a DeclareDepart by given ID",
   *   output = "AppBundle\Entity\DeclareDepart"
   * )
   * @param Request $request the request object
   * @param int $Id Id of the DeclareDepart to be returned
   * @return JsonResponse
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareDepartRepository")
   * @Method("GET")
   */
  public function getDepartById(Request $request, $Id)
  {
    $location = $this->getSelectedLocation($request);
    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_DEPART_REPOSITORY);

    $depart = $repository->getDepartureByRequestId($location, $Id);

    return new JsonResponse($depart, 200);
  }


  /**
   * Retrieve either a list of all DeclareDepartures or a subset of DeclareDepartures with a given state-type:
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
   *        "description"=" DeclareDepartures to filter on",
   *        "format"="?state=state-type"
   *      }
   *   },
   *   resource = true,
   *   description = "Retrieve a a list of DeclareDepartures",
   *   output = "AppBundle\Entity\DeclareDepart"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("GET")
   */
  public function getDepartures(Request $request)
  {
    $location = $this->getSelectedLocation($request);
    $stateExists = $request->query->has(Constant::STATE_NAMESPACE);
    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_DEPART_REPOSITORY);

    if(!$stateExists) {
      $declareDepartures = $repository->getDepartures($location);

    } else if ($request->query->get(Constant::STATE_NAMESPACE) == Constant::HISTORY_NAMESPACE ) {

      $declareDepartures = new ArrayCollection();
      foreach($repository->getDepartures($location, RequestStateType::OPEN) as $depart) {
        $declareDepartures->add($depart);
      }
      foreach($repository->getDepartures($location, RequestStateType::REVOKING) as $depart) {
        $declareDepartures->add($depart);
      }
      foreach($repository->getDepartures($location, RequestStateType::FINISHED) as $depart) {
        $declareDepartures->add($depart);
      }
      
    } else { //A state parameter was given, use custom filter to find subset
      $state = $request->query->get(Constant::STATE_NAMESPACE);
      $declareDepartures = $repository->getDepartures($location, $state);
    }

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $declareDepartures), 200);
  }


  /**
   *
   * Create a new DeclareDepart Request.
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
   *   description = "Post a DeclareDepart request",
   *   input = "AppBundle\Entity\DeclareDepart",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("POST")
   */
  public function createDepart(Request $request)
  {
    $om = $this->getDoctrine()->getManager();

    $content = $this->getContentAsArray($request);
    $client = $this->getAuthenticatedUser($request);
    $loggedInUser = $this->getLoggedInUser($request);
    $location = $this->getSelectedLocation($request);

    $log = ActionLogWriter::declareDepartOrExportPost($om, $client, $loggedInUser, $location, $content);

    //Client can only depart/export own animals
    $animal = $content->get(Constant::ANIMAL_NAMESPACE);
    $isAnimalOfClient = $this->getDoctrine()->getRepository(Constant::ANIMAL_REPOSITORY)->verifyIfClientOwnsAnimal($client, $animal);

    if(!$isAnimalOfClient) {
      return new JsonResponse(array('code'=>428, "message" => "Animal doesn't belong to this account."), 428);
    }

    $isExportAnimal = $content['is_export_animal'];

    if($isExportAnimal) {
      //Convert the array into an object and add the mandatory values retrieved from the database
      $messageObject = $this->buildMessageObject(RequestType::DECLARE_EXPORT_ENTITY, $content, $client, $loggedInUser, $location);

    } else {
      //Convert the array into an object and add the mandatory values retrieved from the database
      $messageObject = $this->buildMessageObject(RequestType::DECLARE_DEPART_ENTITY, $content, $client, $loggedInUser, $location);
    }

    //Send it to the queue and persist/update any changed state to the database
    $messageArray = $this->sendMessageObjectToQueue($messageObject);

    //Reset isExportAnimal to false before persisting
    $messageObject->getAnimal()->setIsExportAnimal(false);

    //Persist object to Database
    $this->persist($messageObject);
    $this->persistAnimalTransferringStateAndFlush($messageObject->getAnimal());

    $log = ActionLogWriter::completeActionLog($om, $log);

    return new JsonResponse($messageArray, 200);
  }


  /**
   *
   * Update existing DeclareDepart Request.
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
   *   description = "Update a DeclareDepart request",
   *   input = "AppBundle\Entity\DeclareDepart",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareDepartRepository")
   * @Method("PUT")
   */
  public function updateDepart(Request $request, $Id)
  {
    $content = $this->getContentAsArray($request);

    //Client can only depart/export own animals
    $client = $this->getAuthenticatedUser($request);
    $loggedInUser = $this->getLoggedInUser($request);
    $location = $this->getSelectedLocation($request);

    //NOTE!!! Don't try to verify any animals directly. Because they will have the isDeparted=true state.
    //Verify this request using the requestId
    $animal = $content->get(Constant::ANIMAL_NAMESPACE);
    //TODO verify if Updated request had was successful or not and set RecoveryIndicator accordingly

    $isExportAnimal = $content['is_export_animal'];

    //TODO Phase 2+: Validate if declare type (export or import) from RequestId matches type read from ['is_export_animal']

    if($isExportAnimal) {
      //Convert the array into an object and add the mandatory values retrieved from the database
      $declareExportUpdate = $this->buildEditMessageObject(RequestType::DECLARE_EXPORT_ENTITY, $content, $client, $loggedInUser, $location);

//      $entityManager = $this->getDoctrine()->getEntityManager()->getRepository(Constant::DECLARE_EXPORT_REPOSITORY);
//      $messageObject = $entityManager->updateDeclareExportMessage($declareExportUpdate, $location, $Id);
dump($declareExportUpdate);die;
      if($messageObject == null) {
        return new JsonResponse(array("message"=>"No DeclareExport found with request_id: " . $Id), 204);
      }

    } else {
      //Convert the array into an object and add the mandatory values retrieved from the database
      $declareDepartUpdate = $this->buildMessageObject(RequestType::DECLARE_DEPART_ENTITY, $content, $client, $loggedInUser, $location);

      $entityManager = $this->getDoctrine()->getManager()->getRepository(Constant::DECLARE_DEPART_REPOSITORY);
      $messageObject = $entityManager->updateDeclareDepartMessage($declareDepartUpdate, $location, $Id);

      if($messageObject == null) {
        return new JsonResponse(array("message"=>"No DeclareDepart found with request_id: " . $Id), 204);
      }
    }
    //Send it to the queue and persist/update any changed state to the database
    $messageArray = $this->sendEditMessageObjectToQueue($messageObject);

    //Reset isExportAnimal to false before persisting
    $messageObject->getAnimal()->setIsExportAnimal(false);

    //First Persist object to Database, before sending it to the queue
    $this->persist($messageObject);
    $this->persistAnimalTransferringStateAndFlush($messageObject->getAnimal());

    //updating the Animal location history is done completely in the worker

    return new JsonResponse($messageArray, 200);
  }

  /**
   *
   * Get DeclareDeparts & DeclareExports which have failed last responses.
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
   *   description = "Get DeclareDeparts & DeclareExports which have failed last responses",
   *   input = "AppBundle\Entity\DeclareDeparts",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   *
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-errors")
   * @Method("GET")
   */
  public function getDepartErrors(Request $request)
  {
    $location = $this->getSelectedLocation($request);

    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_DEPART_RESPONSE_REPOSITORY);
    $declareDeparts = $repository->getDeparturesWithLastErrorResponses($location);

    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_EXPORT_RESPONSE_REPOSITORY);
    $declareExports = $repository->getExportsWithLastErrorResponses($location);

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => array('departs' => $declareDeparts, 'exports' => $declareExports)), 200);
  }


  /**
   *
   * For the history view, get DeclareDeparts & DeclareExports which have the following requestState: OPEN or REVOKING or REVOKED or FINISHED
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
   *   description = "Get DeclareDeparts & DeclareExports which have the following requestState: OPEN or REVOKING or REVOKED or FINISHED",
   *   input = "AppBundle\Entity\DeclareDepart",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   *
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-history")
   * @Method("GET")
   */
  public function getDepartHistory(Request $request)
  {
    $location = $this->getSelectedLocation($request);

    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_DEPART_RESPONSE_REPOSITORY);
    $declareDeparts = $repository->getDeparturesWithLastHistoryResponses($location);

    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_EXPORT_RESPONSE_REPOSITORY);
    $declareExports = $repository->getExportsWithLastHistoryResponses($location);

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => array('departs' => $declareDeparts, 'exports' => $declareExports)), 200);
  }
}