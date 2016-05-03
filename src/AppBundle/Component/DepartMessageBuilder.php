<?php

namespace AppBundle\Component;

use AppBundle\Enumerator\AnimalType;
use AppBundle\Entity\DeclareDepart;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\Person;

/**
 * Class DepartMessageBuilderAPIController
 * @package AppBundle\Controller
 */
class DepartMessageBuilder extends MessageBuilderBase
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
     * @param DeclareDepart $messageObject the message received from the front-end
     * @param Person $person
     * @return DeclareDepart
     */
    public function buildMessage(DeclareDepart $messageObject, Person $person)
    {
        $this->person = $person;
        $baseMessageObject = $this->buildBaseMessageObject($messageObject, $person);
        $completeMessageObject = $this->addDeclareDepartData($baseMessageObject);

        return $completeMessageObject;
    }

    /**
     * @param DeclareDepart $messageObject the message received from the front-end
     * @param Person $person
     * @return DeclareDepart
     */
    private function addDeclareDepartData(DeclareDepart $messageObject)
    {
        $animal = $messageObject->getAnimal();
        $animal->setAnimalType(AnimalType::sheep);

        //TODO For FASE 2 retrieve the correct location & company for someone having more than one location and/or company.
        $messageObject->setLocation($this->person->getCompanies()[0]->getLocations()[0]);
        return $messageObject;
    }

}