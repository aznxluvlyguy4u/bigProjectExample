<?php

namespace AppBundle\Component;

use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\AnimalType;
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
     * @param Location $location
     * @return DeclareDepart
     */
    public function buildMessage(DeclareDepart $messageObject, $person, $location)
    {
        $this->person = $person;
        $baseMessageObject = $this->buildBaseMessageObject($messageObject, $person);
        $completeMessageObject = $this->addDeclareDepartData($baseMessageObject, $location);

        return $completeMessageObject;
    }

    /**
     * @param DeclareDepart $messageObject the message received from the front-end
     * @param Location $location
     * @return DeclareDepart
     */
    private function addDeclareDepartData(DeclareDepart $messageObject, $location)
    {
        $animal = $messageObject->getAnimal();

        $messageObject->setUlnCountryCode($animal->getUlnCountryCode());
        $messageObject->setUlnNumber($animal->getUlnNumber());
        $messageObject->setPedigreeCountryCode($animal->getPedigreeCountryCode());
        $messageObject->setPedigreeNumber($animal->getPedigreeNumber());
        $messageObject->setIsExportAnimal(false);
        $messageObject->setIsDepartedAnimal(true);
        $messageObject->setAnimalType(AnimalType::sheep);
        $messageObject->setLocation($location);
        
        if(ActionFlagSetting::DECLARE_DEPART != null) {
            $messageObject->setAction(ActionFlagSetting::DECLARE_DEPART);
        }

        return $messageObject;
    }

}