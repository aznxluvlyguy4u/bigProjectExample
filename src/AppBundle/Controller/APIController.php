<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Client;
use AppBundle\Entity\Location;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Validator;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Component\HttpFoundation\JsonResponse;


/**
 * Class APIController
 * @package AppBundle\Controller
 */
class APIController extends Controller
{
  const AUTHORIZATION_HEADER_NAMESPACE = 'Authorization';
  const ACCESS_TOKEN_HEADER_NAMESPACE = 'AccessToken';

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

  protected function getContentAsArray(Request $request)
  {
    $content = $request->getContent();

    if(empty($content)){
      throw new BadRequestHttpException("Content is empty");
    }

    return new ArrayCollection(json_decode($content, true));
  }

  public function loginUser($request)
  {
    //Get auth header to read token
    if(!$request->headers->has($this::AUTHORIZATION_HEADER_NAMESPACE)) {
      return new JsonResponse(array("errorCode" => 401, "errorMessage"=>"Unauthorized"), 401);
    }

    $credentials = $request->headers->get($this::AUTHORIZATION_HEADER_NAMESPACE);
    $credentials = str_replace('Basic ', '', $credentials);
    $credentials = base64_decode($credentials);

    list($key, $secret) = explode(":", $credentials);

   /* {
        "ubn":"123",
        "email_address": "",
        "postal_code":"1234AB",
        "home_number":"12"
    }*/

    //Get content to array
    $content = $this->getContentAsArray($request);


    $ubn = $content['ubn'];
    $emailAddress = $content['email_address'];
    $postal_code = $content['postal_code'];
    $home_number = $content['home_number'];

    $client = new Client();
    $client->setPassword($secret);
    $client->setEmailAddress($emailAddress);
    $client->setRelationNumberKeeper("123233");

    $encoder = $this->container->get('security.password_encoder');
    $encoded = $encoder->encodePassword($user, $plainPassword);

    $user->setPassword($encoded);

    return $key . $secret;
    $em = $this->getDoctrine()->getEntityManager();
    $user = $em->getRepository('AppBundle:Person')->findBy(array("emailAddress"=>$key,"password"=>$secret));
//
//    if($user == null) {
//      return new JsonResponse(array("errorCode" => 403, "errorMessage"=>"Forbidden"), 403);
//    }
//
//    $accessToken = null;//$user->getAccessToken();
//    return $accessToken;
  }

  public function isTokenValid($request)
  {
    //Get auth header to read token
    if(!$request->headers->has($this::ACCESS_TOKEN_HEADER_NAMESPACE)) {
      return new JsonResponse(array("errorCode" => 401, "errorMessage"=>"Unauthorized"), 401);
    }

    $token = $request->headers->get('AccessToken');
    //$token = str_replace('Basic ', '', $token);
    //$token = base64_decode($token);

    //list($key, $secret,) = explode(":", $token);
    $em = $this->getDoctrine()->getEntityManager();
    $user = $em->getRepository('AppBundle:Person')->findOneByAccessToken($token);

    if($user == null) {
      return new JsonResponse(array("errorCode" => 403, "errorMessage"=>"Forbidden"), 403);
    }

    return $user;
  }
}