<?php


namespace AppBundle\Service;


use AppBundle\Component\ArrivalMessageBuilder;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareDepartResponse;
use AppBundle\Entity\DeclareExport;
use AppBundle\Entity\DeclareExportResponse;
use AppBundle\Entity\Location;
use AppBundle\Entity\Message;
use AppBundle\Enumerator\MessageType;
use AppBundle\Enumerator\RecoveryIndicatorType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Exception\AnimalNotOnDepartLocationHttpException;
use AppBundle\Exception\DeadAnimalHttpException;
use AppBundle\Service\Google\FireBaseService;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\LocationHealthUpdater;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Worker\DirectProcessor\DeclareArrivalProcessorInterface;
use AppBundle\Worker\DirectProcessor\DeclareDepartProcessorInterface;
use AppBundle\Worker\DirectProcessor\DeclareExportProcessorInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DepartService extends DeclareControllerServiceBase
{
    /** @var string */
    private $environment;

    /** @var HealthUpdaterService */
    private $healthService;

    /** @var FireBaseService */
    private $fireBaseService;

    /** @var DeclareArrivalProcessorInterface */
    private $arrivalProcessor;
    /** @var DeclareDepartProcessorInterface */
    private $departProcessor;
    /** @var DeclareExportProcessorInterface */
    private $exportProcessor;

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
     * @param DeclareExportProcessorInterface $exportProcessor
     */
    public function setExportProcessor(DeclareExportProcessorInterface $exportProcessor): void
    {
        $this->exportProcessor = $exportProcessor;
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
     * @required Set at initialization
     *
     * @param $environment
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
    public function getDepartById(Request $request, $Id)
    {
        $location = $this->getSelectedLocation($request);
        $this->nullCheckLocation($location);
        $depart = $this->getManager()->getRepository(DeclareDepart::class)->getDepartureByRequestId($location, $Id);
        return new JsonResponse($depart, 200);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getDepartures(Request $request)
    {
        $location = $this->getSelectedLocation($request);
        $this->nullCheckLocation($location);

        $stateExists = $request->query->has(Constant::STATE_NAMESPACE);
        $repository = $this->getManager()->getRepository(DeclareDepart::class);

        if(!$stateExists) {
            $declareDepartures = $repository->getDepartures($location);

        } else if ($request->query->get(Constant::STATE_NAMESPACE) == Constant::HISTORY_NAMESPACE ) {

            $declareDepartures = new ArrayCollection();
            foreach($repository->getDepartures($location, RequestStateType::OPEN) as $depart) {
                $declareDepartures->add($depart);
            }
            foreach($repository->getDepartures($location, RequestStateType::REVOKING) as $depart) {
                $declareDepartures->add($depart);
            }
            foreach($repository->getDepartures($location, RequestStateType::FINISHED) as $depart) {
                $declareDepartures->add($depart);
            }

        } else { //A state parameter was given, use custom filter to find subset
            $state = $request->query->get(Constant::STATE_NAMESPACE);
            $declareDepartures = $repository->getDepartures($location, $state);
        }

        return ResultUtil::successResult($declareDepartures);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createDepartOrExport(Request $request)
    {
        $content = RequestUtil::getContentAsArrayCollection($request);
        return $content['is_export_animal'] ? $this->createExport($request) : $this->createDepart($request);
    }


    private function createDepart(Request $request)
    {
        $arrivalLocation = null;

        $content = RequestUtil::getContentAsArrayCollection($request);
        $client = $this->getAccountOwner($request);
        $loggedInUser = $this->getUser();
        $location = $this->getSelectedLocation($request);

        $this->nullCheckClient($client);
        $this->nullCheckLocation($location);

        $useRvoLogic = $location->isDutchLocation();
        $this->validateRelationNumberKeeperOfLocation($location);

        $departOrExportLog = ActionLogWriter::declareDepartOrExportPost($this->getManager(), $client, $loggedInUser, $location, $content);
        $arrivalLog = null;

        $animalArray = $content->get(Constant::ANIMAL_NAMESPACE);
        $this->verifyUlnOrPedigreeNumberFormatByAnimalArray($animalArray);
        $this->verifyIfAnimalIsOnLocation($location, $animalArray);

        $ubnNewOwner = $content->get(JsonInputConstant::UBN_NEW_OWNER);
        $this->verifyUbnFormat($ubnNewOwner, $location->isDutchLocation());
        $this->verifyIfDepartureAndArrivalUbnAreIdentical($location->getUbn(), $ubnNewOwner);
        $this->verifyIfDepartDoesNotExistYet($content, $location);

        //Convert the array into an object and add the mandatory values retrieved from the database
        $depart = $this->buildMessageObject(RequestType::DECLARE_DEPART_ENTITY, $content, $client, $loggedInUser, $location);

        /** @var Location $arrivalLocation */
        $repository = $this->getManager()->getRepository(Location::class);
        $arrivalLocation = $repository->findOneBy(['ubn' => $depart->getUbnNewOwner(), 'isActive' => true]);

        $this->validateIfOriginAndDestinationAreInSameCountry(DeclareDepart::class, $location, $arrivalLocation);

        if (!$useRvoLogic) {
            $this->validateNonRvoSpecificDepartConditions($depart);
        }

        $animal = $depart->getAnimal();
        $arrival = null;
        if($arrivalLocation) {
            $this->validateRelationNumberKeeperOfLocation($arrivalLocation);
            $arrivalOwner = $arrivalLocation->getCompany()->getOwner();

            //DeclareArrival
            $arrival = new DeclareArrival();
            $arrival->setUlnCountryCode($depart->getUlnCountryCode());
            $arrival->setUlnNumber($depart->getUlnNumber());
            $arrival->setAnimal($depart->getAnimal());
            $arrival->setArrivalDate($depart->getDepartDate());
            $arrival->setIsImportAnimal(false);
            $arrival->setAnimalObjectType(Utils::getClassName($depart->getAnimal()));
            $arrival->setRelationNumberKeeper($arrivalOwner->getRelationNumberKeeper());
            $arrival->setUbn($arrivalLocation->getUbn());
            $arrival->setUbnPreviousOwner($location->getUbn());
            $arrival->setRecoveryIndicator(RecoveryIndicatorType::N);
            $arrival->setIsArrivedFromOtherNsfoClient(true);

            $arrivalMessageBuilder = new ArrivalMessageBuilder($this->getManager(), $this->environment);
            $arrival = $arrivalMessageBuilder->buildMessage($arrival, $arrivalOwner, $loggedInUser, $arrivalLocation);
            $this->persist($arrival);

            if ($useRvoLogic) {
                $this->sendMessageObjectToQueue($arrival);
            } else {
                $this->arrivalProcessor->process($arrival, $location);
            }

            $checkHealthStatusArrivalLocation = LocationHealthUpdater::checkHealthStatus($arrivalLocation);
            if ($checkHealthStatusArrivalLocation) {
                //Immediately update the locationHealth regardless or requestState type and persist a locationHealthMessage
                $this->healthService->updateLocationHealth($arrival, $animal);
            }

            $arrivalLog = ActionLogWriter::declareArrival($arrival, $arrivalOwner, true);
        }

        if ($useRvoLogic) {
            //Send it to the queue and persist/update any changed state to the database
            $messageArray = $this->sendMessageObjectToQueue($depart);

            //Reset isExportAnimal to false before persisting
            $depart->getAnimal()->setIsExportAnimal(false);

            //Persist object to Database
            $this->persist($depart);

            $depart->getAnimal()->setTransferringTransferState();
            $this->getManager()->persist($depart->getAnimal());
            $this->getManager()->flush();

            $this->clearLivestockCacheForLocation($location);

        } else {
            $messageArray = $this->departProcessor->process($depart, $arrivalLocation);
        }

        $this->createDepartArrivalTransaction($arrival, $depart, $loggedInUser, $useRvoLogic,false);
        $this->getManager()->flush();

        // Create Message for Receiving Owner
        if($arrivalLocation) {
            $uln = $depart->getAnimal()->getUlnCountryCode() . $depart->getAnimal()->getUlnNumber();

            $message = new Message();
            $message->setType(MessageType::DECLARE_DEPART);
            $message->setSenderLocation($location);
            $message->setReceiverLocation($arrivalLocation);
            $message->setRequestMessage($depart);
            $message->setData($uln);
            $this->persist($message);

            $this->fireBaseService->sendNsfoMessageToUser($location->getOwner(), $message);
        }

        $this->saveNewestDeclareVersion($content, $depart);

        if ($arrivalLog) { $this->persist($arrivalLog); }
        ActionLogWriter::completeActionLog($this->getManager(), $departOrExportLog);

        return new JsonResponse($messageArray, Response::HTTP_OK);
    }


    private function createExport(Request $request)
    {
        $content = RequestUtil::getContentAsArrayCollection($request);
        $client = $this->getAccountOwner($request);
        $loggedInUser = $this->getUser();
        $location = $this->getSelectedLocation($request);

        $this->nullCheckClient($client);
        $this->nullCheckLocation($location);
        $this->validateRelationNumberKeeperOfLocation($location);

        $useRvoLogic = $location->isDutchLocation();

        $departOrExportLog = ActionLogWriter::declareDepartOrExportPost($this->getManager(), $client, $loggedInUser, $location, $content);
        $arrivalLog = null;

        $this->verifyIfAnimalIsOnLocation($location, $content->get(Constant::ANIMAL_NAMESPACE));

        //Convert the array into an object and add the mandatory values retrieved from the database
        $export = $this->buildMessageObject(RequestType::DECLARE_EXPORT_ENTITY, $content, $client, $loggedInUser, $location);

        if (!$useRvoLogic) {
            $this->validateNonRvoSpecificExportConditions($export);
        }

        $messageArray = $this->runDeclareExportWorkerLogic($export, $content);

        if ($arrivalLog) { $this->persist($arrivalLog); }
        ActionLogWriter::completeActionLog($this->getManager(), $departOrExportLog);

        $this->clearLivestockCacheForLocation($location);

        return new JsonResponse($messageArray, Response::HTTP_OK);
    }


    public function runDeclareExportWorkerLogic(DeclareExport $export, ArrayCollection $content)
    {
        if ($export->isRvoMessage()) {
            //Send it to the queue and persist/update any changed state to the database
            $messageArray = $this->sendMessageObjectToQueue($export);

            //Reset isExportAnimal to false before persisting
            $export->getAnimal()->setIsExportAnimal(false);
            $export->getAnimal()->setTransferringTransferState();
            $this->getManager()->persist($export->getAnimal());
            $this->getManager()->persist($export);
            $this->getManager()->flush();

        } else {
            $messageArray = $this->exportProcessor->process($export);
        }

        $this->saveNewestDeclareVersion($content, $export);

        return $messageArray;
    }


    /**
     * @param Request $request
     * @param $Id
     * @return JsonResponse
     */
    public function updateDepart(Request $request, $Id)
    {
        $content = RequestUtil::getContentAsArrayCollection($request);

        //Client can only depart/export own animals
        $client = $this->getAccountOwner($request);
        $loggedInUser = $this->getUser();
        $location = $this->getSelectedLocation($request);

        $this->nullCheckClient($client);
        $this->nullCheckLocation($location);

        $this->verifyUbnFormat($content->get(JsonInputConstant::UBN_NEW_OWNER), $location->isDutchLocation());

        //NOTE!!! Don't try to verify any animals directly. Because they will have the isDeparted=true state.
        //Verify this request using the requestId
        $animal = $content->get(Constant::ANIMAL_NAMESPACE);
        //TODO verify if Updated request had was successful or not and set RecoveryIndicator accordingly

        $isExportAnimal = $content['is_export_animal'];

        //TODO Phase 2+: Validate if declare type (export or import) from RequestId matches type read from ['is_export_animal']

        if($isExportAnimal) {
            //Convert the array into an object and add the mandatory values retrieved from the database
            $declareExportUpdate = $this->buildEditMessageObject(RequestType::DECLARE_EXPORT_ENTITY, $content, $client, $loggedInUser, $location);

            $messageObject = $this->getManager()->getRepository(DeclareExport::class)->updateDeclareExportMessage($declareExportUpdate, $location, $Id);

            if($messageObject == null) {
                return ResultUtil::errorResult("No DeclareDepart found with request_id: " . $Id,204);
            }

        } else {
            //Convert the array into an object and add the mandatory values retrieved from the database
            $declareDepartUpdate = $this->buildMessageObject(RequestType::DECLARE_DEPART_ENTITY, $content, $client, $loggedInUser, $location);

            $entityManager = $this->getManager()->getRepository(DeclareDepart::class);
            $messageObject = $entityManager->updateDeclareDepartMessage($declareDepartUpdate, $location, $Id);

            if($messageObject == null) {
                return ResultUtil::errorResult("No DeclareDepart found with request_id: " . $Id,204);
            }
        }
        //Send it to the queue and persist/update any changed state to the database
        $messageArray = $this->sendEditMessageObjectToQueue($messageObject);

        //Reset isExportAnimal to false before persisting
        $messageObject->getAnimal()->setIsExportAnimal(false);
        $messageObject->getAnimal()->setTransferringTransferState();
        $this->getManager()->persist($messageObject->getAnimal());

        //First Persist object to Database, before sending it to the queue
        $this->persist($messageObject);
        $this->getManager()->flush();

        //updating the Animal location history is done completely in the worker

        return new JsonResponse($messageArray, 200);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getDepartErrors(Request $request)
    {
        $location = $this->getSelectedLocation($request);
        $this->nullCheckLocation($location);

        $repository = $this->getManager()->getRepository(DeclareDepartResponse::class);
        $declareDeparts = $repository->getDeparturesWithLastErrorResponses($location);

        $repository = $this->getManager()->getRepository(DeclareExportResponse::class);
        $declareExports = $repository->getExportsWithLastErrorResponses($location);

        return ResultUtil::successResult(['departs' => $declareDeparts, 'exports' => $declareExports]);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getDepartHistory(Request $request)
    {
        $location = $this->getSelectedLocation($request);
        $this->nullCheckLocation($location);

        $page = $request->query->getInt('page', 1);
        $searchQuery = $request->get('query', '');

        $repository = $this->getManager()->getRepository(DeclareDepartResponse::class);
        $declareDeparts = $repository->getDeparturesWithLastHistoryResponses($location, $page, $searchQuery);

        $repository = $this->getManager()->getRepository(DeclareExportResponse::class);
        $declareExports = $repository->getExportsWithLastHistoryResponses($location, $page, $searchQuery);

        return ResultUtil::successResult(['departs' => $declareDeparts, 'exports' => $declareExports]);
    }


    public function getDepartDatesAndUbnNewOwners(Request $request)
    {
        $location = $this->getSelectedLocation($request);
        $this->nullCheckLocation($location);

        return $this->getManager()->getRepository(DeclareDepart::class)
            ->getDepartDateAndUbnNewOwners($location->getId());
    }


    private function validateNonRvoSpecificDepartConditions(DeclareDepart $depart)
    {
        $animal = $depart->getAnimal();
        if (!$animal) {
            return;
        }

        if ($animal->getUbn() !== $depart->getUbn()) {
            throw new AnimalNotOnDepartLocationHttpException($this->translator, $animal);
        }

        if (!$animal->getIsAlive()) {
            throw new DeadAnimalHttpException($this->translator, $animal->getUln());
        }

        $this->validateIfEventDateIsNotBeforeDateOfBirth($animal, $depart->getDepartDate());
    }


    private function validateNonRvoSpecificExportConditions(DeclareExport $export)
    {
        $animal = $export->getAnimal();
        if (!$animal) {
            return;
        }

        if (!$animal->getIsAlive()) {
            throw new DeadAnimalHttpException($this->translator, $animal->getUln());
        }

        $this->validateIfEventDateIsNotBeforeDateOfBirth($animal, $export->getExportDate());
    }


    /**
     * @param ArrayCollection $content
     * @param Location $location
     */
    private function verifyIfDepartDoesNotExistYet(ArrayCollection $content, Location $location)
    {
        $departs = $this->getManager()->getRepository(DeclareDepart::class)
            ->findByDeclareInput($content, $location, false);
        $this->verifyIfDeclareDoesNotExistYet(DeclareDepart::class, $departs);
    }
}
