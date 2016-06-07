<?php

namespace AppBundle\Component;

use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareImport;
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
   * @return DeclareImport
   */
  public function buildMessage(DeclareImport $messageObject, $person)
  {
    $this->person = $person;
    $baseMessageObject = $this->buildBaseMessageObject($messageObject, $person);
    $completeMessageObject = $this->addDeclareImportData($baseMessageObject);

    return $completeMessageObject;
  }

  /**
   * @param DeclareImport $declareImport the message received from the front-end
   * @return DeclareImport
   */
  private function addDeclareImportData(DeclareImport $declareImport)
  {
    $animal = $declareImport->getAnimal();
    if($animal != null) {
      $animal->setAnimalCountryOrigin($declareImport->getAnimalCountryOrigin());
    }

    if(ActionFlagSetting::DECLARE_IMPORT != null) {
      $declareImport->setAction(ActionFlagSetting::DECLARE_IMPORT);
    }

    //TODO For FASE 2 retrieve the correct location & company for someone having more than one location and/or company.
    $declareImport->setLocation($this->person->getCompanies()[0]->getLocations()[0]);
    return $declareImport;
  }
}