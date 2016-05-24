<?php

namespace AppBundle\Component;

use AppBundle\Entity\RetrieveAnimals;
use AppBundle\Entity\DeclareLoss;
use AppBundle\Setting\ActionFlagSetting;
use Doctrine\ORM\EntityManager;
use AppBundle\Entity\Person;

class RetrieveAnimalsMessageBuilder extends MessageBuilderBase
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
   * Create a complete NSFO+IenR Message.
   *
   * @param RetrieveAnimals $messageObject the message received
   * @param Person $person
   * @return RetrieveAnimals
   */
  public function buildMessage(RetrieveAnimals $messageObject, Person $person)
  {
    $this->person = $person;
    $baseMessageObject = $this->buildBaseRetrieveMessageObject($messageObject, $person);
    $completeMessageObject = $this->addRetrieveAnimalsData($baseMessageObject);

    return $completeMessageObject;
  }

  /**
   * @param RetrieveAnimals $retrieveAnimals the baseMessageObject
   * @return RetrieveAnimals
   */
  private function addRetrieveAnimalsData(RetrieveAnimals $retrieveAnimals)
  {
    if(ActionFlagSetting::RETRIEVE_ANIMAL != null) {
      $retrieveAnimals->setAction(ActionFlagSetting::RETRIEVE_ANIMAL);
    }

    //TODO For FASE 2 retrieve the correct location & company for someone having more than one location and/or company.
    $retrieveAnimals->setLocation($this->person->getCompanies()[0]->getLocations()[0]);
    $retrieveAnimals->setRelationNumberKeeper($this->person->getRelationNumberKeeper());

    return $retrieveAnimals;
  }
}