<?php

namespace AppBundle\Service;


use AppBundle\Component\DepartMessageBuilder;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\RequestMessageBuilder;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Controller\ArrivalAPIControllerInterface;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareArrivalResponse;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\DeclareImportResponse;
use AppBundle\Entity\Location;
use AppBundle\Entity\Message;
use AppBundle\Enumerator\MessageType;
use AppBundle\Enumerator\RecoveryIndicatorType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\Validator;
use AppBundle\Validation\TagValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class ArrivalService extends DeclareControllerServiceBase implements ArrivalAPIControllerInterface
{
    /** @var HealthUpdaterService */
    private $healthService;
    /** @var AnimalLocationHistoryService */
    private $animalLocationHistoryService;
    /** @var string */
    private $environment;

    public function __construct(AwsExternalQueueService $externalQueueService,
                                CacheService $cacheService,
                                EntityManagerInterface $manager,
                                IRSerializer $serializer,
                                RequestMessageBuilder $requestMessageBuilder,
                                UserService $userService, HealthUpdaterService $healthService,
                                AnimalLocationHistoryService $animalLocationHistoryService,
                                $environment)
    {
        parent::__construct($externalQueueService, $cacheService, $manager, $serializer, $requestMessageBuilder, $userService);
        $this->healthService = $healthService;
        $this->animalLocationHistoryService = $animalLocationHistoryService;
        $this->environment = $environment;
    }


    /**
     * @param Request $request
     * @param $Id
     * @return JsonResponse
     */
    public function getArrivalById(Request $request, $Id)
    {
        $location = $this->getSelectedLocation($request);
        $arrival = $this->getManager()->getRepository(DeclareArrival::class)->getArrivalByRequestId($location, $Id);
        return new JsonResponse($arrival, 200);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getArrivals(Request $request)
    {
        $location = $this->getSelectedLocation($request);
        $stateExists = $request->query->has(Constant::STATE_NAMESPACE);

        if(!$stateExists) {
            $declareArrivals = $this->getManager()->getRepository(DeclareArrival::class)->getArrivals($location);

        } else if ($request->query->get(Constant::STATE_NAMESPACE) == Constant::HISTORY_NAMESPACE ) {

            $declareArrivals = new ArrayCollection();
            foreach($this->getManager()->getRepository(DeclareArrival::class)->getArrivals($location, RequestStateType::OPEN) as $arrival) {
                $declareArrivals->add($arrival);
            }
            foreach($this->getManager()->getRepository(DeclareArrival::class)->getArrivals($location, RequestStateType::REVOKING) as $arrival) {
                $declareArrivals->add($arrival);
            }
            foreach($this->getManager()->getRepository(DeclareArrival::class)->getArrivals($location, RequestStateType::FINISHED) as $arrival) {
                $declareArrivals->add($arrival);
            }

        } else { //A state parameter was given, use custom filter to find subset
            $state = $request->query->get(Constant::STATE_NAMESPACE);
            $declareArrivals = $this->getManager()->getRepository(DeclareArrival::class)->getArrivals($location, $state);
        }

        return ResultUtil::successResult($declareArrivals);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createArrival(Request $request)
    {
        $departLocation = null;

        $content = RequestUtil::getContentAsArray($request);
        $client = $this->getAccountOwner($request);
        $location = $this->getSelectedLocation($request);
        $loggedInUser = $this->getUser();

        $log = ActionLogWriter::declareArrivalOrImportPost($this->getManager(), $client, $loggedInUser, $location, $content);

        $content = $this->capitalizePedigreeNumberInPostArray($content);

        //Only verify if pedigree exists in our database and if the format is correct. Unknown ULNs are allowed
        $pedigreeValidation = $this->validateArrivalPost($content);
        if(!$pedigreeValidation->get(Constant::IS_VALID_NAMESPACE)) {
            return $pedigreeValidation->get(Constant::RESPONSE);
        }

        //LocationHealth null value fixes
        $this->healthService->fixLocationHealthMessagesWithNullValues($location);
        $this->healthService->fixArrivalsAndImportsWithoutLocationHealthMessage($location);

        $isImportAnimal = $content->get(Constant::IS_IMPORT_ANIMAL);

        //Convert the array into an object and add the mandatory values retrieved from the database
        if($isImportAnimal) { //DeclareImport

            //Validate if ulnNumber matches that of an unassigned Tag in the tag collection of the client
            $tagValidator = new TagValidator($this->getManager(), $client, $location, $content);
            if($tagValidator->getIsTagCollectionEmpty() || !$tagValidator->getIsTagValid() || $tagValidator->getIsInputEmpty()) {
                return $tagValidator->createImportJsonErrorResponse();
            }

            $messageObject = $this->buildMessageObject(RequestType::DECLARE_IMPORT_ENTITY, $content, $client, $loggedInUser, $location);
        } else {

            //DeclareArrival
            $content->set(JsonInputConstant::IS_ARRIVED_FROM_OTHER_NSFO_CLIENT, true);
            $messageObject = $this->buildMessageObject(RequestType::DECLARE_ARRIVAL_ENTITY, $content, $client, $loggedInUser, $location);

            /** @var Location $departLocation */
            $departLocation = $this->getManager()->getRepository(Location::class)->findOneBy(['ubn' => $messageObject->getUbnPreviousOwner(), 'isActive' => true]);

            if($departLocation) {
                $departOwner = $departLocation->getCompany()->getOwner();

                //DeclareDepart
                $depart = new DeclareDepart();
                $depart->setUlnCountryCode($messageObject->getUlnCountryCode());
                $depart->setUlnNumber($messageObject->getUlnNumber());
                $depart->setAnimal($messageObject->getAnimal());
                $depart->setIsExportAnimal(false);
                $depart->setDepartDate($messageObject->getArrivalDate());
                $depart->setReasonOfDepart("NO REASON");
                $depart->setAnimalObjectType(Utils::getClassName($messageObject->getAnimal()));
                $depart->setRelationNumberKeeper($departOwner->getRelationNumberKeeper());
                $depart->setUbn($departLocation->getUbn());
                $depart->setUbnNewOwner($location->getUbn());
                $depart->setRecoveryIndicator(RecoveryIndicatorType::N);

                $departMessage = new DepartMessageBuilder($this->getManager() , $this->environment);
                $departMessageObject = $departMessage->buildMessage($depart, $departOwner, $loggedInUser, $departLocation);
                $this->persist($departMessageObject);

                $this->sendMessageObjectToQueue($departMessageObject);
            }
        }

        //Send it to the queue and persist/update any changed state to the database
        $this->sendMessageObjectToQueue($messageObject);
        $messageObject->setAnimal(null);

        //Persist message without animal. That is done after a successful response
        $this->persist($messageObject);

        // Create Message for Receiving Owner
        if(!$isImportAnimal && $departLocation) {
            $uln = $messageObject->getUlnCountryCode() . $messageObject->getUlnNumber();

            $message = new Message();
            $message->setType(MessageType::DECLARE_ARRIVAL);
            $message->setSenderLocation($location);
            $message->setReceiverLocation($departLocation);
            $message->setRequestMessage($messageObject);
            $message->setData($uln);
            $this->persist($message);
        }

        $this->getManager()->flush();

        //Immediately update the locationHealth regardless or requestState type and persist a locationHealthMessage
        $this->healthService->updateLocationHealth($messageObject);

        ActionLogWriter::completeActionLog($this->getManager(), $log);

        $this->clearLivestockCacheForLocation($location);

        return new JsonResponse(array("status"=>"ok"), 200);
    }


    /**
     * @param Request $request
     * @param $Id
     * @return JsonResponse
     */
    public function updateArrival(Request $request, $Id) {

        $content = RequestUtil::getContentAsArray($request);
        $requestId = $Id;
        $content->set("request_id", $requestId);

        $client = $this->getAccountOwner($request);
        $location = $this->getSelectedLocation($request);
        $loggedInUser = $this->getUser();
        $content->set(Constant::LOCATION_NAMESPACE, $location);

        //verify requestId for arrivals
        $messageObject = $this->getManager()->getRepository(DeclareArrival::class)->getArrivalByRequestId($location, $requestId);

        if($messageObject == null) { //verify requestId for imports
            $messageObject = $this->getManager()->getRepository(DeclareImport::class)->getImportByRequestId($location, $requestId);
        }

        if($messageObject == null) {
            $errorMessage = "No DeclareArrival or DeclareImport found with request_id: " . $requestId;
            return new JsonResponse(array('code'=>428, "message" => $errorMessage), 428);
        }

        $isImportAnimal = $messageObject->getIsImportAnimal();
        $isFailedMessage = $messageObject->getRequestState() == RequestStateType::FAILED;

        if($isImportAnimal) { //For DeclareImport
            //Convert the array into an object and add the mandatory values retrieved from the database
            $messageObject = $this->buildEditMessageObject(RequestType::DECLARE_IMPORT_ENTITY, $content, $client, $loggedInUser, $location);

        } else { //For DeclareArrival
            //TODO Validate if ubnPreviousOwner matches the ubn of the animal with the given ULN, if the animal is in our database
//      $ubnValidator = new UbnValidator($this->getDoctrine()->getManager(), $content, $messageObject);
//      if(!$ubnValidator->getIsUbnValid()) {
//        return $ubnValidator->createArrivalJsonErrorResponse();
//      }

            //Convert the array into an object and add the mandatory values retrieved from the database
            $messageObject = $this->buildEditMessageObject(RequestType::DECLARE_ARRIVAL_ENTITY, $content, $client, $loggedInUser, $location);
        }

        //Send it to the queue and persist/update any changed requestState to the database
        $messageArray = $this->sendEditMessageObjectToQueue($messageObject);

        //Persist the update
        $this->persist($messageObject);
        $this->getManager()->flush();


        /* LocationHealth status updates are not necessary */

        /*
         * Import: An import (POST & PUT) always leads to the same LocationHealth update.
         *
         * Arrival: Only the arrival date is editable for Animals from other NSFO clients. The ubnPreviousOwner is editable for unknown locations.
         * In both cases the health status change would be identical to the change by the original arrival.
         *
         * We do not discriminate between successful and failed requests at this moment.
         */

        //log Animal location history
        $this->animalLocationHistoryService->logAnimalResidenceInEdit($messageObject);

        $this->clearLivestockCacheForLocation($location);

        return new JsonResponse($messageArray, 200);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getArrivalErrors(Request $request)
    {
        $location = $this->getSelectedLocation($request);
        $declareArrivals = $this->getManager()->getRepository(DeclareArrivalResponse::class)->getArrivalsWithLastErrorResponses($location);
        $declareImports = $this->getManager()->getRepository(DeclareImportResponse::class)->getImportsWithLastErrorResponses($location);

        return ResultUtil::successResult(['arrivals' => $declareArrivals, 'imports' => $declareImports]);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getArrivalHistory(Request $request)
    {
        $location = $this->getSelectedLocation($request);
        $declareArrivals = $this->getManager()->getRepository(DeclareArrivalResponse::class)->getArrivalsWithLastHistoryResponses($location);
        $declareImports = $this->getManager()->getRepository(DeclareImportResponse::class)->getImportsWithLastHistoryResponses($location);

        return ResultUtil::successResult(['arrivals' => $declareArrivals, 'imports' => $declareImports]);
    }


    /**
     * @param ArrayCollection $content
     * @param int $errorCode
     * @return ArrayCollection
     */
    private function validateArrivalPost(ArrayCollection $content, $errorCode = 428)
    {
        //Default values
        $result = new ArrayCollection();
        $jsonErrorResponse = null;
        $isValid = true;
        $result->set(Constant::IS_VALID_NAMESPACE, $isValid);
        $result->set(Constant::RESPONSE, $jsonErrorResponse);


        $animalArray = $content->get(Constant::ANIMAL_NAMESPACE);
        $pedigreeNumber = Utils::getNullCheckedArrayValue(JsonInputConstant::PEDIGREE_NUMBER, $animalArray);
        $pedigreeCountryCode = Utils::getNullCheckedArrayValue(JsonInputConstant::PEDIGREE_COUNTRY_CODE, $animalArray);

        //Don't check if uln was chosen instead of pedigree
        $pedigreeCodeExists = $pedigreeCountryCode != null && $pedigreeNumber != null;
        if(!$pedigreeCodeExists) {
            return $result;
        }


        $isFormatCorrect = Validator::verifyPedigreeNumberFormat($pedigreeNumber);

        if(!$isFormatCorrect) {
            $isValid = false;
            //TODO Translate message in English and match it with the translator in the Frontend
            $jsonErrorResponse = new JsonResponse(array('code'=>$errorCode,
                "pedigree" => $pedigreeCountryCode.$pedigreeNumber,
                "message" => "Het stamboeknummer moet deze structuur XXXXX-XXXXX hebben."), $errorCode);

        } else {
            $pedigreeInDatabaseVerification = $this->verifyOnlyPedigreeCodeInAnimal($animalArray);
            $isExistsInDatabase = $pedigreeInDatabaseVerification->get('isValid');

            if(!$isExistsInDatabase){
                $isValid = false;
                $jsonErrorResponse = new JsonResponse(array('code'=>$errorCode,
                    "pedigree" => $pedigreeCountryCode.$pedigreeNumber,
                    "message" => "PEDIGREE VALUE IS NOT REGISTERED WITH NSFO"), $errorCode);
            }
        }

        $result->set(Constant::IS_VALID_NAMESPACE, $isValid);
        $result->set(Constant::RESPONSE, $jsonErrorResponse);

        return $result;
    }


    /**
     * @param $animalArray
     * @return ArrayCollection
     */
    public function verifyOnlyPedigreeCodeInAnimal($animalArray)
    {
        $array = new ArrayCollection();

        $pedigreeCountryCode = Utils::getNullCheckedArrayValue(JsonInputConstant::PEDIGREE_COUNTRY_CODE, $animalArray);
        $pedigreeNumber = Utils::getNullCheckedArrayValue(JsonInputConstant::PEDIGREE_NUMBER, $animalArray);
        $isValid = Validator::verifyPedigreeCode($this->getManager(), $pedigreeCountryCode, $pedigreeNumber, true);

        if($pedigreeCountryCode != null && $pedigreeNumber != null) {
            $pedigree = $pedigreeCountryCode.$pedigreeNumber;
        } else {
            $pedigree = null;
        }

        $array->set('isValid', $isValid);
        $array->set(JsonInputConstant::PEDIGREE_NUMBER, $pedigreeNumber);
        $array->set(JsonInputConstant::PEDIGREE_COUNTRY_CODE, $pedigreeCountryCode);
        $array->set(Constant::PEDIGREE_NAMESPACE, $pedigree);

        return $array;
    }


    /**
     * @param ArrayCollection $content
     * @return ArrayCollection
     */
    private function capitalizePedigreeNumberInPostArray($content)
    {
        $animalArray = $content->get(Constant::ANIMAL_NAMESPACE);
        $pedigreeNumber = Utils::getNullCheckedArrayValue(JsonInputConstant::PEDIGREE_NUMBER, $animalArray);

        if($pedigreeNumber != null) {
            $pedigreeNumber = StringUtil::capitalizePedigreeNumber($pedigreeNumber);
            $animalArray[JsonInputConstant::PEDIGREE_NUMBER] = $pedigreeNumber;
            $content->set(Constant::ANIMAL_NAMESPACE, $animalArray);
        }

        return $content;
    }

}