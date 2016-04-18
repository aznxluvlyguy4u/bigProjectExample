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
   * @var \JMS\Serializer\Serializer
   */
  private $serializer;

  /**
   * @var string
   */
  private $jsonNamespace  = 'json';

  /**
   * @var \AppBundle\Service\AWSQueueService
   */
  private $queueService;

  /**
   * @return \JMS\Serializer\Serializer
   */
  private function getSerializer()
  {
    if($this->serializer == null){
      $this->serializer = $this->get('jms_serializer');
    }

    return $this->serializer;
  }

  protected function getRequestMessageBuilder()
  {
    $this->requestMessageBuilder = new RequestMessageBuilder($this->getSerializer());
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
   * @param $object
   * @return mixed|string
   */
  protected function serializeToJSON($object)
  {
    return $this->getSerializer()->serialize($object, $this->jsonNamespace);
  }

  /**
   * @param $json
   * @param $entity
   * @return array|\JMS\Serializer\scalar|mixed|object
   */
  protected function deserializeToObject($json, $entity)
  {
    return $this->getSerializer()->deserialize($json, $entity, $this->jsonNamespace);
  }

  /**
   * Generate a psuedo random requestId of MAX length 20
   *
   * @return string
   */
  protected function getNewRequestId()
  {
    $maxLengthRequestId = 20;
    return join('', array_map(function($value) { return $value == 1 ? mt_rand(1, 9) :
      mt_rand(0, 9); }, range(1, $maxLengthRequestId)));
  }

  protected function getContentAsArray($request)
  {
    $content = $request->getContent();

    if(empty($content)){
      throw new BadRequestHttpException("Content is empty");
    }

    return new ArrayCollection(json_decode($content, true));
  }

  public function persist($jsonMessage, $messageClassNameSpace)
  {
    //Set the string values
    $messageClassPathNameSpace = "AppBundle\Entity\\$messageClassNameSpace";
    $repositoryEntityNameSpace = "AppBundle:$messageClassNameSpace";

    //Deserialize to Object
    $messageObject = $this->deserializeToObject($jsonMessage, $messageClassPathNameSpace);

    //Persist to database
    $messageObject = $this->getDoctrine()->getRepository($repositoryEntityNameSpace)->persist($messageObject);

    return $messageObject;
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

  public function getPerson(Request $request)
  {
    return $this->isTokenValid($request);
  }

}