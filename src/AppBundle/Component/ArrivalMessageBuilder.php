<?php

namespace AppBundle\Component;

use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\AnimalType;
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
     * @var Client|Person
     */
    private $person;

    public function __construct(EntityManager $em, $currentEnvironment)
    {
        parent::__construct($em, $currentEnvironment);
    }

    /**
     *
     * Accept front-end input and create a complete NSFO+IenR Message.
     *
     * @param DeclareArrival $messageObject the message received from the front-end
     * @param Client|Person $person
     * @param Location $location
     * @return DeclareArrival
     */
    public function buildMessage(DeclareArrival $messageObject, $person, $location)
    {
        $this->person = $person;
        $baseMessageObject = $this->buildBaseMessageObject($messageObject, $person);
        $completeMessageObject = $this->addDeclareArrivalData($baseMessageObject, $location);

        return $completeMessageObject;
    }

    /**
     * @param DeclareArrival $messageObject the baseMessageObject
     * @param Location $location
     * @return DeclareArrival
     */
    private function addDeclareArrivalData(DeclareArrival $messageObject, $location)
    {
        $messageObject->setAnimalType(AnimalType::sheep);

        $messageObject->setLocation($location);
        return $messageObject;
    }

}