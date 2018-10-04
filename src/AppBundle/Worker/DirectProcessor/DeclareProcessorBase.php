<?php


namespace AppBundle\Worker\DirectProcessor;


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
use AppBundle\Service\ControllerServiceBase;
use AppBundle\Service\DeclareControllerServiceBase;
use AppBundle\Util\StringUtil;

class DeclareProcessorBase extends ControllerServiceBase
{
    /** @var string */
    private $environment;

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
     * @param DeclareBase $messageObject
     * @param bool $isUpdate
     * @param array $jmsGroups
     * @return array
     */
    protected function getDeclareMessageArrayAndJsonMessage($messageObject, bool $isUpdate, $jmsGroups = [JmsGroup::RVO]): array
    {
        return DeclareControllerServiceBase::staticGetDeclareMessageArrayAndJsonMessage($this->getManager(), $this->getBaseSerializer(),
            $messageObject, $isUpdate, $jmsGroups);
    }


    /**
     * @param Animal $animal
     * @param Location $location
     */
    protected function resetOriginPendingStateAnimalResidence(Animal $animal, Location $location)
    {
        $animalResidence = $this->getManager()->getRepository(AnimalResidence::class)
            ->getLastOpenResidenceOnLocation($location, $animal);
        if ($animalResidence->isPending()) {
            $animalResidence->setIsPending(false);
            $this->getManager()->persist($animalResidence);
        }
    }


    /**
     * @param Animal $animal
     * @param Location $destination
     */
    protected function finalizeAnimalTransferAndAnimalResidenceDestination(Animal $animal, Location $destination)
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
    protected function closeLastOpenAnimalResidence(Animal $animal, Location $location, $endDate)
    {
        if (!$endDate) {
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
     * @param DeclareBase $declare
     * @param DeclareBaseResponse $response
     */
    protected function displayDeclareNotificationMessage(DeclareBase $declare, DeclareBaseResponse $response)
    {
        $message = $this->getManager()->getRepository(Message::class)
            ->findOneByRequest($declare);
        if ($message) {
            $message->setHidden(false);
            $message->setResponseMessage($response);
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