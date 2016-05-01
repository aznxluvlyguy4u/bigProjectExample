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
class DepartAPIController extends APIController {

  /**
   *
   * Get a DeclareBirth, found by it's ID.
   *
   * @param Request $request the request object
   * @param int $Id Id of the DeclareArrival to be returned
   * @return JsonResponse
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareDepartRepository")
   * @Method("GET")
   */
  public function getDepartById(Request $request, $Id)
  {
//    $depart = $this->getDoctrine()->getRepository(Constant::DECLARE_DEPART_REPOSITORY)->findOneBy(array(Constant::REQUEST_ID_NAMESPACE=>$Id));
//    return new JsonResponse($depart, 200);
    return new JsonResponse(null);
  }

  /**
   * @var Client
   */
  private $user;
  /**
   *
   * Get a list of DeclareDeparts with a given state:{OPEN, CLOSED, DECLINED}.
   *
   *
   * @Route("/status")
   * @Method("GET")
   */
  public function getDepartByState(Request $request)
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
   * Create a DeclareDepart Request.
   *
   * @Route("")
   * @Method("POST")
   */
  public function createDepart(Request $request)
  {
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
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareArrivalRepository")
   * @Method("PUT")
   */
  public function editDepart(Request $request, $Id)
  {
    //Convert the array into an object and add the mandatory values retrieved from the database
    $declareDepartUpdate = $this->buildMessageObject(RequestType::DECLARE_DEPART_ENTITY,
        $this->getContentAsArray($request), $this->getAuthenticatedUser($request));

    $entityManager = $this->getDoctrine()
        ->getManager()
        ->getRepository(Constant::DECLARE_DEPART_REPOSITORY);
    $declareDepart = $entityManager->findOneBy(array (Constant::REQUEST_ID_NAMESPACE => $Id));

    if($declareDepart == null) {
      return new JsonResponse(array("message"=>"No DeclareDepart found with request_id: " . $Id), 204);
    }

    
    if ($declareDepartUpdate->getAnimal() != null) {
      $declareDepart->setAnimal($declareDepartUpdate->getAnimal());
    }

    if ($declareDepartUpdate->getDepartDate() != null) {
      $declareDepart->setDepartDate($declareDepartUpdate->getDepartDate());
    }

    if ($declareDepartUpdate->getLocation() != null) {
      $declareDepart->setLocation($declareDepartUpdate->getLocation());
    }

    if ($declareDepartUpdate->getTransportationCode() != null) {
      $declareDepart->setTransportationCode($declareDepartUpdate->getTransportationCode());
    }

    if ($declareDepartUpdate->getSelectionUlnCountryCode() != null) {
      $declareDepart->setSelectionUlnCountryCode($declareDepartUpdate->getSelectionUlnCountryCode());
    }

    if ($declareDepartUpdate->getSelectionUlnNumber() != null) {
      $declareDepart->setSelectionUlnNumber($declareDepartUpdate->getSelectionUlnNumber());
    }

    if($declareDepartUpdate->getUbnNewOwner() != null) {
      $declareDepart->setUbnPreviousOwner($declareDepartUpdate->getUbnNewOwner());
    }

    $declareDepart = $entityManager->update($declareDepart);

    return new JsonResponse($declareDepart, 200);
  }  
}