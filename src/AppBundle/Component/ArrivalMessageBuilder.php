<?php

namespace AppBundle\Component;

use AppBundle\Enumerator\AnimalType;
use AppBundle\Entity\Ram;
use AppBundle\Entity\DeclareArrival;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\Person;

/**
 * Class ArrivalMessageBuilderAPIController
 * @package AppBundle\Controller
 */
class ArrivalMessageBuilder extends MessageBuilderBase
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
     * @param DeclareArrival $messageObject the message received from the front-end
     * @param string $relationNumberKeeper
     * @return ArrayCollection
     */
    public function buildMessage(DeclareArrival $messageObject, Person $person)
    {
        $this->person = $person;
        $baseMessageObject = $this->buildBaseMessageObject($messageObject, $person);
        $completeMessageObject = $this->addDeclareArrivalData($baseMessageObject);

        return $completeMessageObject;
    }

    /**
     * @param ArrayCollection $content
     * @return ArrayCollection
     */
    private function addDeclareArrivalData(DeclareArrival $messageObject)
    {
        $animal = $messageObject->getAnimal();
        $animal->setAnimalType(AnimalType::sheep);

        //TODO retrieve location from database related to the correct person
        $messageObject->setLocation($this->person->getCompanies()[0]->getLocations()[0]);
        return $messageObject;
    }

}