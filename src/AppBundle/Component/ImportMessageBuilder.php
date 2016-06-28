<?php

namespace AppBundle\Component;

use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\AnimalType;
use AppBundle\Setting\ActionFlagSetting;
use Doctrine\ORM\EntityManager;
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

  public function __construct(EntityManager $em)
  {
    parent::__construct($em);
  }

  /**
   *
   * Accept front-end input and create a complete NSFO+IenR Message.
   *
   * @param DeclareImport $messageObject the message received from the front-end
   * @param Client|Person $person
   * @param Location $location
   * @return DeclareImport
   */
  public function buildMessage(DeclareImport $messageObject, $person, $location)
  {
    $this->person = $person;
    $baseMessageObject = $this->buildBaseMessageObject($messageObject, $person);
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

    if(ActionFlagSetting::DECLARE_IMPORT != null) {
      $declareImport->setAction(ActionFlagSetting::DECLARE_IMPORT);
    }

    $declareImport->setLocation($location);
    return $declareImport;
  }
}