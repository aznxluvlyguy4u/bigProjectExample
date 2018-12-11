<?php


namespace AppBundle\Worker\DirectProcessor;


use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalResidence;
use AppBundle\Entity\DeclareBase;
use AppBundle\Entity\DeclareBaseResponse;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
use AppBundle\Entity\Message;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Tag;
use AppBundle\Enumerator\AnimalObjectType;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Service\AwsInternalQueueService;
use AppBundle\Service\BaseSerializer;
use AppBundle\Service\ControllerServiceBase;
use AppBundle\Service\DeclareControllerServiceBase;
use AppBundle\Util\StringUtil;
use AppBundle\Util\WorkerTaskUtil;
use Symfony\Component\HttpKernel\Exception\PreconditionRequiredHttpException;

class DeclareProcessorBase extends ControllerServiceBase
{
    /** @var AwsInternalQueueService */
    private $internalQueueService;

    /** @var string */
    private $environment;


    /**
     * @return AwsInternalQueueService
     */
    protected function getInternalQueueService(): AwsInternalQueueService
    {
        return $this->internalQueueService;
    }

    /**
     * @required
     *
     * @param AwsInternalQueueService $internalQueueService
     */
    public function setInternalQueueService(AwsInternalQueueService $internalQueueService): void
    {
        $this->internalQueueService = $internalQueueService;
    }


    /**
     * @param string $environment
     */
    public function setEnvironment(string $environment): void
    {
        $this->environment = $environment;
    }

    /**
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }


    /**
     * @param DeclareBaseResponse $response
     * @return bool|array|null
     */
    public function persistResponseInSeparateTransaction(DeclareBaseResponse $response)
    {
        return DeclareProcessorBase::sendResponseToWorkerQueue(
            $this->getBaseSerializer(),
            $this->getInternalQueueService(),
            $response
        );
    }


    /**
     * @param BaseSerializer $serializer
     * @param AwsInternalQueueService $internalQueueService
     * @param DeclareBaseResponse $response
     * @return bool|array|null
     */
    public static function sendResponseToWorkerQueue(BaseSerializer $serializer,
                                                     AwsInternalQueueService $internalQueueService,
                                                     DeclareBaseResponse $response)
    {
        if($response == null) { return false; }

        $workerMessageBody = WorkerTaskUtil::createResponseToPersistBody($response);
        $jsonMessage = $serializer->serializeToJSON($workerMessageBody, JmsGroup::RESPONSE_PERSISTENCE);

        //Send  message to Queue
        $sendToQresult = $internalQueueService
            ->send($jsonMessage, $workerMessageBody->getTaskType(), 1);

        //If send to Queue, failed, it needs to be resend, set state to failed
        return $sendToQresult['statusCode'] == '200';
    }


    /**
     * @param DeclareBase $messageObject
     * @param bool $isUpdate
     * @param array $jmsGroups
     * @return array
     */
    protected function getDeclareMessageArray($messageObject, bool $isUpdate, $jmsGroups = [JmsGroup::RVO]): ?array
    {
        return DeclareControllerServiceBase::staticGetDeclareMessageArrayAndJsonMessage($this->getManager(), $this->getBaseSerializer(),
            $messageObject, $isUpdate, $jmsGroups)[JsonInputConstant::ARRAY];
    }


    /**
     * @param Animal $animal
     * @param Location $location
     */
    protected function resetOriginPendingStateAnimalResidence(?Animal $animal, ?Location $location)
    {
        if (!$location || !$animal) {
            return;
        }

        $animalResidence = $this->getManager()->getRepository(AnimalResidence::class)
            ->getLastOpenResidenceOnLocation($location, $animal);
        if ($animalResidence && $animalResidence->isPending()) {
            $animalResidence->setIsPending(false);
            $this->getManager()->persist($animalResidence);
        }
    }


    /**
     * @param Animal $animal
     * @param Location $origin
     * @return bool
     */
    protected function animalResidenceOnPreviousLocationHasBeenFinalized(?Animal $animal, ?Location $origin): bool
    {
        if (!$origin || !$animal) {
            // If previous location does not exist in the database, finalize transaction
            return true;
        }

        $animalResidence = $this->getManager()->getRepository(AnimalResidence::class)
            ->getLastResidenceOnLocation($origin, $animal);
        return $animalResidence && $animalResidence->getEndDate() !== null;
    }


    /**
     * @param Animal $animal
     * @param Location $location
     * @param \DateTime $startDate
     * @param bool $isPending
     * @return AnimalResidence
     */
    protected function createNewAnimalResidenceIfNotExistsYet(Animal $animal, ?Location $location,
                                                              \DateTime $startDate, bool $isPending): AnimalResidence
    {
        if (!$location) {
            throw new PreconditionRequiredHttpException('Destination location missing');
        }

        $currentAnimalResidence = $this->getManager()->getRepository(AnimalResidence::class)
            ->getLastResidenceOnLocation($location, $animal);
        if ($currentAnimalResidence) {
            if ($currentAnimalResidence->isPending() !== $isPending) {
                $currentAnimalResidence->setIsPending($isPending);
                $this->getManager()->persist($currentAnimalResidence);
            }
            return $currentAnimalResidence;
        }

        $newAnimalResidence = new AnimalResidence(
            $location->getCountryCode(),
            $isPending
        );
        $newAnimalResidence->setAnimal($animal);
        $newAnimalResidence->setLocation($location);
        $newAnimalResidence->setStartDate($startDate);
        $this->getManager()->persist($newAnimalResidence);

        return $newAnimalResidence;
    }


    /**
     * @param Animal $animal
     * @param Location $destination
     */
    protected function finalizeAnimalTransferAndAnimalResidenceDestination(?Animal $animal, ?Location $destination)
    {
        if (!$destination || !$animal) {
            return;
        }

        $animalResidence = $this->getManager()->getRepository(AnimalResidence::class)
            ->getLastOpenResidenceOnLocation($destination, $animal);
        if (!$animalResidence) {
            return;
        }

        if ($animalResidence->isPending()) {
            $animalResidence->setIsPending(false);
            $this->getManager()->persist($animalResidence);
        }
        $animal->setLocation($destination);
        $animal->setTransferState(null);
        $destination->addAnimal($animal);
        $this->getManager()->persist($animal);
        $this->getManager()->persist($destination);
    }


    /**
     * @param Animal $animal
     * @param Location $location
     * @param \DateTime $endDate
     */
    protected function closeLastOpenAnimalResidence(Animal $animal, ?Location $location, $endDate)
    {
        if (!$endDate || !$location) {
            return;
        }

        $animalResidence = $this->getManager()->getRepository(AnimalResidence::class)
            ->getLastOpenResidenceOnLocation($location, $animal);
        if ($animalResidence && !$animalResidence->getEndDate()) {
            $animalResidence->setEndDate($endDate);
            $animalResidence->setIsPending(false);
            $this->getManager()->persist($animalResidence);
        }
    }


    /**
     * NOTE! Set the Response on the message in the JAVA worker. Or else the
     *
     * @param DeclareBase $declare
     */
    protected function displayDeclareNotificationMessage(DeclareBase $declare)
    {
        $message = $this->getManager()->getRepository(Message::class)
            ->findOneByRequest($declare);
        if ($message) {
            $message->setHidden(false);
            $this->getManager()->persist($message);
        }
    }


    /**
     * @param string $ulnCountryCode
     * @param string $ulnNumber
     * @return Tag|null
     */
    protected function findTag($ulnCountryCode, $ulnNumber): ?Tag
    {
        if (empty($ulnCountryCode) || empty($ulnNumber)) {
            return null;
        }

        return $this->getManager()->getRepository(Tag::class)
            ->findByUlnNumberAndCountryCode($ulnCountryCode, $ulnNumber);
    }


    /**
     * @param string $ulnCountryCode
     * @param string $ulnNumber
     * @param string $gender
     * @return Ewe|Neuter|Ram
     */
    protected function createNewAnimal($ulnCountryCode, $ulnNumber, $gender)
    {
        $gender = strtolower($gender);
        $isRam = $gender === strtolower(GenderType::M)
            || $gender === strtolower(GenderType::MALE)
            || $gender === strtolower(AnimalObjectType::RAM);

        $isEwe = $gender === strtolower(GenderType::V)
            || $gender === strtolower(GenderType::FEMALE)
            || $gender === strtolower(AnimalObjectType::EWE);

        if ($isRam) {
            $animal = new Ram();
        } elseif ($isEwe) {
            $animal = new Ewe();
        } else {
            $animal = new Neuter();
        }

        $animal->setUlnCountryCode($ulnCountryCode);
        $animal->setUlnNumber($ulnNumber);
        $animal->setAnimalOrderNumber(StringUtil::getLast5CharactersFromString($ulnNumber));
        $animal->setIsAlive(true);

        return $animal;
    }
}