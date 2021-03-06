<?php

namespace AppBundle\Component;

use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\Location;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\AnimalType;
use Doctrine\Common\Persistence\ObjectManager;

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

    public function __construct(ObjectManager $em, $currentEnvironment)
    {
        parent::__construct($em, $currentEnvironment);
    }

    /**
     *
     * Accept front-end input and create a complete NSFO+IenR Message.
     *
     * @param DeclareDepart $messageObject the message received from the front-end
     * @param Client|Person $person
     * @param Person $loggedInUser
     * @param Location $location
     * @return DeclareDepart
     */
    public function buildMessage(DeclareDepart $messageObject, $person, $loggedInUser, $location)
    {
        $this->person = $person;
        $baseMessageObject = $this->buildBaseMessageObject($messageObject, $person, $loggedInUser, $location);
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

        $alternativeStartDateForInitialAnimalResidence = $messageObject->getDepartDate();
        Utils::setResidenceToPending($animal, $location, $alternativeStartDateForInitialAnimalResidence);

        $messageObject->setUlnCountryCode($animal->getUlnCountryCode());
        $messageObject->setUlnNumber($animal->getUlnNumber());
        $messageObject->setPedigreeCountryCode($animal->getPedigreeCountryCode());
        $messageObject->setPedigreeNumber($animal->getPedigreeNumber());
        $messageObject->setIsExportAnimal(false);
        $messageObject->setIsDepartedAnimal(true);
        $messageObject->setAnimalType(AnimalType::sheep);
        $messageObject->setLocation($location);

        return $messageObject;
    }

}