<?php

namespace AppBundle\Controller;

use AppBundle\Component\RequestMessageBuilder;
use AppBundle\Constant\Constant;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Validator;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class APIController
 * @package AppBundle\Controller
 */
class APIController extends Controller implements APIControllerInterface
{
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

  /**
   * @return \AppBundle\Service\EntityGetter
   */
  protected function getEntityGetter()
  {
    if($this->entityGetter == null){
      $this->entityGetter = $this->get('app.doctrine.entitygetter');
    }

    return $this->entityGetter;
  }

  /**
   * @return \AppBundle\Service\IRSerializer
   */
  protected function getSerializer()
  {
    if($this->serializer == null){
      $this->serializer = $this->get('app.serializer.ir');
    }

    return $this->serializer;
  }

  /**
   * @return RequestMessageBuilder
   */
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

  /**
   * @param Request $request
   * @return ArrayCollection
   */
  protected function getContentAsArray(Request $request)
  {
    $content = $request->getContent();

    if(empty($content)){
      throw new BadRequestHttpException("Content is empty");
    }

    return new ArrayCollection(json_decode($content, true));
  }

  /**
   * @param Request $request
   * @return JsonResponse|array|string
   */
  public function getToken(Request $request)
  {
    //Get auth header to read token
    if(!$request->headers->has(Constant::AUTHORIZATION_HEADER_NAMESPACE)) {
      return new JsonResponse(array("errorCode" => 401, "errorMessage"=>"Unauthorized"), 401);
    }

    return $request->headers->get('AccessToken');
  }

  /**
   * @param $request
   * @return JsonResponse|\AppBundle\Entity\Person|null|object
   */
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

  /**
   * @param $messageClassNameSpace
   * @param ArrayCollection $contentArray
   * @param $user
   * @return \AppBundle\Entity\RetrieveEartags|\AppBundle\Entity\RevokeDeclaration|null
   * @throws \Exception
   */
  protected function buildMessageObject($messageClassNameSpace, ArrayCollection $contentArray, $user)
  {
    $messageObject = $this->getRequestMessageBuilder()
        ->build($messageClassNameSpace, $contentArray, $user);

    return $messageObject;
  }

  /**
   * @param $messageObject
   * @param $messageClassNameSpace
   * @return mixed
   */
  public function persist($messageObject, $messageClassNameSpace)
  {
    //Set the string values
    $repositoryEntityNameSpace = "AppBundle:$messageClassNameSpace";

    //Persist to database
    $this->getDoctrine()->getRepository($repositoryEntityNameSpace)->persist($messageObject);

    return $messageObject;
  }

  /**
   * @param $messageObject
   * @param $messageClassNameSpace
   * @param $requestTypeNameSpace
   * @return mixed
   */
  //TODO It works but better reassess this function
  protected function sendMessageObjectToQueue($messageObject, $messageClassNameSpace, $requestTypeNameSpace) {
    $requestId = $messageObject->getRequestId();
    $jsonMessage = $this->getSerializer()->serializeToJSON($messageObject);

    //Send serialized message to Queue
    $sendToQresult = $this->getQueueService()
      ->send($requestId, $jsonMessage, $requestTypeNameSpace);

    //If send to Queue, failed, it needs to be resend, set state to failed
    if ($sendToQresult['statusCode'] != '200') {
      $messageObject->setRequestState('failed');

      //Update this state to the database
      $repositoryEntityNameSpace = "AppBundle:$messageClassNameSpace";
      $messageObject = $this->getDoctrine()
        ->getRepository($repositoryEntityNameSpace)
        ->persist($messageObject);
    }

    return $messageObject;
  }

  /**
   * Redirect to API docs when root is requested
   *
   * @Route("")
   * @Method("GET")
   */
  public function redirectRootToAPIDoc()
  {
    return new RedirectResponse('/api/v1/doc');
  }

  /**
   * @param Request|null $request
   * @param null $token
   * @return \AppBundle\Entity\Person|null|object
   */
  public function getAuthenticatedUser(Request $request= null, $token = null)
  {
    if($token == null) {
      $token = $request->headers->get(Constant::ACCESS_TOKEN_HEADER_NAMESPACE);
    }
    $em = $this->getDoctrine()->getEntityManager();

    return $em->getRepository('AppBundle:Person')->findOneBy(array("accessToken" => $token));
  }

  /**
   * @param Request $request
   * @return JsonResponse
   */
  public function isAccessTokenValid(Request $request)
  {
    $token = null;
    $response = null;

    //Get token header to read token value
    if($request->headers->has(Constant::ACCESS_TOKEN_HEADER_NAMESPACE)) {
      $token = $request->headers->get(Constant::ACCESS_TOKEN_HEADER_NAMESPACE);

      // A user was found with given token
      if($this->getAuthenticatedUser($request, $token) != null) {
        $response = array(
          'token_status' => 'valid',
          'token' => $token
        );

        return new JsonResponse($response, 200);
      } else { // No user found for given token
        $response = array(
          'error'=> 401,
          'errorMessage'=> 'No AccessToken provided'
        );
      }
    }

    //Mandatory AccessToken was not provided
    $response = array(
      'error'=> 401,
      'errorMessage'=> 'Mandatory AccessToken header was not provided'
    );

    return new JsonResponse($response, 401);
  }



}