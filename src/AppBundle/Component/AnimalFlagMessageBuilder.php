<?php

namespace AppBundle\Component;

use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareAnimalFlag;
use AppBundle\Entity\Location;
use AppBundle\Entity\Person;
use Doctrine\Common\Persistence\ObjectManager;

class AnimalFlagMessageBuilder extends MessageBuilderBase
{
    /**
     * @var Client|Person
     */
    private $person;

    public function __construct(ObjectManager $em, $currentEnvironment)
    {
        parent::__construct($em, $currentEnvironment);
    }

    /**
     *
     * Accept front-end input and create a complete NSFO+IenR Message.
     *
     * @param DeclareAnimalFlag $messageObject the message received from the front-end
     * @param Client|Person $person
     * @param Person $loggedInUser
     * @param Location $location
     * @return DeclareAnimalFlag
     */
    public function buildMessage(DeclareAnimalFlag $messageObject, $person, $loggedInUser, $location)
    {
        $this->person = $person;

        return $this->buildBaseMessageObject($messageObject, $person, $loggedInUser, $location);
    }
}