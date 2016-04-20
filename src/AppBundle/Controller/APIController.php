<?php

namespace AppBundle\Controller;

use AppBundle\Component\RequestMessageBuilder;
use AppBundle\Entity\Client;
use AppBundle\Entity\Location;
use AppBundle\Component\MessageBuilderBase;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Validator;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Component\HttpFoundation\JsonResponse;


/**
 * Class APIController
 * @package AppBundle\Controller
 */
class APIController extends Controller
{
  const AUTHORIZATION_HEADER_NAMESPACE = 'AccessToken';

  /**
   * @var RequestMessageBuilder
   */
  private $requestMessageBuilder;

  /**
   * @var
   */
  private $serializer;

  /**
   * @var \AppBundle\Service\AWSQueueService
   */
  private $queueService;

  /**
   * @var \AppBundle\Service\EntityGetter
   */
  private $entityGetter;



  protected function getEntityGetter()
  {
    if($this->entityGetter == null){
      $this->entityGetter = $this->get('app.doctrine.entitygetter');
    }

    return $this->entityGetter;
  }

  /**
   * @return
   */
  protected function getSerializer()
  {
    if($this->serializer == null){
      $this->serializer = $this->get('app.serializer.decrappifier');
    }

    return $this->serializer;
  }

  protected function getRequestMessageBuilder()
  {
    if($this->requestMessageBuilder == null) {
      $serializer = $this->getSerializer();
      $em = $this->getDoctrine()->getEntityManager();
      $this->requestMessageBuilder = new RequestMessageBuilder($em, $serializer);
    }

    return $this->requestMessageBuilder;
  }

  /**
   * @return \AppBundle\Service\AWSQueueService
   */
  protected function getQueueService(){
    if($this->queueService == null){
      $this->queueService = $this->get('app.aws.queueservice');
    }

    return $this->queueService;
  }



  protected function getContentAsArray(Request $request)
  {
    $content = $request->getContent();

    if(empty($content)){
      throw new BadRequestHttpException("Content is empty");
    }

    return new ArrayCollection(json_decode($content, true));
  }

  public function getToken(Request $request)
  {
    //Get auth header to read token
    if(!$request->headers->has($this::AUTHORIZATION_HEADER_NAMESPACE)) {
      return new JsonResponse(array("errorCode" => 401, "errorMessage"=>"Unauthorized"), 401);
    }

    return $request->headers->get('AccessToken');
  }

  public function isTokenValid($request)
  {
    $token = $this->getToken($request);

    $em = $this->getDoctrine()->getEntityManager();
    $person = $em->getRepository('AppBundle:Person')
        ->findOneBy(array('accessToken' => $token));
    if($person == null) {
      return new JsonResponse(array("errorCode" => 403, "errorMessage"=>"Forbidden"), 403);
    }

    return $person;
  }

  protected function buildMessageObject($messageClassNameSpace, ArrayCollection $contentArray, $user)
  {
    $messageObject = $this->getRequestMessageBuilder()
        ->build($messageClassNameSpace, $contentArray, $user);

    return $messageObject;
  }

  public function persist($messageObject, $messageClassNameSpace)
  {
    //Set the string values
    $repositoryEntityNameSpace = "AppBundle:$messageClassNameSpace";

    //Persist to database
    $messageObject = $this->getDoctrine()->getRepository($repositoryEntityNameSpace)->persist($messageObject);

    return $messageObject;
  }

  //TODO It works but better reassess this function
  protected function sendMessageObjectToQueue($messageObject, $requestTypeNameSpace)
  {
    $requestId = $messageObject->getRequestId();
    $jsonMessage = $this->getSerializer()->serializeToJSON($messageObject);

    //Send serialized message to Queue
    $sendToQresult = $this->getQueueService()->send($requestId, $jsonMessage, $requestTypeNameSpace);

    //If send to Queue, failed, it needs to be resend, set state to failed
    if($sendToQresult['statusCode'] != '200') {
      $messageObject->setRequestState('failed');

      //TODO Generalize path to send to database
      //Update this state to the database
      $messageObject = $this->getDoctrine()->getRepository('AppBundle:DeclareArrival')->persist($messageObject);
    }

    return $messageObject;
  }
}