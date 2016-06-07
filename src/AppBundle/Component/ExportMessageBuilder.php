<?php

namespace AppBundle\Component;

use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareExport;
use AppBundle\Setting\ActionFlagSetting;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\Person;

class ExportMessageBuilder extends MessageBuilderBase
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
   * Accept input and create a complete NSFO+IenR Message.
   *
   * @param DeclareExport $messageObject the message received
   * @param Client|Person $person
   * @return DeclareExport
   */
  public function buildMessage(DeclareExport $messageObject, $person)
  {
    $this->person = $person;
    $baseMessageObject = $this->buildBaseMessageObject($messageObject, $person);
    $completeMessageObject = $this->addDeclareExportData($baseMessageObject);

    return $completeMessageObject;
  }

  /**
   * @param DeclareExport $messageObject the message received
   * @return DeclareExport
   */
  private function addDeclareExportData(DeclareExport $messageObject)
  {
    $animal = $messageObject->getAnimal();

    $messageObject->setUlnCountryCode($animal->getUlnCountryCode());
    $messageObject->setUlnNumber($animal->getUlnNumber());
    $messageObject->setPedigreeCountryCode($animal->getPedigreeCountryCode());
    $messageObject->setPedigreeNumber($animal->getPedigreeNumber());
    $messageObject->setIsExportAnimal(true);

    if(ActionFlagSetting::DECLARE_EXPORT != null) {
      $messageObject->setAction(ActionFlagSetting::DECLARE_EXPORT);
    }

    //TODO For FASE 2 retrieve the correct location & company for someone having more than one location and/or company.
    $messageObject->setLocation($this->person->getCompanies()[0]->getLocations()[0]);
    return $messageObject;
  }

}