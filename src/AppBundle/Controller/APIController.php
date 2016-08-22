<?php

namespace AppBundle\Controller;

use AppBundle\Component\Modifier\MessageModifier;
use AppBundle\Component\RequestMessageBuilder;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Client;
use AppBundle\Entity\Employee;
use AppBundle\Entity\DeclarationDetail;
use AppBundle\Entity\DeclareAnimalFlag;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareExport;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\DeclareLoss;
use AppBundle\Entity\DeclareTagsTransfer;
use AppBundle\Entity\Person;
use AppBundle\Entity\RetrieveAnimals;
use AppBundle\Entity\RetrieveCountries;
use AppBundle\Entity\RetrieveTags;
use AppBundle\Entity\RetrieveAnimalDetails;
use AppBundle\Entity\RetrieveUbnDetails;
use AppBundle\Entity\RevokeDeclaration;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Token;
use AppBundle\Enumerator\AnimalTransferStatus;
use AppBundle\Enumerator\AnimalType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Enumerator\TagStateType;
use AppBundle\Enumerator\TokenType;
use AppBundle\Output\RequestMessageOutputBuilder;
use AppBundle\Service\EntityGetter;
use AppBundle\Util\Finder;
use AppBundle\Validation\HeaderValidation;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Query;
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
  /** @var RequestMessageBuilder */
  private $requestMessageBuilder;

  /** @var */
  private $serializer;

  /** @var \AppBundle\Service\AWSQueueService */
  private $queueService;

  /** @var \AppBundle\Service\AWSSimpleStorageService */
  private $storageService;

  /** @var \AppBundle\Service\EntityGetter */
  private $entityGetter;

  /** @var \AppBundle\Service\HealthService */
  private $healthService;

  /** @var \AppBundle\Service\EntityGetter */
  private $animalLocationHistoryService;

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
      $currentEnvironment = $this->getCurrentEnvironment();
      $this->requestMessageBuilder = new RequestMessageBuilder($em, $serializer, $currentEnvironment);
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
   * @return \AppBundle\Service\AWSSimpleStorageService
   */
  protected function getStorageService(){
    if($this->storageService == null){
      $this->storageService = $this->get('app.aws.storageservice');
    }

    return $this->storageService;
  }


  /**
   * @return \AppBundle\Service\AnimalLocationHistoryService
   */
  protected function getAnimalLocationHistoryService(){
    if($this->animalLocationHistoryService == null){
      $this->animalLocationHistoryService = $this->get('app.animallocation.history');
    }

    return $this->animalLocationHistoryService;
  }


  /**
   * @return \AppBundle\Service\HealthService
   */
  protected function getHealthService(){
    if($this->healthService == null){
      $this->healthService = $this->get('app.health.updater');
    }

    return $this->healthService;
  }
  
  
  protected function getCurrentEnvironment()
  {
    return $this->get('kernel')->getEnvironment();
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
   * @param $messageClassNameSpace
   * @param ArrayCollection $contentArray
   * @param $user
   * @param Location $location
   * @return null|DeclareArrival|DeclareImport|DeclareExport|DeclareDepart|DeclareBirth|DeclareLoss|DeclareAnimalFlag|DeclarationDetail|DeclareTagsTransfer|RetrieveTags|RevokeDeclaration|RetrieveAnimals|RetrieveAnimals|RetrieveCountries|RetrieveUBNDetails
   * @throws \Exception
   */
  protected function buildEditMessageObject($messageClassNameSpace, ArrayCollection $contentArray, $user, $loggedInUser, $location)
  {
    $isEditMessage = true;
    $messageObject = $this->getRequestMessageBuilder()
      ->build($messageClassNameSpace, $contentArray, $user, $loggedInUser, $location, $isEditMessage);

    return $messageObject;
  }

  /**
   * @param $messageClassNameSpace
   * @param ArrayCollection $contentArray
   * @param $user
   * @param Location $location
   * @return null|DeclareArrival|DeclareImport|DeclareExport|DeclareDepart|DeclareBirth|DeclareLoss|DeclareAnimalFlag|DeclarationDetail|DeclareTagsTransfer|RetrieveTags|RevokeDeclaration|RetrieveAnimals|RetrieveAnimals|RetrieveCountries|RetrieveUBNDetails
   * @throws \Exception
   */
  protected function buildMessageObject($messageClassNameSpace, ArrayCollection $contentArray, $user, $loggedInUser, $location)
  {
    $isEditMessage = false;
    $messageObject = $this->getRequestMessageBuilder()
        ->build($messageClassNameSpace, $contentArray, $user, $loggedInUser, $location, $isEditMessage);

    return $messageObject;
  }

  /**
   * @param $messageObject
   * @return null|DeclareArrival|DeclareImport|DeclareExport|DeclareDepart|DeclareBirth|DeclareLoss|DeclareAnimalFlag|DeclarationDetail|DeclareTagsTransfer|RetrieveTags|RevokeDeclaration|RetrieveAnimals|RetrieveAnimals|RetrieveCountries|RetrieveUBNDetails
   */
  public function persist($messageObject)
  {
    //Set the string values
    $repositoryEntityNameSpace = Utils::getRepositoryNameSpace($messageObject);

    //Persist to database
    $this->getDoctrine()->getRepository($repositoryEntityNameSpace)->persist($messageObject);

    return $messageObject;
  }


  /**
   * @param $object
   * @return mixed
   */
  protected function persistAndFlush($object)
  {
    $this->getDoctrine()->getManager()->persist($object);
    $this->getDoctrine()->getManager()->flush();
    return $object;
  }

  
  /**
   */
  protected function flushClearAndGarbageCollect()
  {
    $this->getDoctrine()->getManager()->flush();
    $this->getDoctrine()->getManager()->clear();
    gc_collect_cycles();
  }


  /**
   * @param $messageObject
   * @param bool $isUpdate
   * @return array
   */
  protected function sendMessageObjectToQueue($messageObject, $isUpdate = false) {

    $doctrine = $this->getDoctrine();
    $requestId = $messageObject->getRequestId();
    $repository = $this->getDoctrine()->getRepository(Utils::getRepositoryNameSpace($messageObject));

    //create array and jsonMessage
    $messageArray = RequestMessageOutputBuilder::createOutputArray($doctrine->getEntityManager(), $messageObject, $isUpdate);

    if($messageArray == null) {
      //These objects do not have a customized minimal json output for the queue yet
      $jsonMessage = $this->getSerializer()->serializeToJSON($messageObject);
      $messageArray = json_decode($jsonMessage, true);
    } else {
      //Use the minimized custom output
      $jsonMessage = $this->getSerializer()->serializeToJSON($messageArray);
    }

    //Send serialized message to Queue
    $requestTypeNameSpace = RequestType::getRequestTypeFromObject($messageObject);

    $sendToQresult = $this->getQueueService()
      ->send($requestId, $jsonMessage, $requestTypeNameSpace);

    //If send to Queue, failed, it needs to be resend, set state to failed
    if ($sendToQresult['statusCode'] != '200') {
      $messageObject->setRequestState(RequestStateType::FAILED);
      $messageObject = MessageModifier::modifyBeforePersistingRequestStateByQueueStatus($messageObject, $doctrine);
      $this->persist($messageObject);

    } else if($isUpdate) { //If successfully sent to the queue and message is an Update/Edit request
      $messageObject->setRequestState(RequestStateType::OPEN); //update the RequestState
      $messageObject = MessageModifier::modifyBeforePersistingRequestStateByQueueStatus($messageObject, $doctrine);
      $this->persist($messageObject);
    }

    return $messageArray;
  }

  /**
   * @param $messageObject
   * @return array
   */
  protected function sendEditMessageObjectToQueue($messageObject) {
    return $this->sendMessageObjectToQueue($messageObject, true);
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
   * @param Request $request
   * @param string $tokenCode
   * @return Client|null
   */
  public function getAuthenticatedUser(Request $request= null, $tokenCode = null)
  {
    $loggedInUser = $this->getLoggedInUser($request, $tokenCode);

    /* Clients */
    if($loggedInUser instanceof Client) {
      $isUserTheOwner = $loggedInUser->getEmployer() == null;

      if($isUserTheOwner) {
        return $loggedInUser;
      } else {
        //User is an employee at their company
        return $loggedInUser->getEmployer()->getOwner();
      }

      /* Admins with a GhostToken */
    } else if ($loggedInUser instanceof Employee) {

      if($request->headers->has(Constant::GHOST_TOKEN_HEADER_NAMESPACE) && $tokenCode == null) {
        $ghostTokenCode = $request->headers->get(Constant::GHOST_TOKEN_HEADER_NAMESPACE);
        $ghostToken = $this->getDoctrine()->getEntityManager()->getRepository(Token::class)
            ->findOneBy(array("code" => $ghostTokenCode));

        if($ghostToken != null) {
          if($ghostToken->getIsVerified()) {
            return $ghostToken->getOwner(); //client
          }
        }
        //Admins without a GhostToken
        return null;
      }
      
    } else {
        return null;
        /* Note that returning null will break a lot of code in the controllers. That is why it is essential that both the AccessToken and _verified_ GhostToken
         are validated in the TokenAuthenticator Prehook.
         At this point only Clients and Employees can login to the system. Not Inspectors.
        */
    }

  }


  /**
   * @param Request $request
   * @param string $tokenCode
   * @return Client|Employee
   */
  public function getLoggedInUser(Request $request= null, $tokenCode = null)
  {
    $em = $this->getDoctrine()->getEntityManager();

    if($tokenCode == null) {
      $tokenCode = $request->headers->get(Constant::ACCESS_TOKEN_HEADER_NAMESPACE);
    }

    $token = $em->getRepository(Token::class)->findOneBy(array("code" => $tokenCode));
    if($token != null) {
      return $token->getOwner();
    } else {
      return null;
      /* Note that returning null will break a lot of code in the controllers. That is why it is essential that both the AccessToken and _verified_ GhostToken
        are validated in the TokenAuthenticator Prehook.
      */
    }

  }


  /**
   * @param Request $request
   * @param string $tokenCode
   * @return Employee|null
   */
  public function getAuthenticatedEmployee(Request $request = null, $tokenCode = null)
  {
    $em = $this->getDoctrine()->getEntityManager();

    if($tokenCode == null) {
      $tokenCode = $request->headers->get(Constant::ACCESS_TOKEN_HEADER_NAMESPACE);
    }

    $token = $em->getRepository(Token::class)->findOneBy(array("code" => $tokenCode));
    if($token != null) {
      $owner = $token->getOwner();
      if($owner instanceof Employee) {
        return $owner;
      }
    }
    
    return null;
    /* Note that returning null will break a lot of code in the controllers. That is why it is essential that both the AccessToken and _verified_ GhostToken
      are validated in the TokenAuthenticator Prehook.
    */

  }

  public function isUlnOrPedigreeCodeValid(Request $request, $ulnCode = null)
  {
    $verifySurrogates = false;

    if($ulnCode != null) {
      return $this->verifyAnimalByUln($ulnCode);

    } else {
      $contentArray = $this->getContentAsArray($request);
      $array = $contentArray->toArray();

      //For Father (DeclareBirth) only verify pedigree. ULN not checked in API since father can be from external farm.
      if($contentArray->containsKey(Constant::FATHER_NAMESPACE)) {
        $father = $array[Constant::FATHER_NAMESPACE];

        $isVerified = $this->verifyOnlyPedigreeCodeInAnimal($father)->get('isValid');

        if (!$isVerified) {
          return array("animalKind" => Constant::FATHER_NAMESPACE,
              "keyType" => Constant::PEDIGREE_NAMESPACE,
              "isValid" => false,
              "result" => $this->createValidityCheckMessage(false, Constant::ULN_NAMESPACE), Constant::FATHER_NAMESPACE);
        }
      }

      $objectsToBeVerified = array();
      array_push($objectsToBeVerified, Constant::ANIMAL_NAMESPACE, Constant::MOTHER_NAMESPACE);
      
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

          //NOTE Children are created with new uln from unassigned tags, so they cannot be in the system!

          if($verifySurrogates) {
            //Also verify the surrogate of a child
            if(array_key_exists(Constant::SURROGATE_NAMESPACE, $child)){
              $verification = $this->verifyUlnOrPedigreeCodeInAnimal($child[Constant::SURROGATE_NAMESPACE], Constant::SURROGATE_NAMESPACE);

              if($verification["isValid"] == false) { return $verification; }
            }
          }

        }
      }

      $keyType = Constant::ULN_NAMESPACE . " and/or " . Constant::PEDIGREE_SNAKE_CASE_NAMESPACE;

      //When all animals have passed the verification return this:
      return array("animalKind" => "All objects",
            "keyType" => $keyType,
            "isValid" => true,
            "result" => $this->createValidityCheckMessage(true));
    }

  }

  /**
   * @param array $animalArray
   * @return ArrayCollection
   */
  public function verifyOnlyPedigreeCodeInAnimal($animalArray)
  {
    $array = new ArrayCollection();

    $pedigreeCountryCode = Utils::getNullCheckedArrayValue(JsonInputConstant::PEDIGREE_COUNTRY_CODE, $animalArray);
    $pedigreeNumber = Utils::getNullCheckedArrayValue(JsonInputConstant::PEDIGREE_NUMBER, $animalArray);
    $isValid = \AppBundle\Validation\Validator::verifyPedigreeCode($this->getDoctrine()->getManager(), $pedigreeCountryCode, $pedigreeNumber, true);

    if($pedigreeCountryCode != null && $pedigreeNumber != null) {
      $pedigree = $pedigreeCountryCode.$pedigreeNumber;
    } else {
      $pedigree = null;
    }
    
    $array->set('isValid', $isValid);
    $array->set('pedigreeNumber', null);
    $array->set('pedigreeCountryCode', null);
    $array->set(Constant::PEDIGREE_NAMESPACE, $pedigree);

    return $array;
  }

  /**
   * @param boolean $isValid
   * @param string $keyType
   * @param string $animalKind
   * @return array
   */
  private function createValidityCheckMessage($isValid, $keyType = null, $animalKind = null)
  {
    if($isValid && $keyType == null && $animalKind == null) {
      $message = "The uln and/or pedigree values for all objects are valid.";
      $code = 200;
    } else if ($keyType == null) {
      $message = "The uln or pedigree" . ' of ' . $animalKind . ' not found.';
      $code = 400;
    } else if ($animalKind == null) {
      $message = "No animal found";
      $code = 400;
    } else if (!$isValid) { //and has keyType and animalKind
      $message = $keyType . ' of ' . $animalKind . ' not found.';
      $code = 428;
    } else { //isValid == true, and has keyType and animalKind
      $message = "The " . $keyType . " of " . $animalKind . " is valid.";
      $code = 200;
    }

    return array('code'=>$code, "message" => $message);
  }

  private function verifyUlnOrPedigreeCodeInAnimal($animalContentArray, $objectToBeVerified)
  {
    $ulnCountryCode = null;
    $pedigreeCountryCode = null;
    $ulnCode = null;
    $pedigreeCode = null;
    $animal = null;

    //This repository class is used to verify if a pedigree code is valid
    $animalRepository = $this->getDoctrine()->getRepository(Constant::ANIMAL_REPOSITORY);

    if (array_key_exists(Constant::ULN_COUNTRY_CODE_NAMESPACE, $animalContentArray) && array_key_exists(Constant::ULN_NUMBER_NAMESPACE, $animalContentArray)) {
      $ulnCode = $animalContentArray[Constant::ULN_NUMBER_NAMESPACE];
      $ulnCountryCode = $animalContentArray[Constant::ULN_COUNTRY_CODE_NAMESPACE];

      $animal = $animalRepository->findByUlnCountryCodeAndNumber($ulnCountryCode, $ulnCode);

      if ($animal == null) {
        return array("animalKind" => $objectToBeVerified,
            "keyType" => Constant::ULN_NAMESPACE,
            "isValid" => false,
            "result" => $this->createValidityCheckMessage(false, Constant::ULN_NAMESPACE), $objectToBeVerified);
      }
    }
    else {
      if (array_key_exists(Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE, $animalContentArray) && array_key_exists(Constant::PEDIGREE_NUMBER_NAMESPACE, $animalContentArray)) {
        $pedigreeCode = $animalContentArray[Constant::PEDIGREE_NUMBER_NAMESPACE];
        $pedigreeCountryCode = $animalContentArray[Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE];
      }

      $animal = $animalRepository->findByPedigreeCountryCodeAndNumber($pedigreeCountryCode, $pedigreeCode);

      if($animal == null){
        $keyType = Constant::PEDIGREE_SNAKE_CASE_NAMESPACE;
        return array("animalKind" => $objectToBeVerified,
            "keyType" => $keyType,
            "isValid" => false,
            "result" => $this->createValidityCheckMessage(false, $keyType, $objectToBeVerified));
      }
    }
    $keyType = Constant::ULN_NAMESPACE . " and/or " . Constant::PEDIGREE_SNAKE_CASE_NAMESPACE;

    return array("animalKind" => $objectToBeVerified,
        "keyType" => $keyType,
        "isValid" => true,
        "result" => $this->createValidityCheckMessage(true));
  }

  private function verifyAnimalByUln($ulnString)
  {
    $isValid = false;
    $keyType = Constant::ULN_NAMESPACE;

    //validate if Id is of format: AZ123456789
    if(!preg_match("([A-Z]{2}\d+)",$ulnString)){
      //Directly return isValid = false result

    } else {

      $animalRepository = $this->getDoctrine()->getRepository(Constant::ANIMAL_REPOSITORY);
      $animal = $animalRepository->findByUlnOrPedigree($ulnString, true);

      if ($animal != null) {
        $isValid = true;
      }
    }

    return array("animalKind" => "Id",
        "keyType" => $keyType,
        "isValid" => $isValid,
        "result" => $this->createValidityCheckMessage($isValid, $keyType, $ulnString));
  }

  public function isTagUnassigned($ulnCountryCode, $ulnNumber)
  {
    $isValid = true;
    $jsonResponse = null;

    $tag = $this->getEntityGetter()->retrieveTag($ulnCountryCode, $ulnNumber);

    if($tag == null) {
      $isValid = false;
      $message = "Tag " . $ulnCountryCode . $ulnNumber . " for child does not exist";
      $jsonResponse = new JsonResponse(array('code'=>428, "message" => $message), 428);

    } else {
      if($tag->getTagStatus() == TagStateType::ASSIGNED || $tag->getTagStatus() == TagStateType::ASSIGNING){
        $isValid = false;
        $message = "Tag " . $ulnCountryCode . $ulnNumber . " for child already in use";
        $jsonResponse = new JsonResponse(array('code'=>428, "message" => $message), 428);
      }
    }

    return array('isValid' => $isValid, 'jsonResponse' => $jsonResponse);
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
      $tokenCode = $request->headers->get(Constant::ACCESS_TOKEN_HEADER_NAMESPACE);

      $token = $this->getDoctrine()->getRepository(Token::class)
          ->findOneBy(array("code" => $tokenCode, "type" => TokenType::ACCESS));
      
      if($token != null) {
        $response = array(
            'token_status' => 'valid',
            'token' => $tokenCode
        );
        return new JsonResponse($response, 200);
        
      } else {
        $response = array(
            'error'=> 401,
            'errorMessage'=> 'No AccessToken provided'
        );
      }
    } else {
      //Mandatory AccessToken was not provided
      $response = array(
          'error'=> 401,
          'errorMessage'=> 'Mandatory AccessToken header was not provided'
      ); 
    }

    return new JsonResponse($response, 401);
  }

  /**
   * Retrieve the messageObject related to the RevokeDeclaration
   * reset the request state to 'REVOKING'
   * and persist the update.
   *
   * @param string $messageNumber
   */
  public function persistRevokingRequestState($messageNumber)
  {
    $messageObjectTobeRevoked = $this->getEntityGetter()->getRequestMessageByMessageNumber($messageNumber);

    $messageObjectWithRevokedRequestState = $messageObjectTobeRevoked->setRequestState(RequestStateType::REVOKING);

    $this->persist($messageObjectWithRevokedRequestState);
  }

  /**
   * @param Animal|Ram|Ewe|Neuter $animal
   */
  public function persistAnimalTransferringStateAndFlush($animal)
  {
    $animal->setTransferState(AnimalTransferStatus::TRANSFERRING);
    $this->getDoctrine()->getEntityManager()->persist($animal);
    $this->getDoctrine()->getEntityManager()->flush();
  }

  /**
   * @param $email
   * @return Client
   */
  public function getClientByEmail($email) {
    return $this->getDoctrine()->getRepository(Constant::CLIENT_REPOSITORY)->getByEmail($email);
  }

  /**
   * @param Client $client
   * @param string $ubn
   * @return Location|null
   */
  public function getLocationByUbn($client, $ubn)
  {
    return $this->getDoctrine()->getRepository(Constant::LOCATION_REPOSITORY)->findOfClientByUbn($client, $ubn);
  }


  public function persistNewTagsToAssigning($client, $animalObject)
  {
    $tag = $this->getDoctrine()->getRepository(Constant::TAG_REPOSITORY)->findByAnimal($client, $animalObject);
    $tag->setTagStatus(TagStateType::ASSIGNING);

    //Because of cascade persist, unset the tag from the animal first
    $tag->setAnimal(null);

    $this->getDoctrine()->getManager()->persist($tag);
    $this->getDoctrine()->getManager()->flush();

    return $tag;
  }

  /**
   * @param string $ubn
   * @return array
   */
  public function isUbnValid($ubn)
  {
    //Default values
    $isValid = false;
    $relationNumberKeeper = null;
    $code = 428;
    $message = 'THE UBN IS NOT REGISTERED AT NSFO';

    $location = $this->getDoctrine()->getRepository(Constant::LOCATION_REPOSITORY)->findByUbn($ubn);

    if($location != null) {
      $isValid = true;
      //'relationNumberKeeper' is an obligatory field in Client, so no need to verify if that field exists or not.
      $relationNumberKeeper = $location->getCompany()->getOwner()->getRelationNumberKeeper();
      $code = 200;
      $message = 'UBN IS VALID';
    } //else just use the default values

    return array('isValid' => $isValid,
    'relationNumberKeeper' => $relationNumberKeeper,
        Constant::MESSAGE_NAMESPACE => $message,
           Constant::CODE_NAMESPACE => $code
                );

  }


  public function hasMessageNumber(ArrayCollection $content)
  {
    //Default values
    $isValid = false;
    $messageNumber = null;
    $code = 428;
    $messageBody = 'THERE IS NO VALUE GIVEN FOR THE MESSAGE NUMBER';

    if($content->containsKey(Constant::MESSAGE_NUMBER_SNAKE_CASE_NAMESPACE)) {
      $messageNumber = $content->get(Constant::MESSAGE_NUMBER_SNAKE_CASE_NAMESPACE);

      if($messageNumber != null || $messageNumber != "") {
        $isValid = true;
        $code = 200;
        $messageBody = 'MESSAGE NUMBER FIELD EXISTS AND IS NOT EMPTY';
      }
    }

    return Utils::buildValidationArray($isValid, $code, $messageBody, array('messageNumber' => $messageNumber));
  }

  /**
   * @param Request $request
   * @return Location|null
   */
  public function getSelectedLocation(Request $request)
  {

    $client = $this->getAuthenticatedUser($request);
    $headerValidation = new HeaderValidation($this->getDoctrine()->getManager(), $request, $client);

    if($headerValidation->isInputValid()) {
      return $headerValidation->getLocation();

    } else {

      $locations = Finder::findLocationsOfClient($client);
      if($locations->count() > 0) {
        //pick the first available Location as default
        return $locations->get(0);

      } else {
        return null;
      }
    }
  }

  /**
   * @param Request $request
   * @return string|null
   */
  public function getSelectedUbn(Request $request)
  {

    $client = $this->getAuthenticatedUser($request);
    $headerValidation = new HeaderValidation($this->getDoctrine()->getManager(), $request, $client);

    if($headerValidation->isInputValid()) {
      return $headerValidation->getUbn();

    } else {

      $locations = Finder::findLocationsOfClient($client);
      if($locations->count() > 0) {
        //pick the first available Location as default
        return $locations->get(0)->getUbn();

      } else {
        return null;
      }
    }
  }

  public function syncAnimalsForAllLocations($loggedInUser)
  {
    $allLocations = $this->getDoctrine()->getRepository(Constant::LOCATION_REPOSITORY)->findAll();
    $content = new ArrayCollection();
    $count = 0;

    /** @var Location $location */
    foreach($allLocations as $location) {
      $client = $location->getCompany()->getOwner();

      //Convert the array into an object and add the mandatory values retrieved from the database
      $messageObject = $this->buildMessageObject(RequestType::RETRIEVE_ANIMALS_ENTITY, $content, $client, $loggedInUser, $location);

      //First Persist object to Database, before sending it to the queue
      $this->persist($messageObject);

      //Send it to the queue and persist/update any changed state to the database
      $messageArray = $this->sendMessageObjectToQueue($messageObject);

      $count++;
    }

    $total = sizeof($allLocations);
    $message = "THE ANIMALS HAVE BEEN SYNCED FOR " . $count . " OUT OF " . $total . " TOTAL LOCATIONS (UBNS)";

    return array('message' => $message,
        'count' => $count);
  }

  
  /**
   * @param Person $person
   * @param int $passwordLength
   * @return string
   */
  protected function persistNewPassword($person, $passwordLength = 9)
  {
    $newPassword = Utils::randomString($passwordLength);

    $encoder = $this->get('security.password_encoder');
    $encodedNewPassword = $encoder->encodePassword($person, $newPassword);
    $person->setPassword($encodedNewPassword);

    $this->getDoctrine()->getManager()->persist($person);
    $this->getDoctrine()->getManager()->flush();

    return $newPassword;
  }

  /**
   * @param Person $person
   */
  protected function emailNewPasswordToPerson($person, $newPassword, $isAdmin = false)
  {
    $mailerSourceAddress = $this->getParameter('mailer_source_address');

    if($isAdmin) {
      $subjectHeader = Constant::NEW_ADMIN_PASSWORD_MAIL_SUBJECT_HEADER;
    } else {
      $subjectHeader = Constant::NEW_PASSWORD_MAIL_SUBJECT_HEADER;
    }
    
    //Confirmation message back to the sender
    $message = \Swift_Message::newInstance()
        ->setSubject($subjectHeader)
        ->setFrom($mailerSourceAddress)
        ->setTo($person->getEmailAddress())
        ->setBody(
            $this->renderView(
            // app/Resources/views/...
                'User/reset_password_email.html.twig',
                array('firstName' => $person->getFirstName(),
                    'lastName' => $person->getLastName(),
                    'userName' => $person->getUsername(),
                    'email' => $person->getEmailAddress(),
                    'password' => $newPassword)
            ),
            'text/html'
        )
        ->setSender($mailerSourceAddress)
    ;

    $this->get('mailer')->send($message);
  }
}