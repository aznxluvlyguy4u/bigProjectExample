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
     * @param Person $person
     * @return DeclareArrival
     */
    public function buildMessage(DeclareArrival $messageObject, Person $person)
    {
        $this->person = $person;
        $baseMessageObject = $this->buildBaseMessageObject($messageObject, $person);
        $completeMessageObject = $this->addDeclareArrivalData($baseMessageObject);

        return $completeMessageObject;
    }

    /**
     * @param DeclareArrival $messageObject the baseMessageObject
     * @return DeclareArrival
     */
    private function addDeclareArrivalData(DeclareArrival $messageObject)
    {
        //TODO For FASE 2 retrieve the correct location & company for someone having more than one location and/or company.
        $messageObject->setLocation($this->person->getCompanies()[0]->getLocations()[0]);
        return $messageObject;
    }

}