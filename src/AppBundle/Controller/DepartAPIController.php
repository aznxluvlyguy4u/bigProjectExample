<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
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
    //TODO for phase 2: read a location from the $request and find declareExports for that location
    $client = $this->getAuthenticatedUser($request);
    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_DEPART_REPOSITORY);

    $depart = $repository->getDepartureByRequestId($client, $Id);

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
    //TODO for phase 2: read a location from the $request and find declareDepartures for that location
    $client = $this->getAuthenticatedUser($request);
    $stateExists = $request->query->has(Constant::STATE_NAMESPACE);
    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_DEPART_REPOSITORY);

    if(!$stateExists) {
      $declareDepartures = $repository->getDepartures($client);

    } else if ($request->query->get(Constant::STATE_NAMESPACE) == Constant::HISTORY_NAMESPACE ) {

      $declareDeparts = new ArrayCollection();
      //TODO Front-end cannot accept messages without animal ULN/Pedigree
//      foreach($repository->getDeparts($client, RequestStateType::OPEN) as $depart) {
//        $declareDeparts->add($depart);
//      }
      foreach($repository->getDepartures($client, RequestStateType::REVOKING) as $depart) {
        $declareDeparts->add($depart);
      }
      foreach($repository->getDepartures($client, RequestStateType::FINISHED) as $depart) {
        $declareDeparts->add($depart);
      }
      
    } else { //A state parameter was given, use custom filter to find subset
      $state = $request->query->get(Constant::STATE_NAMESPACE);
      $declareDepartures = $repository->getDepartures($client, $state);
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
    $content = $this->getContentAsArray($request);

    //Client can only depart/export own animals
    $client = $this->getAuthenticatedUser($request);
    $animal = $content->get(Constant::ANIMAL_NAMESPACE);
    $isAnimalOfClient = $this->getDoctrine()->getRepository(Constant::ANIMAL_REPOSITORY)->verifyIfClientOwnsAnimal($client, $animal);

    if(!$isAnimalOfClient) {
      return new JsonResponse(array('code'=>428, "message" => "Animal doesn't belong to this account."), 428);
    }

    $isExportAnimal = $animal['is_export_animal'];

    if($isExportAnimal) {
      //Convert the array into an object and add the mandatory values retrieved from the database
      $messageObject = $this->buildMessageObject(RequestType::DECLARE_EXPORT_ENTITY, $content, $this->getAuthenticatedUser($request));

    } else {
      //Convert the array into an object and add the mandatory values retrieved from the database
      $messageObject = $this->buildMessageObject(RequestType::DECLARE_DEPART_ENTITY, $content, $this->getAuthenticatedUser($request));
    }

    //Send it to the queue and persist/update any changed state to the database
    $messageArray = $this->sendMessageObjectToQueue($messageObject);

    //Persist object to Database
    $this->persist($messageObject);

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
    $animal = $content->get(Constant::ANIMAL_NAMESPACE);
    $isAnimalOfClient = $this->getDoctrine()->getRepository(Constant::ANIMAL_REPOSITORY)->verifyIfClientOwnsAnimal($client, $animal);

    if(!$isAnimalOfClient) {
      return new JsonResponse(array('code'=>428, "message" => "Animal doesn't belong to this account."), 428);
    }

    $isExportAnimal = $animal['is_export_animal'];

    if($isExportAnimal) {
      //Convert the array into an object and add the mandatory values retrieved from the database
      $declareExportUpdate = $this->buildMessageObject(RequestType::DECLARE_EXPORT_ENTITY,
          $this->getContentAsArray($request), $this->getAuthenticatedUser($request));

      $entityManager = $this->getDoctrine()->getEntityManager()->getRepository(Constant::DECLARE_EXPORT_REPOSITORY);
      $messageObject = $entityManager->updateDeclareExportMessage($declareExportUpdate, $Id);

      if($messageObject == null) {
        return new JsonResponse(array("message"=>"No DeclareExport found with request_id: " . $Id), 204);
      }

    } else {
      //Convert the array into an object and add the mandatory values retrieved from the database
      $declareDepartUpdate = $this->buildMessageObject(RequestType::DECLARE_DEPART_ENTITY,
          $this->getContentAsArray($request), $this->getAuthenticatedUser($request));

      $entityManager = $this->getDoctrine()->getManager()->getRepository(Constant::DECLARE_DEPART_REPOSITORY);
      $messageObject = $entityManager->updateDeclareDepartMessage($declareDepartUpdate, $Id);

      if($messageObject == null) {
        return new JsonResponse(array("message"=>"No DeclareDepart found with request_id: " . $Id), 204);
      }
    }

    //First Persist object to Database, before sending it to the queue
    $this->persist($messageObject);

    //Send it to the queue and persist/update any changed state to the database
    $messageArray = $this->sendEditMessageObjectToQueue($messageObject);

    return new JsonResponse($messageArray, 200);
  }  
}