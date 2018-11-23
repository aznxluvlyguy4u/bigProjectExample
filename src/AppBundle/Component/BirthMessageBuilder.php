<?php

namespace AppBundle\Component;

use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\Location;
use AppBundle\Entity\Person;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class BirthMessageBuilderAPIController
 * @package AppBundle\Controller
 */
class BirthMessageBuilder extends MessageBuilderBase
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
     * @param DeclareBirth $messageObject the message received from the front-end
     * @param Client|Person $person
     * @param Person $loggedInUser
     * @param Location $location
     * @return DeclareBirth
     */
    public function buildMessage(DeclareBirth $messageObject, $person, $loggedInUser, $location)
    {
        $this->person = $person;
        return $this->buildBaseMessageObject($messageObject, $person, $loggedInUser, $location);
    }

}