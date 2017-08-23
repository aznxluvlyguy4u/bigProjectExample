<?php

namespace AppBundle\Controller;

use AppBundle\Component\Modifier\MessageModifier;
use AppBundle\Component\RequestMessageBuilder;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Client;
use AppBundle\Entity\ClientRepository;
use AppBundle\Entity\DeclareBase;
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
use AppBundle\Entity\RetrieveUbnDetails;
use AppBundle\Entity\RevokeDeclaration;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Token;
use AppBundle\Enumerator\AnimalTransferStatus;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Enumerator\ServiceId;
use AppBundle\Enumerator\TagStateType;
use AppBundle\Enumerator\TokenType;
use AppBundle\Output\RequestMessageOutputBuilder;
use AppBundle\Service\AnimalLocationHistoryService;
use AppBundle\Service\AwsExternalQueueService;
use AppBundle\Service\AwsInternalQueueService;
use AppBundle\Service\AWSSimpleStorageService;
use AppBundle\Service\CacheService;
use AppBundle\Service\EntityGetter;
use AppBundle\Service\ExcelService;
use AppBundle\Service\HealthService;
use AppBundle\Service\IRSerializer;
use AppBundle\Service\Migration\ClientMigrator;
use AppBundle\Service\MixBlupInputQueueService;
use AppBundle\Service\MixBlupOutputQueueService;
use AppBundle\Service\Report\BreedValuesOverviewReportService;
use AppBundle\Service\Report\InbreedingCoefficientReportService;
use AppBundle\Service\Report\LiveStockReportService;
use AppBundle\Service\Report\PedigreeCertificateReportService;
use AppBundle\Service\Report\PedigreeRegisterOverviewReportService;
use AppBundle\Service\UserService;
use AppBundle\Util\Finder;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\Validator;
use AppBundle\Validation\HeaderValidation;
use AppBundle\Worker\Task\WorkerMessageBody;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\LazyCriteriaCollection;
use Doctrine\ORM\Query;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
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
  /** @var array */
  private $services = [
      ServiceId::ANIMAL_LOCATION_HISTORY => null,
      ServiceId::BREED_VALUES_OVERVIEW_REPORT => null,
      ServiceId::CACHE => null,
      ServiceId::CLIENT_MIGRATOR => null,
      ServiceId::ENTITY_GETTER => null,
      ServiceId::EXCEL_SERVICE => null,
      ServiceId::EXTERNAL_QUEUE_SERVICE => null,
      ServiceId::HEALTH_SERVICE => null,
      ServiceId::INBREEDING_COEFFICIENT_REPORT_SERVICE => null,
      ServiceId::INTERNAL_QUEUE_SERVICE => null,
      ServiceId::LIVESTOCK_REPORT => null,
      ServiceId::LOGGER => null,
      ServiceId::MIXBLUP_INPUT_QUEUE_SERVICE => null,
      ServiceId::MIXBLUP_OUTPUT_QUEUE_SERVICE => null,
      ServiceId::PEDIGREE_CERTIFICATES_REPORT => null,
      ServiceId::PEDIGREE_REGISTER_REPORT => null,
      ServiceId::REDIS_CLIENT => null,
      ServiceId::REQUEST_MESSAGE_BUILDER => null,
      ServiceId::SERIALIZER => null,
      ServiceId::STORAGE_SERVICE => null,
      ServiceId::USER_SERVICE => null,
  ];


  /**
   * @param string $controller
   * @return mixed|null
   */
  private function getService($controller){
    if(!key_exists($controller, $this->services)) { return null;}

    if ($this->services[$controller] == null) {
      $this->services[$controller] = $this->get($controller);
    }
    return $this->services[$controller];
  }


  /** @return AnimalLocationHistoryService */
  protected function getAnimalLocationHistoryService(){ return $this->getService(ServiceId::ANIMAL_LOCATION_HISTORY); }
  /** @return BreedValuesOverviewReportService */
  protected function getBreedValuesOverviewReportService() { return $this->getService(ServiceId::BREED_VALUES_OVERVIEW_REPORT); }
  /** @return CacheService */
  protected function getCacheService(){ return $this->getService(ServiceId::CACHE); }
  /** @return ClientMigrator */
  protected function getClientMigratorService(){ return $this->getService(ServiceId::CLIENT_MIGRATOR); }
  /** @return EntityGetter */
  protected function getEntityGetter() { return $this->getService(ServiceId::ENTITY_GETTER); }
  /** @return ExcelService */
  protected function getExcelService() { return $this->getService(ServiceId::EXCEL_SERVICE); }
  /** @return AwsExternalQueueService */
  protected function getExternalQueueService(){ return $this->getService(ServiceId::EXTERNAL_QUEUE_SERVICE); }
  /** @return HealthService */
  protected function getHealthService(){ return $this->getService(ServiceId::HEALTH_SERVICE); }
  /** @return InbreedingCoefficientReportService */
  protected function getInbreedingCoefficientReportService() { return $this->getService(ServiceId::INBREEDING_COEFFICIENT_REPORT_SERVICE); }
  /** @return AwsInternalQueueService */
  protected function getInternalQueueService() { return $this->getService(ServiceId::INTERNAL_QUEUE_SERVICE); }
  /** @return LiveStockReportService */
  protected function getLiveStockReportService() { return $this->getService(ServiceId::LIVESTOCK_REPORT); }
  /** @return Logger */
  protected function getLogger() { return $this->getService(ServiceId::LOGGER); }
  /** @return MixBlupInputQueueService */
  protected function getMixBlupInputQueueService() { return $this->getService(ServiceId::MIXBLUP_INPUT_QUEUE_SERVICE); }
  /** @return MixBlupOutputQueueService */
  protected function getMixBlupOutputQueueService() { return $this->getService(ServiceId::MIXBLUP_OUTPUT_QUEUE_SERVICE); }
  /** @return PedigreeCertificateReportService */
  protected function getPedigreeCertificateReportService() { return $this->getService(ServiceId::PEDIGREE_CERTIFICATES_REPORT); }
  /** @return PedigreeRegisterOverviewReportService */
  protected function getPedigreeRegisterReportService() { return $this->getService(ServiceId::PEDIGREE_REGISTER_REPORT); }
  /** @return \Redis */
  protected function getRedisClient() { return $this->getService(ServiceId::REDIS_CLIENT); }
  /** @return RequestMessageBuilder */
  protected function getRequestMessageBuilder() { return $this->getService(ServiceId::REQUEST_MESSAGE_BUILDER); }
  /** @return IRSerializer */
  protected function getSerializer() { return $this->getService(ServiceId::SERIALIZER);  }
  /** @return AWSSimpleStorageService */
  protected function getStorageService(){ return $this->getService(ServiceId::STORAGE_SERVICE); }
  /** @return UserService */
  protected function getUserService(){ return $this->getService(ServiceId::USER_SERVICE); }


  /**
   * @param Request $request
   * @return ArrayCollection
   */
  protected function getContentAsArray(Request $request)
  {
      return RequestUtil::getContentAsArray($request);
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
   * @param DeclareBase $messageObject
   * @param bool $isUpdate
   * @return array
   */
  protected function sendMessageObjectToQueue($messageObject, $isUpdate = false) {

    $em = $this->getDoctrine()->getManager();
    $requestId = $messageObject->getRequestId();
    $repository = $this->getDoctrine()->getRepository(Utils::getRepositoryNameSpace($messageObject));

    //create array and jsonMessage
    $messageArray = RequestMessageOutputBuilder::createOutputArray($em, $messageObject, $isUpdate);

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

    $sendToQresult = $this->getExternalQueueService()
      ->send($jsonMessage, $requestTypeNameSpace, $requestId);

    //If send to Queue, failed, it needs to be resend, set state to failed
    if ($sendToQresult['statusCode'] != '200') {
      $messageObject->setRequestState(RequestStateType::FAILED);
      $messageObject = MessageModifier::modifyBeforePersistingRequestStateByQueueStatus($messageObject, $em);
      $this->persist($messageObject);

    } else if($isUpdate) { //If successfully sent to the queue and message is an Update/Edit request
      $messageObject->setRequestState(RequestStateType::OPEN); //update the RequestState
      $messageObject = MessageModifier::modifyBeforePersistingRequestStateByQueueStatus($messageObject, $em);
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
   * @param WorkerMessageBody $workerMessageBody
   * @return bool
   */
  protected function sendTaskToQueue($workerMessageBody) {
    if($workerMessageBody == null) { return false; }

    $jsonMessage = $this->getSerializer()->serializeToJSON($workerMessageBody);

    //Send  message to Queue
    $sendToQresult = $this->getInternalQueueService()
      ->send($jsonMessage, $workerMessageBody->getTaskType(), 1);

    //If send to Queue, failed, it needs to be resend, set state to failed
    return $sendToQresult['statusCode'] == '200';
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
   * @return Client|null
   */
  public function getAccountOwner(Request $request = null)
  {
    return $this->getUserService()->getAccountOwner($request);
  }


  /**
   * @param string $tokenCode
   * @return Employee|null
   */
  public function getEmployee($tokenCode = null)
  {
    return $this->getUserService()->getEmployee($tokenCode);
  }


  /**
   * TODO move to AUTH service
   *
   * @param Request $request
   * @return JsonResponse
   */
    public function isAccessTokenValid(Request $request)
    {
        $token = null;
        $response = null;
        $content = $this->getContentAsArray($request);

        //Get token header to read token value
        if($request->headers->has(Constant::ACCESS_TOKEN_HEADER_NAMESPACE)) {

            $environment = $content->get('env');
            $tokenCode = $request->headers->get(Constant::ACCESS_TOKEN_HEADER_NAMESPACE);
            $token = $this->getDoctrine()->getRepository(Token::class)
                ->findOneBy(array("code" => $tokenCode, "type" => TokenType::ACCESS));

            if ($token != null) {
                if ($environment == 'USER') {
                    if ($token->getOwner() instanceof Client) {
                        $response = array(
                            'token_status' => 'valid',
                            'token' => $tokenCode
                        );
                        return new JsonResponse($response, 200);
                    } elseif ($token->getOwner() instanceof Employee ) {
                        $ghostTokenCode = $request->headers->get(Constant::GHOST_TOKEN_HEADER_NAMESPACE);
                        $ghostToken = $this->getDoctrine()->getRepository(Token::class)
                            ->findOneBy(array("code" => $ghostTokenCode, "type" => TokenType::GHOST));

                        if($ghostToken != null) {
                            $response = array(
                                'token_status' => 'valid',
                                'token' => $tokenCode
                            );
                            return new JsonResponse($response, 200);
                        }
                    } else {
                        $response = array(
                            'error' => 401,
                            'errorMessage' => 'No AccessToken provided'
                        );
                    }
                }
            }

            if ($environment == 'ADMIN') {
                if ($token->getOwner() instanceof Employee) {
                    $response = array(
                        'token_status' => 'valid',
                        'token' => $tokenCode
                    );
                    return new JsonResponse($response, 200);
                } else {
                    $response = array(
                        'error' => 401,
                        'errorMessage' => 'No AccessToken provided'
                    );
                }
            }

            $response = array(
                'error'=> 401,
                'errorMessage'=> 'No AccessToken provided'
            );
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
    $this->getDoctrine()->getManager()->persist($animal);
    $this->getDoctrine()->getManager()->flush();
  }

  /**
   * @param Request $request
   * @return Location|null
   */
  public function getSelectedLocation(Request $request)
  {
      return $this->getUserService()->getSelectedLocation($request);
  }

  
  /**
   * TODO move to AUTH Service
   *
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
   * TODO move to email service
   *
   * @param Person $person
   */
  protected function emailNewPasswordToPerson($person, $newPassword, $isAdmin = false, $isNewUser = false)
  {
    $mailerSourceAddress = $this->getParameter('mailer_source_address');

    if($isAdmin) {
      $subjectHeader = Constant::NEW_ADMIN_PASSWORD_MAIL_SUBJECT_HEADER;
    } else {
      $subjectHeader = Constant::NEW_PASSWORD_MAIL_SUBJECT_HEADER;
    }

    if($isNewUser) {
        $twig = 'User/new_user_email.html.twig';
    } else {
        $twig = 'User/reset_password_email.html.twig';
    }
    
    //Confirmation message back to the sender
    $message = \Swift_Message::newInstance()
        ->setSubject($subjectHeader)
        ->setFrom($mailerSourceAddress)
        ->setTo($person->getEmailAddress())
        ->setBcc($mailerSourceAddress)
        ->setBody(
            $this->renderView(
            // app/Resources/views/...
                $twig,
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

  /**
   * Clears the redis cache for the Livestock of a given location , to reflect changes of animals on Livestock.
   *
   * @param Location $location
   * @param Animal | Ewe | Ram | Neuter $animal
   */
  protected function clearLivestockCacheForLocation(Location $location = null, $animal = null) {
      $this->getCacheService()->clearLivestockCacheForLocation($location, $animal);
  }
}