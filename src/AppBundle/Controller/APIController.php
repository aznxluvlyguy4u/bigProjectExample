<?php

namespace AppBundle\Controller;

use AppBundle\Component\RequestMessageBuilder;
use AppBundle\Constant\Constant;
use AppBundle\Enumerator\AnimalType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Service\EntityGetter;
use AppBundle\Service\EntitySetter;
use AppBundle\Entity\RevokeDeclaration;
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
   * @var \AppBundle\Service\EntitySetter
   */
  private $entitySetter;

  /**
   * @return \AppBundle\Service\EntitySetter
   */
  protected function getEntitySetter()
  {
    if($this->entitySetter == null){
      $this->entitySetter = $this->get('app.doctrine.entitysetter');
    }

    return $this->entitySetter;
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
   * @return object|null
   * @throws \Exception
   */
  protected function buildEditMessageObject($messageClassNameSpace, ArrayCollection $contentArray, $user)
  {
    $isEditMessage = true;
    $messageObject = $this->getRequestMessageBuilder()
      ->build($messageClassNameSpace, $contentArray, $user, $isEditMessage);

    return $messageObject;
  }

  /**
   * @param $messageClassNameSpace
   * @param ArrayCollection $contentArray
   * @param $user
   * @return object
   * @throws \Exception
   */
  protected function buildMessageObject($messageClassNameSpace, ArrayCollection $contentArray, $user)
  {
    $isEditMessage = false;
    $messageObject = $this->getRequestMessageBuilder()
        ->build($messageClassNameSpace, $contentArray, $user, $isEditMessage);

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

  public function isUlnOrPedigreeCodeValid(Request $request, $Id = null)
  {
    if($Id != null) {
      return $this->verifyUlnOrPedigreeCode($Id);

    } else {
      $contentArray = $this->getContentAsArray($request);
      $array = $contentArray->toArray();

      $objectsToBeVerified = array();
      array_push($objectsToBeVerified, Constant::ANIMAL_NAMESPACE, Constant::FATHER_NAMESPACE, Constant::MOTHER_NAMESPACE);

      //All objects containing a uln or pedigree code must have that code verified
      foreach ($objectsToBeVerified as $objectToBeVerified) {

        if (array_key_exists($objectToBeVerified, $array)) {
          $animalContentArray = $contentArray->get($objectToBeVerified);

          $verification = $this->verifyUlnOrPedigreeCodeInAnimal($animalContentArray, $objectToBeVerified);

          if($verification["isValid"] == false) { return $verification; }
        }
      }

      //Animals in a Children array need to be retrieved differently
      if($contentArray->containsKey(Constant::CHILDREN_NAMESPACE)){
        $children = $array[Constant::CHILDREN_NAMESPACE];

        foreach($children as $child) {
          $verification = $this->verifyUlnOrPedigreeCodeInAnimal($child, Constant::CHILDREN_NAMESPACE);

          if($verification["isValid"] == false) { return $verification; }

          //Also verify the surrogate of a child
          if(array_key_exists(Constant::SURROGATE_NAMESPACE, $child)){
            $verification = $this->verifyUlnOrPedigreeCodeInAnimal($child[Constant::SURROGATE_NAMESPACE], Constant::SURROGATE_NAMESPACE);

            if($verification["isValid"] == false) { return $verification; }
          }
        }
      }

      $keyType = Constant::ULN_NAMESPACE . " and/or " . Constant::PEDIGREE_NAMESPACE;

      //When all animals have passed the verification return this:
      return array("animalKind" => "All objects",
            "keyType" => $keyType,
            "isValid" => true);
    }

  }

  private function verifyUlnOrPedigreeCodeInAnimal($animalContentArray, $objectToBeVerified)
  {
    $ulnCountryCode = null;
    $pedigreeCountryCode = null;
    $ulnCode = null;
    $pedigreeCode = null;
    $tag = null;
    $animal = null;

    //First check if supplied ulnNumber & ulnCountryCode exists by checking if a Tag exists
    $tagRepository = $this->getDoctrine()->getRepository(Constant::TAG_REPOSITORY);

    //This repository class is used to verify if a pedigree code is valid
    $animalRepository = $this->getDoctrine()->getRepository(Constant::ANIMAL_REPOSITORY);

    if (array_key_exists(Constant::ULN_COUNTRY_CODE_NAMESPACE, $animalContentArray) && array_key_exists(Constant::ULN_NUMBER_NAMESPACE, $animalContentArray)) {
      $ulnCode = $animalContentArray[Constant::ULN_NUMBER_NAMESPACE];
      $ulnCountryCode = $animalContentArray[Constant::ULN_COUNTRY_CODE_NAMESPACE];

      $tag = $tagRepository->findByUlnNumberAndCountryCode($ulnCountryCode, $ulnCode);

      if ($tag == null) {
        return array("animalKind" => $objectToBeVerified,
            "keyType" => Constant::ULN_NAMESPACE,
            "isValid" => false);
      }
    }
    else {
      if (array_key_exists(Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE, $animalContentArray) && array_key_exists(Constant::PEDIGREE_NUMBER_NAMESPACE, $animalContentArray)) {
        $pedigreeCode = $animalContentArray[Constant::PEDIGREE_NUMBER_NAMESPACE];
        $pedigreeCountryCode = $animalContentArray[Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE];
      }

      $animal = $animalRepository->findByCountryCodeAndPedigree($pedigreeCountryCode, $pedigreeCode);

      if($animal == null){
        return array("animalKind" => $objectToBeVerified,
            "keyType" => Constant::PEDIGREE_NAMESPACE,
            "isValid" => false);
      }
    }
    $keyType = Constant::ULN_NAMESPACE . " and/or " . Constant::PEDIGREE_NAMESPACE;

    return array("animalKind" => $objectToBeVerified,
        "keyType" => $keyType,
        "isValid" => true);
  }

  private function verifyUlnOrPedigreeCode($Id)
  {
    $isValid = false;
    $keyType = Constant::ULN_NAMESPACE . " and/or " . Constant::PEDIGREE_NAMESPACE;

    //First check if supplied ulnNumber & ulnCountryCode exists by checking if a Tag exists
    $tagRepository = $this->getDoctrine()->getRepository(Constant::TAG_REPOSITORY);

    //Strip countryCode
    $countryCode = mb_substr($Id, 0, 2, 'utf-8');

    //Strip ulnCode or pedigreeCode
    $ulnOrPedigreeCode = mb_substr($Id, 2, strlen($Id));

    $tag = $tagRepository->findByUlnNumberAndCountryCode($countryCode, $ulnOrPedigreeCode);

    if ($tag != null) {
      $isValid = true;
      $keyType = Constant::ULN_NAMESPACE;
    } else {
      //Verify if id is a valid pedigreenumber

      $animalRepository = $this->getDoctrine()->getRepository(Constant::ANIMAL_REPOSITORY);
      $animal = $animalRepository->findByCountryCodeAndPedigree($countryCode, $ulnOrPedigreeCode);

      if ($animal != null) {
        $isValid = true;
        $keyType = Constant::PEDIGREE_NAMESPACE;
      }
    }

    return array("animalKind" => "Id",
        "keyType" => $keyType,
        "isValid" => $isValid);
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

  /**
   * Retrieve the messageObject related to the RevokeDeclaration
   * reset the request state to 'revoked'
   * and persist the update.
   *
   * @param RevokeDeclaration $revokeDeclarationObject
   */
  public function persistRevokedRequestState(RevokeDeclaration $revokeDeclarationObject)
  {
    $em = $this->getDoctrine()->getEntityManager();

    $messageObjectTobeRevoked = $this->getEntitySetter()->setRequestStateToRevoked($revokeDeclarationObject->getMessageId());

    $messageObjectWithRevokedRequestState = $messageObjectTobeRevoked->setRequestState(RequestStateType::REVOKED);

    $classNameWithPath = $em->getClassMetadata(get_class($messageObjectTobeRevoked))->getName();
    $pathArray = explode('\\', $classNameWithPath);
    $className = $pathArray[sizeof($pathArray)-1];

    $this->persist($messageObjectWithRevokedRequestState, $className);
  }
}