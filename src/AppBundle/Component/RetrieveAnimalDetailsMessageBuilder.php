<?php

namespace AppBundle\Component;

use AppBundle\Entity\RetrieveAnimalDetails;
use AppBundle\Setting\ActionFlagSetting;
use Doctrine\ORM\EntityManager;
use AppBundle\Entity\Person;

class RetrieveAnimalDetailsMessageBuilder extends MessageBuilderBase{
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
   * @param RetrieveAnimalDetails $messageObject the message received
   * @param Person $person
   * @return RetrieveAnimalDetails
   */
  public function buildMessage(RetrieveAnimalDetails $messageObject, Person $person)
  {
    $this->person = $person;
    $baseMessageObject = $this->buildBaseRetrieveMessageObject($messageObject, $person);
    $completeMessageObject = $this->addRetrieveAnimalDetailsData($baseMessageObject);

    return $completeMessageObject;
  }

  /**
   * @param RetrieveAnimalDetails $retrieveAnimalDetails the baseMessageObject
   * @return RetrieveAnimalDetails
   */
  private function addRetrieveAnimalDetailsData(RetrieveAnimalDetails $retrieveAnimals)
  {
    if(ActionFlagSetting::RETRIEVE_ANIMAL_DETAILS != null) {
      $retrieveAnimals->setAction(ActionFlagSetting::RETRIEVE_ANIMAL_DETAILS);
    }

    //TODO For FASE 2 retrieve the correct location & company for someone having more than one location and/or company.
    $retrieveAnimals->setLocation($this->person->getCompanies()[0]->getLocations()[0]);
    $retrieveAnimals->setRelationNumberKeeper($this->person->getRelationNumberKeeper());

    return $retrieveAnimals;
  }
}