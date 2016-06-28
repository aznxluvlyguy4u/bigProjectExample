<?php

namespace AppBundle\Component;

use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\Location;
use AppBundle\Setting\ActionFlagSetting;
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
     * @var Client|Person
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
     * @param Client|Person $person
     * @param Location $location
     * @return DeclareBirth
     */
    public function buildMessage(DeclareBirth $messageObject, $person, $location)
    {
        $this->person = $person;
        $baseMessageObject = $this->buildBaseMessageObject($messageObject, $person);
        $completeMessageObject = $this->addDeclareBirthData($baseMessageObject, $location);

        return $completeMessageObject;
    }

    /**
     * @param DeclareBirth $declareBirth the message received from the front-end
     * @param Location $location
     * @return DeclareBirth
     */
    private function addDeclareBirthData(DeclareBirth $declareBirth, $location)
    {
        $animal = $declareBirth->getAnimal();
        $animal->setDateOfBirth($declareBirth->getDateOfBirth());
        $declareBirth->setLocation($location);

        if(ActionFlagSetting::DECLARE_BIRTH != null) {
            $declareBirth->setAction(ActionFlagSetting::DECLARE_BIRTH);
        }

        return $declareBirth;
    }

}