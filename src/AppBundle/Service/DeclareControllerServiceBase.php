<?php


namespace AppBundle\Service;


use AppBundle\Component\Modifier\MessageModifier;
use AppBundle\Component\Utils;
use AppBundle\Entity\Animal;
use AppBundle\Entity\DeclareBase;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\Enumerator\AnimalTransferStatus;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Output\RequestMessageOutputBuilder;
use AppBundle\Worker\Task\WorkerMessageBody;
use Doctrine\ORM\EntityManagerInterface;

abstract class DeclareControllerServiceBase extends ControllerServiceBase
{
    /** @var EntityGetter */
    protected $entityGetter;
    /** @var AwsExternalQueueService */
    protected $externalQueueService;
    /** @var AwsInternalQueueService */
    protected $internalQueueService;

    public function __construct(EntityManagerInterface $em, IRSerializer $serializer, CacheService $cacheService,
                                UserService $userService, AwsExternalQueueService $externalQueueService, AwsInternalQueueService $internalQueueService, EntityGetter $entityGetter)
    {
        parent::__construct($em, $serializer, $cacheService, $userService);

        $this->entityGetter = $entityGetter;
        $this->externalQueueService = $externalQueueService;
        $this->internalQueueService = $internalQueueService;
    }

    /**
     * @param DeclareBase $messageObject
     * @param bool $isUpdate
     * @return array
     */
    protected function sendMessageObjectToQueue($messageObject, $isUpdate = false) {

        $requestId = $messageObject->getRequestId();
        $repository = $this->em->getRepository(Utils::getRepositoryNameSpace($messageObject));

        //create array and jsonMessage
        $messageArray = RequestMessageOutputBuilder::createOutputArray($this->em, $messageObject, $isUpdate);

        if($messageArray == null) {
            //These objects do not have a customized minimal json output for the queue yet
            $jsonMessage = $this->serializer->serializeToJSON($messageObject);
            $messageArray = json_decode($jsonMessage, true);
        } else {
            //Use the minimized custom output
            $jsonMessage = $this->serializer->serializeToJSON($messageArray);
        }

        //Send serialized message to Queue
        $requestTypeNameSpace = RequestType::getRequestTypeFromObject($messageObject);

        $sendToQresult = $this->externalQueueService->send($jsonMessage, $requestTypeNameSpace, $requestId);

        //If send to Queue, failed, it needs to be resend, set state to failed
        if ($sendToQresult['statusCode'] != '200') {
            $messageObject->setRequestState(RequestStateType::FAILED);
            $messageObject = MessageModifier::modifyBeforePersistingRequestStateByQueueStatus($messageObject, $this->em);
            $this->persist($messageObject);

        } else if($isUpdate) { //If successfully sent to the queue and message is an Update/Edit request
            $messageObject->setRequestState(RequestStateType::OPEN); //update the RequestState
            $messageObject = MessageModifier::modifyBeforePersistingRequestStateByQueueStatus($messageObject, $this->em);
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

        $jsonMessage = $this->serializer->serializeToJSON($workerMessageBody);

        //Send  message to Queue
        $sendToQresult = $this->internalQueueService->send($jsonMessage, $workerMessageBody->getTaskType(), 1);

        //If send to Queue, failed, it needs to be resend, set state to failed
        return $sendToQresult['statusCode'] == '200';
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
        $messageObjectTobeRevoked = $this->entityGetter->getRequestMessageByMessageNumber($messageNumber);

        $messageObjectWithRevokedRequestState = $messageObjectTobeRevoked->setRequestState(RequestStateType::REVOKING);

        $this->persist($messageObjectWithRevokedRequestState);
    }


    /**
     * @param Animal|Ram|Ewe|Neuter $animal
     */
    public function persistAnimalTransferringStateAndFlush($animal)
    {
        $animal->setTransferState(AnimalTransferStatus::TRANSFERRING);
        $this->em->persist($animal);
        $this->em->flush();
    }

}