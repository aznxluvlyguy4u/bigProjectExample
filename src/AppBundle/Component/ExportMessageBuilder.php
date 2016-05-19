<?php

namespace AppBundle\Component;

use AppBundle\Enumerator\AnimalType;
use AppBundle\Entity\DeclareExport;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\Person;

class ExportMessageBuilder extends MessageBuilderBase
{
  /**
   * @var Person
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
   * @param Person $person
   * @return DeclareExport
   */
  public function buildMessage(DeclareExport $messageObject, Person $person)
  {
    $this->person = $person;
    $baseMessageObject = $this->buildBaseMessageObject($messageObject, $person);
    $completeMessageObject = $this->addDeclareExportData($baseMessageObject);

    return $completeMessageObject;
  }

  /**
   * @param DeclareExport $messageObject the message received
   * @param Person $person
   * @return DeclareExport
   */
  private function addDeclareExportData(DeclareExport $messageObject)
  {
    $animal = $messageObject->getAnimal();
    $animal->setAnimalType(AnimalType::sheep);

    //Both persist and flush are necessary for the animal
    $animal->setIsExportAnimal(true);
    $this->em->persist($animal);
    $this->em->flush();

    //TODO For FASE 2 retrieve the correct location & company for someone having more than one location and/or company.
    $messageObject->setLocation($this->person->getCompanies()[0]->getLocations()[0]);
    return $messageObject;
  }

}