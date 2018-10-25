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
use AppBundle\Entity\DepartArrivalTransaction;
use AppBundle\Entity\Location;
use AppBundle\Entity\Message;
use AppBundle\Enumerator\MessageType;
use AppBundle\Enumerator\RecoveryIndicatorType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Service\Google\FireBaseService;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\Validator;
use AppBundle\Validation\TagValidator;
use AppBundle\Worker\DirectProcessor\DeclareArrivalProcessorInterface;
use AppBundle\Worker\DirectProcessor\DeclareDepartProcessorInterface;
use AppBundle\Worker\DirectProcessor\DeclareImportProcessorInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

class ArrivalService extends DeclareControllerServiceBase implements ArrivalAPIControllerInterface
{
    /** @var HealthUpdaterService */
    private $healthService;
    /** @var AnimalLocationHistoryService */
    private $animalLocationHistoryService;
    /** @var string */
    private $environment;
    /** @var FireBaseService */
    private $fireBaseService;
    /** @var DeclareArrivalProcessorInterface */
    private $arrivalProcessor;
    /** @var DeclareDepartProcessorInterface */
    private $departProcessor;
    /** @var DeclareImportProcessorInterface */
    private $importProcessor;

    /**
     * @required
     *
     * @param DeclareArrivalProcessorInterface $arrivalProcessor
     */
    public function setArrivalProcessor(DeclareArrivalProcessorInterface $arrivalProcessor): void
    {
        $this->arrivalProcessor = $arrivalProcessor;
    }

    /**
     * @required
     *
     * @param DeclareDepartProcessorInterface $departProcessor
     */
    public function setDepartProcessor(DeclareDepartProcessorInterface $departProcessor): void
    {
        $this->departProcessor = $departProcessor;
    }

    /**
     * @required
     *
     * @param DeclareImportProcessorInterface $importProcessor
     */
    public function setImportProcessor(DeclareImportProcessorInterface $importProcessor): void
    {
        $this->importProcessor = $importProcessor;
    }

    /**
     * @required
     *
     * @param FireBaseService $fireBaseService
     */
    public function setFireBaseService(FireBaseService $fireBaseService) {
        $this->fireBaseService = $fireBaseService;
    }

    /**
     * @required
     *
     * @param HealthUpdaterService $healthService
     */
    public function setHealthUpdaterService(HealthUpdaterService $healthService)
    {
        $this->healthService = $healthService;
    }

    /**
     * @required
     *
     * @param AnimalLocationHistoryService $animalLocationHistoryService
     */
    public function setAnimalLocationHistoryService(AnimalLocationHistoryService $animalLocationHistoryService)
    {
        $this->animalLocationHistoryService = $animalLocationHistoryService;
    }

    /**
     * @required
     *
     * @param string $environment
     */
    public function setEnvironment($environment)
    {
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
        $this->nullCheckLocation($location);
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
        $this->nullCheckLocation($location);

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
    public function createArrivalOrImport(Request $request)
    {
        $content = RequestUtil::getContentAsArray($request);
        if ($content->get(Constant::IS_IMPORT_ANIMAL)) {
            return $this->createImport($request);
        }
        return $this->createArrival($request);
    }


    private function createArrival(Request $request)
    {
        $content = RequestUtil::getContentAsArray($request);
        $content = $this->capitalizePedigreeNumberInPostArray($content);

        $client = $this->getAccountOwner($request);
        $location = $this->getSelectedLocation($request);
        $loggedInUser = $this->getUser();

        $this->nullCheckClient($client);
        $this->nullCheckLocation($location);

        $useRvoLogic = $location->isDutchLocation();

        $arrivalOrImportLog = ActionLogWriter::declareArrivalOrImportPost($this->getManager(), $client, $loggedInUser, $location, $content);

        //Only verify if pedigree exists in our database and if the format is correct. Unknown ULNs are allowed
        $pedigreeValidation = $this->validateArrivalPost($content, $location->isDutchLocation());
        if(!$pedigreeValidation->get(Constant::IS_VALID_NAMESPACE)) {
            return $pedigreeValidation->get(Constant::RESPONSE);
        }

        $departLocation = $this->getValidatedLocationPreviousOwner($location, $content->get(Constant::UBN_PREVIOUS_OWNER_NAMESPACE));

        $this->validateIfOriginAndDestinationAreInSameCountry(DeclareArrival::class, $departLocation, $location);

        if ($location->getAnimalHealthSubscription()) {
            //LocationHealth null value fixes
            $this->healthService->fixLocationHealthMessagesWithNullValues($location);
            $this->healthService->fixIncongruentLocationHealthIllnessValues($location);
        }

        //Convert the array into an object and add the mandatory values retrieved from the database
        $content->set(JsonInputConstant::IS_ARRIVED_FROM_OTHER_NSFO_CLIENT, true);
        $arrival = $this->buildMessageObject(RequestType::DECLARE_ARRIVAL_ENTITY, $content, $client, $loggedInUser, $location);

        $departLog = null;
        $depart = null;
        if ($departLocation) {
            $departOwner = $departLocation->getCompany()->getOwner();

            //DeclareDepart
            $depart = new DeclareDepart();
            $depart->setUlnCountryCode($arrival->getUlnCountryCode());
            $depart->setUlnNumber($arrival->getUlnNumber());
            $depart->setAnimal($arrival->getAnimal());
            $depart->setIsExportAnimal(false);
            $depart->setDepartDate($arrival->getArrivalDate());
            $depart->setReasonOfDepart("NO REASON");
            $depart->setAnimalObjectType(Utils::getClassName($arrival->getAnimal()));
            $depart->setRelationNumberKeeper($departOwner->getRelationNumberKeeper());
            $depart->setUbn($departLocation->getUbn());
            $depart->setUbnNewOwner($location->getUbn());
            $depart->setRecoveryIndicator(RecoveryIndicatorType::N);

            $departMessageBuilder = new DepartMessageBuilder($this->getManager() , $this->environment);
            $depart = $departMessageBuilder->buildMessage($depart, $departOwner, $loggedInUser, $departLocation);

            if ($useRvoLogic) {
                $this->persist($depart);
                $this->sendMessageObjectToQueue($depart);
            } else {
                $this->departProcessor->process($depart, $location);
            }

            $departLog = ActionLogWriter::declareDepart($depart, $departOwner, true);
        }

        if ($useRvoLogic) {
            //Send it to the queue and persist/update any changed state to the database
            $outputArray = $this->sendMessageObjectToQueue($arrival);
            $arrival->setAnimal(null);

            //Persist message without animal. That is done after a successful response
            $this->persist($arrival);

        } else {
            $outputArray = $this->arrivalProcessor->process($arrival, $departLocation);
        }

        $this->createDepartArrivalTransaction($arrival, $depart, $loggedInUser, $useRvoLogic,true);

        // Create Message for Receiving Owner
        if($departLocation) {
            $uln = $arrival->getUlnCountryCode() . $arrival->getUlnNumber();

            $message = new Message();
            $message->setType(MessageType::DECLARE_ARRIVAL);
            $message->setSenderLocation($location);
            $message->setReceiverLocation($departLocation);
            $message->setRequestMessage($arrival);
            $message->setData($uln);
            $this->persist($message);
            foreach($location->getOwner()->getMobileDevices() as $mobileDevice) {
                $title = $this->translator->trans($message->getNotificationMessageTranslationKey());
                $this->fireBaseService->sendMessageToDevice($mobileDevice->getRegistrationToken(), $title, $message->getData());
            }
        }

        $this->getManager()->flush();

        $this->saveNewestDeclareVersion($content, $arrival);

        if ($location->getAnimalHealthSubscription()) {
            //Immediately update the locationHealth regardless or requestState type and persist a locationHealthMessage
            $this->healthService->updateLocationHealth($arrival);
        }

        if ($departLog) { $this->persist($departLog); }
        ActionLogWriter::completeActionLog($this->getManager(), $arrivalOrImportLog);

        $this->clearLivestockCacheForLocation($location);

        return $outputArray;
    }


    private function createImport(Request $request)
    {
        $content = RequestUtil::getContentAsArray($request);
        $content = $this->capitalizePedigreeNumberInPostArray($content);

        $client = $this->getAccountOwner($request);
        $location = $this->getSelectedLocation($request);
        $loggedInUser = $this->getUser();

        $this->nullCheckClient($client);
        $this->nullCheckLocation($location);

        $actionLog = ActionLogWriter::declareArrivalOrImportPost($this->getManager(), $client, $loggedInUser, $location, $content);

        //Only verify if pedigree exists in our database and if the format is correct. Unknown ULNs are allowed
        $pedigreeValidation = $this->validateArrivalPost($content, $location->isDutchLocation());
        if(!$pedigreeValidation->get(Constant::IS_VALID_NAMESPACE)) {
            return $pedigreeValidation->get(Constant::RESPONSE);
        }

        //Convert the array into an object and add the mandatory values retrieved from the database
        //Validate if ulnNumber matches that of an unassigned Tag in the tag collection of the client
        $tagValidator = new TagValidator($this->getManager(), $client, $location, $content);
        if($tagValidator->getIsTagCollectionEmpty() || !$tagValidator->getIsTagValid() || $tagValidator->getIsInputEmpty()) {
            return $tagValidator->createImportJsonErrorResponse();
        }

        if ($location->getAnimalHealthSubscription()) {
            //LocationHealth null value fixes
            $this->healthService->fixLocationHealthMessagesWithNullValues($location);
            $this->healthService->fixIncongruentLocationHealthIllnessValues($location);
        }

        $import = $this->buildMessageObject(RequestType::DECLARE_IMPORT_ENTITY, $content, $client, $loggedInUser, $location);

        //Send it to the queue and persist/update any changed state to the database
       $messageArray = $this->runDeclareImportWorkerLogic($import);

        $this->saveNewestDeclareVersion($content, $import);

        if ($location->getAnimalHealthSubscription()) {
            //Immediately update the locationHealth regardless or requestState type and persist a locationHealthMessage
            $this->healthService->updateLocationHealth($import);
        }

        ActionLogWriter::completeActionLog($this->getManager(), $actionLog);

        $this->clearLivestockCacheForLocation($location);

        return $messageArray;
    }


    private function runDeclareImportWorkerLogic(DeclareImport $import)
    {
        if ($import->isRvoMessage()) {
            //Send it to the queue and persist/update any changed state to the database
            $messageArray = $this->sendMessageObjectToQueue($import);
            $import->setAnimal(null);
            //Persist message without animal. That is done after a successful response
            $this->persist($import);
            $this->getManager()->flush();

            return $messageArray;
        }

        // DO NOT remove animal from import before importProcessor
        return $this->importProcessor->process($import);
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

        $this->nullCheckClient($client);
        $this->nullCheckLocation($location);

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
        $this->nullCheckLocation($location);

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
        $this->nullCheckLocation($location);

        $declareArrivals = $this->getManager()->getRepository(DeclareArrivalResponse::class)->getArrivalsWithLastHistoryResponses($location);
        $declareImports = $this->getManager()->getRepository(DeclareImportResponse::class)->getImportsWithLastHistoryResponses($location);

        return ResultUtil::successResult(['arrivals' => $declareArrivals, 'imports' => $declareImports]);
    }


    /**
     * @param ArrayCollection $content
     * @param bool $isDutchLocation
     * @return ArrayCollection
     */
    private function validateArrivalPost(ArrayCollection $content, $isDutchLocation)
    {
        $errorCode = Response::HTTP_PRECONDITION_REQUIRED;

        //Default values
        $result = new ArrayCollection();
        $jsonErrorResponse = null;
        $isValid = true;
        $result->set(Constant::IS_VALID_NAMESPACE, $isValid);
        $result->set(Constant::RESPONSE, $jsonErrorResponse);


        $animalArray = $content->get(Constant::ANIMAL_NAMESPACE);
        $pedigreeNumber = Utils::getNullCheckedArrayValue(JsonInputConstant::PEDIGREE_NUMBER, $animalArray);
        $pedigreeCountryCode = Utils::getNullCheckedArrayValue(JsonInputConstant::PEDIGREE_COUNTRY_CODE, $animalArray);
        $this->verifyUbnFormat($content->get(JsonInputConstant::UBN_PREVIOUS_OWNER), $isDutchLocation);

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


    /**
     * @param Location $locationOfDestination
     * @param string $ubnOfPreviousOwner
     * @return Location|null
     */
    private function getValidatedLocationPreviousOwner(Location $locationOfDestination, $ubnOfPreviousOwner): ?Location
    {
        if (empty($ubnOfPreviousOwner)) {
            return null;
        }

        self::verifyIfDepartureAndArrivalUbnAreIdentical($locationOfDestination->getUbn(), $ubnOfPreviousOwner);
        $ubnOfPreviousOwner = StringUtil::preformatUbn($ubnOfPreviousOwner);

        /** @var Location $departLocation */
        $departLocation = $this->getManager()->getRepository(Location::class)
            ->findOneBy(['ubn' => $ubnOfPreviousOwner, 'isActive' => true]);

        if (empty($departLocation)) {
            return null;
        }

        if ($locationOfDestination->getCountryCode() !== $departLocation->getCountryCode()
        && !empty($locationOfDestination->getCountryCode())) {
            throw new PreconditionFailedHttpException($this->translator->trans('ARRIVALS ARE ONLY ALLOWED BETWEEN UBNS FROM THE SAME COUNTRY')
                .'. '.$ubnOfPreviousOwner. '['.$departLocation->getCountryCode().']'
                .' => '.$locationOfDestination->getUbn().' ['.$locationOfDestination->getCountryCode().']'
            );
        }

        return $departLocation;
    }
}