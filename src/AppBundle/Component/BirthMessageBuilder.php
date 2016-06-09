<?php

namespace AppBundle\Component;

use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareBirth;
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
     * @return DeclareBirth
     */
    public function buildMessage(DeclareBirth $messageObject, $person)
    {
        $this->person = $person;
        $baseMessageObject = $this->buildBaseMessageObject($messageObject, $person);
        $completeMessageObject = $this->addDeclareBirthData($baseMessageObject);

        return $completeMessageObject;
    }

    /**
     * @param DeclareBirth $declareBirth the message received from the front-end
     * @return DeclareBirth
     */
    private function addDeclareBirthData(DeclareBirth $declareBirth)
    {
        $animal = $declareBirth->getAnimal();
        $animal->setDateOfBirth($declareBirth->getDateOfBirth());

        if(ActionFlagSetting::DECLARE_BIRTH != null) {
            $declareBirth->setAction(ActionFlagSetting::DECLARE_BIRTH);
        }
        
        //TODO For FASE 2 retrieve the correct location & company for someone having more than one location and/or company.
        $declareBirth->setLocation($this->person->getCompanies()[0]->getLocations()[0]);
        return $declareBirth;
    }

}