<?php

namespace AppBundle\Component;

use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareExport;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\AnimalType;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\Person;

class ExportMessageBuilder extends MessageBuilderBase
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
   * Accept input and create a complete NSFO+IenR Message.
   *
   * @param DeclareExport $messageObject the message received
   * @param Client|Person $person
   * @param Person $loggedInUser
   * @param Location $location
   * @return DeclareExport
   */
  public function buildMessage(DeclareExport $messageObject, $person, $loggedInUser, $location)
  {
    $this->person = $person;
    $baseMessageObject = $this->buildBaseMessageObject($messageObject, $person, $loggedInUser);
    $completeMessageObject = $this->addDeclareExportData($baseMessageObject, $location);

    return $completeMessageObject;
  }

  /**
   * @param DeclareExport $messageObject the message received
   * @param Location $location
   * @return DeclareExport
   */
  private function addDeclareExportData(DeclareExport $messageObject, $location)
  {
    $animal = $messageObject->getAnimal();

    $messageObject->setUlnCountryCode($animal->getUlnCountryCode());
    $messageObject->setUlnNumber($animal->getUlnNumber());
    $messageObject->setPedigreeCountryCode($animal->getPedigreeCountryCode());
    $messageObject->setPedigreeNumber($animal->getPedigreeNumber());
    $messageObject->setIsExportAnimal(true);
    $messageObject->setAnimalType(AnimalType::sheep);
    $messageObject->setLocation($location);

    return $messageObject;
  }

}