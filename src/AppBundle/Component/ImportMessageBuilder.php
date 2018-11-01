<?php

namespace AppBundle\Component;

use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\AnimalType;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\Person;

/**
 * Class ImportMessageBuilder
 * @package AppBundle\Component
 */
class ImportMessageBuilder extends MessageBuilderBase
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
   * @param DeclareImport $messageObject the message received from the front-end
   * @param Client|Person $person
   * @param Person $loggedInUser
   * @param Location $location
   * @return DeclareImport
   */
  public function buildMessage(DeclareImport $messageObject, $person, $loggedInUser, $location)
  {
    $this->person = $person;
    $baseMessageObject = $this->buildBaseMessageObject($messageObject, $person, $loggedInUser, $location);
    $completeMessageObject = $this->addDeclareImportData($baseMessageObject, $location);

    return $completeMessageObject;
  }

  /**
   * @param DeclareImport $declareImport the message received from the front-end
   * @param Location $location
   * @return DeclareImport
   */
  private function addDeclareImportData(DeclareImport $declareImport, $location)
  {
    $animal = $declareImport->getAnimal();
    if($animal != null) {
      $animal->setAnimalCountryOrigin($declareImport->getAnimalCountryOrigin());
    }

    $declareImport->setAnimalType(AnimalType::sheep);

    $declareImport->setLocation($location);
    return $declareImport;
  }
}