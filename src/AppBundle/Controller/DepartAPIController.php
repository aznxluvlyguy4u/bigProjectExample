<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
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
    $depart = $this->getDoctrine()->getRepository(Constant::DECLARE_DEPART_REPOSITORY)->findOneBy(array(Constant::REQUEST_ID_NAMESPACE=>$Id));
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
    //No explicit filter given, thus find all
    if(!$request->query->has(Constant::STATE_NAMESPACE)) {
      $declareDepartures = $this->getDoctrine()->getRepository(Constant::DECLARE_DEPART_REPOSITORY)->findAll();
    } else { //A state parameter was given, use custom filter to find subset
      $state = $request->query->get(Constant::STATE_NAMESPACE);
      $declareDepartures = $this->getDoctrine()->getRepository(Constant::DECLARE_DEPART_REPOSITORY)->findBy(array(Constant::REQUEST_STATE_NAMESPACE => $state));
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
    $messageObject = $this->buildMessageObject(RequestType::DECLARE_DEPART_ENTITY, $content, $this->getAuthenticatedUser($request));

    //First Persist object to Database, before sending it to the queue
    $this->persist($messageObject, RequestType::DECLARE_DEPART_ENTITY);

    //Send it to the queue and persist/update any changed state to the database
    $this->sendMessageObjectToQueue($messageObject, RequestType::DECLARE_DEPART_ENTITY, RequestType::DECLARE_DEPART);
    return new JsonResponse($messageObject, 200);
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
    $declareDepartUpdate = $this->buildMessageObject(RequestType::DECLARE_DEPART_ENTITY,
        $this->getContentAsArray($request), $this->getAuthenticatedUser($request));

    $entityManager = $this->getDoctrine()->getManager()->getRepository(Constant::DECLARE_DEPART_REPOSITORY);
    $declareDepart = $entityManager->updateDeclareDepartMessage($declareDepartUpdate, $Id);

    if($declareDepart == null) {
      return new JsonResponse(array("message"=>"No DeclareDepart found with request_id:" . $Id), 204);
    }

    return new JsonResponse($declareDepart, 200);
  }  
}