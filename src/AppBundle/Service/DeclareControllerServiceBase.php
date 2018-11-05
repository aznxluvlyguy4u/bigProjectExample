<?php


namespace AppBundle\Service;


use AppBundle\Component\Modifier\MessageModifier;
use AppBundle\Component\RequestMessageBuilder;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Client;
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
use AppBundle\Entity\DeclareTagReplace;
use AppBundle\Entity\DeclareTagsTransfer;
use AppBundle\Entity\DepartArrivalTransaction;
use AppBundle\Entity\Location;
use AppBundle\Entity\Person;
use AppBundle\Entity\RetrieveAnimals;
use AppBundle\Entity\RetrieveCountries;
use AppBundle\Entity\RetrieveTags;
use AppBundle\Entity\RetrieveUbnDetails;
use AppBundle\Entity\RevokeDeclaration;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Exception\DeclareToOtherCountryHttpException;
use AppBundle\Exception\EventDateBeforeDateOfBirthHttpException;
use AppBundle\Exception\InvalidStnHttpException;
use AppBundle\Exception\InvalidUlnHttpException;
use AppBundle\Output\RequestMessageOutputBuilder;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Util\Validator;
use AppBundle\Worker\Task\WorkerMessageBody;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionRequiredHttpException;

abstract class DeclareControllerServiceBase extends ControllerServiceBase
{
    /** @var AwsExternalQueueService */
    protected $externalQueueService;
    /** @var IRSerializer */
    protected $irSerializer;
    /** @var RequestMessageBuilder */
    protected $requestMessageBuilder;

    /**
     * @required
     *
     * @param AwsExternalQueueService $externalQueueService
     */
    public function setExternalQueueService(AwsExternalQueueService $externalQueueService)
    {
        $this->externalQueueService = $externalQueueService;
    }

    /**
     * @required
     *
     * @param IRSerializer $irSerializer
     */
    public function setIrSerializer(IRSerializer $irSerializer)
    {
        $this->irSerializer = $irSerializer;
    }

    /**
     * @required
     *
     * @param RequestMessageBuilder $requestMessageBuilder
     */
    public function setRequestMessageBuilder(RequestMessageBuilder $requestMessageBuilder)
    {
        $this->requestMessageBuilder = $requestMessageBuilder;
    }


    /**
     * @param DeclareBase $messageObject
     * @param bool $isUpdate
     * @param string|array $jmsGroups
     * @return array
     */
    protected function sendMessageObjectToQueue($messageObject, $isUpdate = false, $jmsGroups = [JmsGroup::RVO]) {

        $requestId = $messageObject->getRequestId();

        $declareOutputs = $this->getDeclareMessageArrayAndJsonMessage($messageObject, $isUpdate, $jmsGroups);
        $messageArray = $declareOutputs[JsonInputConstant::ARRAY];
        $jsonMessage = $declareOutputs[JsonInputConstant::JSON];

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
     * @param DeclareBase $messageObject
     * @param bool $isUpdate
     * @param array $jmsGroups
     * @return mixed
     */
    protected function getDeclareMessageArray($messageObject, bool $isUpdate, $jmsGroups = [JmsGroup::RVO])
    {
        return $this->getDeclareMessageArrayAndJsonMessage($messageObject, $isUpdate, $jmsGroups)[JsonInputConstant::ARRAY];
    }


    /**
     * @param DeclareBase $messageObject
     * @param bool $isUpdate
     * @param array $jmsGroups
     * @return array
     */
    protected function getDeclareMessageArrayAndJsonMessage($messageObject, bool $isUpdate, $jmsGroups = [JmsGroup::RVO]): array
    {
        return self::staticGetDeclareMessageArrayAndJsonMessage($this->getManager(), $this->irSerializer,
            $messageObject, $isUpdate, $jmsGroups);
    }


    public static function staticGetDeclareMessageArrayAndJsonMessage(
        EntityManagerInterface $em,
        BaseSerializer $serializer,
        $messageObject,
        bool $isUpdate,
        $jmsGroups = [JmsGroup::RVO]
    ): array
    {
        $messageArray = RequestMessageOutputBuilder::createOutputArray($em, $messageObject, $isUpdate);

        if($messageArray == null) {
            //These objects do not have a customized minimal json output for the queue yet
            $jsonMessage = $serializer->serializeToJSON($messageObject, $jmsGroups);
            $messageArray = json_decode($jsonMessage, true);
        } else {
            //Use the minimized custom output
            $jsonMessage = $serializer->serializeToJSON($messageArray);
        }

        return [
            JsonInputConstant::ARRAY => $messageArray,
            JsonInputConstant::JSON => $jsonMessage,
        ];
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
        return $this->requestMessageBuilder
            ->build($messageClassNameSpace, $contentArray, $user, $loggedInUser, $location, true);
    }


    /**
     * @param $messageClassNameSpace
     * @param ArrayCollection $contentArray
     * @param $user
     * @param Location $location
     * @param Person $loggedInUser
     * @return null|DeclareArrival|DeclareImport|DeclareExport|DeclareDepart|DeclareBirth|DeclareLoss|DeclareAnimalFlag|DeclarationDetail|DeclareTagsTransfer|RetrieveTags|RevokeDeclaration|RetrieveAnimals|RetrieveAnimals|RetrieveCountries|RetrieveUBNDetails|DeclareTagReplace
     * @throws \Exception
     */
    protected function buildMessageObject($messageClassNameSpace, ArrayCollection $contentArray, $user, $loggedInUser, $location)
    {
        return $this->requestMessageBuilder
            ->build($messageClassNameSpace, $contentArray, $user, $loggedInUser, $location, false);
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
            if ($oldRequest instanceof DeclareNsfoBase) {
                $oldRequest->setIsOverwrittenVersion(true);
            }

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


    /**
     * @param string $declareClazz
     * @param Location $origin
     * @param Location $destination
     */
    protected function validateIfOriginAndDestinationAreInSameCountry(string $declareClazz,
                                                                      ?Location $origin, ?Location $destination)
    {
        if ($origin && $destination &&
            $origin->getCountryCode() !== $destination->getCountryCode()){
            throw new DeclareToOtherCountryHttpException($this->translator, $declareClazz, $destination, $origin);
        }
    }


    /**
     * @param Location $location
     * @param string $declareClazz
     */
    protected function validateIfLocationIsDutch(Location $location, string $declareClazz)
    {
        if (!$location->isDutchLocation()) {
            $message = ucfirst($this->translator->trans(StringUtil::getDeclareTranslationKey($declareClazz, true)))
            .' '.$this->translator->trans('ARE ONLY ALLOWED FOR DUTCH UBNS');
            throw new PreconditionFailedHttpException($message);
        }
    }


    /**
     * @param Location|null $location
     * @param array $animalArray
     */
    protected function verifyIfAnimalIsOnLocation(?Location $location, array $animalArray): void
    {
        $this->nullCheckLocation($location);
        $animalIsOnLocation = $this->getManager()->getRepository(Animal::class)
            ->verifyIfAnimalIsOnLocation($location, $animalArray);

        if(!$animalIsOnLocation) {
            throw new PreconditionFailedHttpException("Animal doesn't belong to this account.");
        }
    }


    /**
     * @param $ubn
     * @param bool $isDutchLocation
     */
    protected function verifyUbnFormat($ubn, bool $isDutchLocation)
    {
        if (!Validator::hasValidUbnFormatByLocationType($ubn, $isDutchLocation)) {
            throw new PreconditionFailedHttpException($this->translateUcFirstLower('UBN IS NOT A VALID NUMBER').': '.$ubn);
        }
    }


    /**
     * @param $animalArray
     */
    protected function verifyUlnOrPedigreeNumberFormatByAnimalArray($animalArray)
    {
        if (!is_array($animalArray)) {
            throw new InvalidUlnHttpException($this->translator, null);
        }

        $uln = ArrayUtil::get(JsonInputConstant::ULN_COUNTRY_CODE, $animalArray)
            . ArrayUtil::get(JsonInputConstant::ULN_NUMBER, $animalArray);
        $uln = is_string($uln) && !empty($uln) ? $uln : null;

        if ($uln) {
            if (!Validator::verifyUlnFormat($uln, false)) {
                throw new InvalidUlnHttpException($this->translator, $uln);
            }
            return;
        }

        $stn = ArrayUtil::get(JsonInputConstant::PEDIGREE_COUNTRY_CODE, $animalArray)
            . ArrayUtil::get(JsonInputConstant::PEDIGREE_NUMBER, $animalArray);
        $stn = is_string($stn) && !empty($stn) ? $stn : null;

        $stnIsValid = $stn && Validator::verifyPedigreeCountryCodeAndNumberFormat($stn,false);
        if (!$stnIsValid) {
            throw new InvalidStnHttpException($this->translator, $stn);
        }
    }


    /**
     * @param $animalArray
     */
    protected function verifyUlnFormatByAnimalArray($animalArray)
    {
        $hasValidUlnFormat = false;
        $uln = null;

        if (is_array($animalArray)) {
            $uln = ArrayUtil::get(JsonInputConstant::ULN_COUNTRY_CODE, $animalArray)
                . ArrayUtil::get(JsonInputConstant::ULN_NUMBER, $animalArray);

            $hasValidUlnFormat = Validator::verifyUlnFormat($uln, false);
            $uln = is_string($uln) ? $uln : null;
        }

        if (!$hasValidUlnFormat) {
            throw new InvalidUlnHttpException($this->translator, $uln);
        }
    }


    /**
     * @param string $uln
     * @param bool $includeSpaceBetweenCountryCodeAndNumber
     */
    protected function verifyUlnFormat($uln, $includeSpaceBetweenCountryCodeAndNumber = false)
    {
        $hasValidUlnFormat = Validator::verifyUlnFormat($uln, $includeSpaceBetweenCountryCodeAndNumber);
        if (!$hasValidUlnFormat) {
            $uln = is_string($uln) ? $uln : null;
            throw new InvalidUlnHttpException($this->translator, $uln);
        }
    }


    /**
     * @param $ubnOfDeparture
     * @param $ubnOfArrival
     */
    protected function verifyIfDepartureAndArrivalUbnAreIdentical($ubnOfDeparture, $ubnOfArrival): void
    {
        if (Validator::areUbnsIdentical($ubnOfArrival, $ubnOfDeparture)) {
            throw new PreconditionFailedHttpException(
                $this->translator->trans('UBN OF DEPARTURE AND ARRIVAL ARE IDENTICAL')
            );
        }
    }


    /**
     * @param string $requestId
     * @return DeclareBase
     */
    protected function getRequestByRequestId($requestId): DeclareBase
    {
        if (empty($requestId)) {
            throw new PreconditionRequiredHttpException('RequestId is empty');
        }

        $declareBase = $this->getManager()->getRepository(DeclareBase::class)->getByRequestId($requestId);
        if (!$declareBase) {
            throw new PreconditionRequiredHttpException('Declare was not found for requestId '.$requestId);
        }

        return $declareBase;
    }


    /**
     * @param DeclareArrival|null $arrival
     * @param DeclareDepart|null $depart
     * @param Person $actionBy
     * @param bool $useRvoLogic
     * @param bool $isArrivalInitiated
     */
    protected function createDepartArrivalTransaction(?DeclareArrival $arrival,
                                                      ?DeclareDepart $depart,
                                                      Person $actionBy,
                                                      bool $useRvoLogic,
                                                      bool $isArrivalInitiated)
    {
        if ($isArrivalInitiated) {
            if (!$arrival) {
                throw new PreconditionRequiredHttpException('ARRIVAL cannot be NULL if leading');
            }
        } else {
            if (!$depart) {
                throw new PreconditionRequiredHttpException('DEPART cannot be NULL if leading');
            }
        }

        $transaction = new DepartArrivalTransaction();
        $transaction
            ->setArrival($arrival)
            ->setDepart($depart)
            ->setActionBy($actionBy)
            ->setIsRvoMessage($useRvoLogic)
            ->setIsArrivalInitiated($isArrivalInitiated)
        ;

        $this->getManager()->persist($transaction);
    }




    /**
     * @param Animal|null $animal
     * @param \DateTime|null $eventDate
     */
    protected function validateIfEventDateIsNotBeforeDateOfBirth(?Animal $animal, $eventDate)
    {
        if ($animal && $animal->getDateOfBirth() && !empty($eventDate) && $eventDate instanceof \DateTime
            && TimeUtil::isDate1BeforeDate2($eventDate, $animal->getDateOfBirth())
        ) {
            throw new EventDateBeforeDateOfBirthHttpException(
                $this->translator, $animal->getDateOfBirth(), $eventDate
            );
        }
    }
}