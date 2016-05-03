<?php

namespace AppBundle\Component;

use AppBundle\Enumerator\AnimalType;
use AppBundle\Entity\Ram;
use AppBundle\Entity\DeclareBirth;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\Person;

/**
 * Class BirthMessageBuilderAPIController
 * @package AppBundle\Controller
 */
class BirthMessageBuilder extends MessageBuilderBase
{
    /**
     * @var Person
     */
    private $person;

    public function __construct(EntityManager $em)
    {
        parent::__construct($em);
    }

    /**
     *
     * Accept front-end input and create a complete NSFO+IenR Message.
     *
     * @param DeclareBirth $messageObject the message received from the front-end
     * @param Person $person
     * @return ArrayCollection
     */
    public function buildMessage(DeclareBirth $messageObject, Person $person)
    {
        $this->person = $person;
        $baseMessageObject = $this->buildBaseMessageObject($messageObject, $person);
        $completeMessageObject = $this->addDeclareBirthData($baseMessageObject);

        return $completeMessageObject;
    }

    /**
     * @param DeclareBirth $messageObject the message received from the front-end
     * @return DeclareBirth
     */
    private function addDeclareBirthData(DeclareBirth $messageObject)
    {
        $animal = $messageObject->getAnimal();
        $animal->setAnimalType(AnimalType::sheep);

        //TODO For FASE 2 retrieve the correct location & company for someone having more than one location and/or company.
        $messageObject->setLocation($this->person->getCompanies()[0]->getLocations()[0]);
        return $messageObject;
    }

}