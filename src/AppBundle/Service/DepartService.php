<?php


namespace AppBundle\Service;


use AppBundle\Component\ArrivalMessageBuilder;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\RequestMessageBuilder;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
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
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class DepartService extends DeclareControllerServiceBase
{
    /** @var string */
    private $environment;

    public function __construct(AwsExternalQueueService $externalQueueService,
                                CacheService $cacheService,
                                EntityManagerInterface $manager,
                                IRSerializer $irSerializer,
                                RequestMessageBuilder $requestMessageBuilder,
                                UserService $userService,
                                $environment)
    {
        parent::__construct($externalQueueService, $cacheService, $manager, $irSerializer, $requestMessageBuilder, $userService);
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
    public function createDepart(Request $request)
    {
        $arrivalLocation = null;

        $content = RequestUtil::getContentAsArray($request);
        $client = $this->getAccountOwner($request);
        $loggedInUser = $this->getUser();
        $location = $this->getSelectedLocation($request);

        $departOrExportLog = ActionLogWriter::declareDepartOrExportPost($this->getManager(), $client, $loggedInUser, $location, $content);
        $arrivalLog = null;

        //Client can only depart/export own animals
        $animal = $content->get(Constant::ANIMAL_NAMESPACE);
        $isAnimalOfClient = $this->getManager()->getRepository(Animal::class)->verifyIfClientOwnsAnimal($client, $animal);

        if(!$isAnimalOfClient) {
            return new JsonResponse(array('code'=>428, "message" => "Animal doesn't belong to this account."), 428);
        }

        $isExportAnimal = $content['is_export_animal'];

        if($isExportAnimal) {
            //Convert the array into an object and add the mandatory values retrieved from the database
            $messageObject = $this->buildMessageObject(RequestType::DECLARE_EXPORT_ENTITY, $content, $client, $loggedInUser, $location);

        } else {
            //Convert the array into an object and add the mandatory values retrieved from the database
            $messageObject = $this->buildMessageObject(RequestType::DECLARE_DEPART_ENTITY, $content, $client, $loggedInUser, $location);

            /** @var Location $arrivalLocation */
            $repository = $this->getManager()->getRepository(Location::class);
            $arrivalLocation = $repository->findOneBy(['ubn' => $messageObject->getUbnNewOwner(), 'isActive' => true]);

            if($arrivalLocation) {
                $arrivalOwner = $arrivalLocation->getCompany()->getOwner();

                //DeclareArrival
                $arrival = new DeclareArrival();
                $arrival->setUlnCountryCode($messageObject->getUlnCountryCode());
                $arrival->setUlnNumber($messageObject->getUlnNumber());
                $arrival->setAnimal($messageObject->getAnimal());
                $arrival->setArrivalDate($messageObject->getDepartDate());
                $arrival->setIsImportAnimal(false);
                $arrival->setAnimalObjectType(Utils::getClassName($messageObject->getAnimal()));
                $arrival->setRelationNumberKeeper($arrivalOwner->getRelationNumberKeeper());
                $arrival->setUbn($arrivalLocation->getUbn());
                $arrival->setUbnPreviousOwner($location->getUbn());
                $arrival->setRecoveryIndicator(RecoveryIndicatorType::N);
                $arrival->setIsArrivedFromOtherNsfoClient(true);

                $arrivalMessage = new ArrivalMessageBuilder($this->getManager(), $this->environment);
                $arrivalMessageObject = $arrivalMessage->buildMessage($arrival, $arrivalOwner, $loggedInUser, $arrivalLocation);
                $this->persist($arrivalMessageObject);

                $this->sendMessageObjectToQueue($arrivalMessageObject);

                $arrivalLog = ActionLogWriter::declareArrival($arrival, $arrivalOwner, true);
            }
        }

        //Send it to the queue and persist/update any changed state to the database
        $messageArray = $this->sendMessageObjectToQueue($messageObject);

        //Reset isExportAnimal to false before persisting
        $messageObject->getAnimal()->setIsExportAnimal(false);

        //Persist object to Database
        $this->persist($messageObject);

        // Create Message for Receiving Owner
        if(!$isExportAnimal && $arrivalLocation) {
            $uln = $messageObject->getAnimal()->getUlnCountryCode() . $messageObject->getAnimal()->getUlnNumber();

            $message = new Message();
            $message->setType(MessageType::DECLARE_DEPART);
            $message->setSenderLocation($location);
            $message->setReceiverLocation($arrivalLocation);
            $message->setRequestMessage($messageObject);
            $message->setData($uln);
            $this->persist($message);
        }

        $this->persistAnimalTransferringStateAndFlush($messageObject->getAnimal());

        if ($arrivalLog) { $this->persist($arrivalLog); }
        ActionLogWriter::completeActionLog($this->getManager(), $departOrExportLog);

        $this->clearLivestockCacheForLocation($location);

        return new JsonResponse($messageArray, 200);
    }


    /**
     * @param Request $request
     * @param $Id
     * @return JsonResponse
     */
    public function updateDepart(Request $request, $Id)
    {
        $content = RequestUtil::getContentAsArray($request);

        //Client can only depart/export own animals
        $client = $this->getAccountOwner($request);
        $loggedInUser = $this->getUser();
        $location = $this->getSelectedLocation($request);

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

        //First Persist object to Database, before sending it to the queue
        $this->persist($messageObject);
        $this->persistAnimalTransferringStateAndFlush($messageObject->getAnimal());

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

        $repository = $this->getManager()->getRepository(DeclareDepartResponse::class);
        $declareDeparts = $repository->getDeparturesWithLastHistoryResponses($location);

        $repository = $this->getManager()->getRepository(DeclareExportResponse::class);
        $declareExports = $repository->getExportsWithLastHistoryResponses($location);

        return ResultUtil::successResult(['departs' => $declareDeparts, 'exports' => $declareExports]);
    }
}