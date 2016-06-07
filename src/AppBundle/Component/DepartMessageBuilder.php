<?php

namespace AppBundle\Component;

use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Setting\ActionFlagSetting;
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
     * @param DeclareDepart $messageObject the message received from the front-end
     * @param Client|Person $person
     * @return DeclareDepart
     */
    public function buildMessage(DeclareDepart $messageObject, $person)
    {
        $this->person = $person;
        $baseMessageObject = $this->buildBaseMessageObject($messageObject, $person);
        $completeMessageObject = $this->addDeclareDepartData($baseMessageObject);

        return $completeMessageObject;
    }

    /**
     * @param DeclareDepart $messageObject the message received from the front-end
     * @return DeclareDepart
     */
    private function addDeclareDepartData(DeclareDepart $messageObject)
    {
        $animal = $messageObject->getAnimal();

        $messageObject->setUlnCountryCode($animal->getUlnCountryCode());
        $messageObject->setUlnNumber($animal->getUlnNumber());
        $messageObject->setPedigreeCountryCode($animal->getPedigreeCountryCode());
        $messageObject->setPedigreeNumber($animal->getPedigreeNumber());
        $messageObject->setIsExportAnimal(false);
        $messageObject->setIsDepartedAnimal(true);

        if(ActionFlagSetting::DECLARE_DEPART != null) {
            $messageObject->setAction(ActionFlagSetting::DECLARE_DEPART);
        }

        //TODO For FASE 2 retrieve the correct location & company for someone having more than one location and/or company.
        $messageObject->setLocation($this->person->getCompanies()[0]->getLocations()[0]);
        return $messageObject;
    }

}