<?php


namespace AppBundle\Service;


use AppBundle\Component\Modifier\MessageModifier;
use AppBundle\Component\RequestMessageBuilder;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\DeclarationDetail;
use AppBundle\Entity\DeclareAnimalFlag;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareBase;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareExport;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\DeclareLoss;
use AppBundle\Entity\DeclareNsfoBase;
use AppBundle\Entity\DeclareTagsTransfer;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Person;
use AppBundle\Entity\Ram;
use AppBundle\Entity\RetrieveAnimals;
use AppBundle\Entity\RetrieveCountries;
use AppBundle\Entity\RetrieveTags;
use AppBundle\Entity\RetrieveUbnDetails;
use AppBundle\Entity\RevokeDeclaration;
use AppBundle\Enumerator\AnimalTransferStatus;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Output\RequestMessageOutputBuilder;
use AppBundle\Util\SqlUtil;
use AppBundle\Worker\Task\WorkerMessageBody;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

abstract class DeclareControllerServiceBase extends ControllerServiceBase
{
    /** @var AwsExternalQueueService */
    protected $externalQueueService;
    /** @var IRSerializer */
    protected $irSerializer;
    /** @var RequestMessageBuilder */
    protected $requestMessageBuilder;

    public function __construct(AwsExternalQueueService $externalQueueService,
                                CacheService $cacheService,
                                EntityManagerInterface $manager,
                                IRSerializer $irSerializer,
                                RequestMessageBuilder $requestMessageBuilder,
                                UserService $userService)
    {
        parent::__construct($irSerializer, $cacheService, $manager, $userService);
        $this->externalQueueService = $externalQueueService;
        $this->irSerializer = $irSerializer;
        $this->requestMessageBuilder = $requestMessageBuilder;
    }

    /**
     * @param DeclareBase $messageObject
     * @param bool $isUpdate
     * @return array
     */
    protected function sendMessageObjectToQueue($messageObject, $isUpdate = false) {

        $requestId = $messageObject->getRequestId();
        //$repository = $this->getManager()->getRepository(Utils::getRepositoryNameSpace($messageObject));

        //create array and jsonMessage
        $messageArray = RequestMessageOutputBuilder::createOutputArray($this->getManager(), $messageObject, $isUpdate);

        if($messageArray == null) {
            //These objects do not have a customized minimal json output for the queue yet
            $jsonMessage = $this->irSerializer->serializeToJSON($messageObject);
            $messageArray = json_decode($jsonMessage, true);
        } else {
            //Use the minimized custom output
            $jsonMessage = $this->irSerializer->serializeToJSON($messageArray);
        }

        //Send serialized message to Queue
        $requestTypeNameSpace = RequestType::getRequestTypeFromObject($messageObject);

        $sendToQresult = $this->externalQueueService->send($jsonMessage, $requestTypeNameSpace, $requestId);

        //If send to Queue, failed, it needs to be resend, set state to failed
        if ($sendToQresult['statusCode'] != '200') {
            $messageObject->setRequestState(RequestStateType::FAILED);
            $messageObject = MessageModifier::modifyBeforePersistingRequestStateByQueueStatus($messageObject, $this->getManager());
            $this->persist($messageObject);

        } else if($isUpdate) { //If successfully sent to the queue and message is an Update/Edit request
            $messageObject->setRequestState(RequestStateType::OPEN); //update the RequestState
            $messageObject = MessageModifier::modifyBeforePersistingRequestStateByQueueStatus($messageObject, $this->getManager());
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
     * @param AwsInternalQueueService $internalQueueService
     * @param WorkerMessageBody $workerMessageBody
     * @return bool
     */
    protected function sendTaskToQueue(AwsInternalQueueService $internalQueueService, $workerMessageBody) {
        if($workerMessageBody == null) { return false; }

        $jsonMessage = $this->irSerializer->serializeToJSON($workerMessageBody);

        //Send  message to Queue
        $sendToQresult = $internalQueueService->send($jsonMessage, $workerMessageBody->getTaskType(), 1);

        //If send to Queue, failed, it needs to be resend, set state to failed
        return $sendToQresult['statusCode'] == '200';
    }


    /**
     * Retrieve the messageObject related to the RevokeDeclaration
     * reset the request state to 'REVOKING'
     * and persist the update.
     *
     * @param EntityGetter $entityGetter
     * @param string $messageNumber
     */
    public function persistRevokingRequestState(EntityGetter $entityGetter, $messageNumber)
    {
        $messageObjectTobeRevoked = $entityGetter->getRequestMessageByMessageNumber($messageNumber);

        $messageObjectWithRevokedRequestState = $messageObjectTobeRevoked->setRequestState(RequestStateType::REVOKING);

        $this->persist($messageObjectWithRevokedRequestState);
    }


    /**
     * @param Animal|Ram|Ewe|Neuter $animal
     */
    public function persistAnimalTransferringStateAndFlush($animal)
    {
        $animal->setTransferState(AnimalTransferStatus::TRANSFERRING);
        $this->getManager()->persist($animal);
        $this->getManager()->flush();
    }


    /**
     * @param $messageClassNameSpace
     * @param ArrayCollection $contentArray
     * @param $user
     * @param Location $location
     * @param Person $loggedInUser
     * @return null|DeclareArrival|DeclareImport|DeclareExport|DeclareDepart|DeclareBirth|DeclareLoss|DeclareAnimalFlag|DeclarationDetail|DeclareTagsTransfer|RetrieveTags|RevokeDeclaration|RetrieveAnimals|RetrieveCountries|RetrieveUbnDetails
     * @throws \Exception
     */
    protected function buildEditMessageObject($messageClassNameSpace, ArrayCollection $contentArray, $user, $loggedInUser, $location)
    {
        $isEditMessage = true;
        $messageObject = $this->requestMessageBuilder
            ->build($messageClassNameSpace, $contentArray, $user, $loggedInUser, $location, $isEditMessage);

        return $messageObject;
    }


    /**
     * @param $messageClassNameSpace
     * @param ArrayCollection $contentArray
     * @param $user
     * @param Location $location
     * @param Person $loggedInUser
     * @return null|DeclareArrival|DeclareImport|DeclareExport|DeclareDepart|DeclareBirth|DeclareLoss|DeclareAnimalFlag|DeclarationDetail|DeclareTagsTransfer|RetrieveTags|RevokeDeclaration|RetrieveAnimals|RetrieveAnimals|RetrieveCountries|RetrieveUBNDetails
     * @throws \Exception
     */
    protected function buildMessageObject($messageClassNameSpace, ArrayCollection $contentArray, $user, $loggedInUser, $location)
    {
        $isEditMessage = false;
        $messageObject = $this->requestMessageBuilder
            ->build($messageClassNameSpace, $contentArray, $user, $loggedInUser, $location, $isEditMessage);

        return $messageObject;
    }


    /**
     * @param ArrayCollection $content
     * @param DeclareBase|DeclareNsfoBase $newestDeclare
     * @return DeclareBase|DeclareNsfoBase
     * @throws \Exception
     */
    protected function saveNewestDeclareVersion(ArrayCollection $content, $newestDeclare)
    {
        if ($newestDeclare instanceof DeclareBase) {
            $key = JsonInputConstant::REQUEST_ID;
            $keyParameter = 'requestId';
            $clazz = DeclareBase::class;
            $tableName = DeclareBase::getTableName();

        } elseif ($newestDeclare instanceof DeclareNsfoBase) {
            $key = JsonInputConstant::MESSAGE_ID;
            $keyParameter = 'messageId';
            $clazz = DeclareNsfoBase::class;
            $tableName = DeclareNsfoBase::getTableName();

        } else {
            throw new \Exception('Declare must be a DeclareBase or DeclareNsfoBase', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if(!$content->containsKey($key) || $newestDeclare === null) {
            return $newestDeclare;
        }

        $newId = $newestDeclare->getId();
        if ($newId === null) {
            throw new \Exception('Newest Declare must have already been persisted', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $oldRequestId = $content->get($key);
        $oldRequest = $this->getManager()->getRepository($clazz)->findOneBy([$keyParameter => $oldRequestId]);

        //Update old request
        if ($oldRequest) {
            $oldRequest->setNewestVersion($newestDeclare);
            $this->getManager()->persist($oldRequest);
            $this->getManager()->flush();

            $oldId = $oldRequest->getId();
            if (is_int($oldId) || ctype_digit($oldId)) {
                //Update all older requests
                $sql = "UPDATE $tableName SET newest_version_id = '$newId' WHERE newest_version_id = '$oldId' ";
                $updateCount = SqlUtil::updateWithCount($this->getConnection(), $sql);
            }
        }

        return $newestDeclare;
    }


}