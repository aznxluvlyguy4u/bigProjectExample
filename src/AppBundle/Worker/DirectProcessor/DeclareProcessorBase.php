<?php


namespace AppBundle\Worker\DirectProcessor;


use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalResidence;
use AppBundle\Entity\DeclareBase;
use AppBundle\Entity\Location;
use AppBundle\Entity\Tag;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Service\ControllerServiceBase;
use AppBundle\Service\DeclareControllerServiceBase;

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
}