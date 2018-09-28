<?php


namespace AppBundle\Worker\DirectProcessor;


use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalResidence;
use AppBundle\Entity\DeclareBase;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
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