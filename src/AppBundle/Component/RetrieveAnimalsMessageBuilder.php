<?php

namespace AppBundle\Component;

use AppBundle\Entity\Client;
use AppBundle\Entity\Location;
use AppBundle\Entity\RetrieveAnimals;
use Doctrine\ORM\EntityManager;
use AppBundle\Entity\Person;

class RetrieveAnimalsMessageBuilder extends MessageBuilderBase
{
  /**
   * @var Client|Person
   */
  private $person;

  public function __construct(EntityManager $em, $currentEnvironment)
  {
    parent::__construct($em, $currentEnvironment);
  }

  /**
   *
   * Create a complete NSFO+IenR Message.
   *
   * @param RetrieveAnimals $messageObject the message received
   * @param Client|Person $person
   * @param Location $location
   * @return RetrieveAnimals
   */
  public function buildMessage(RetrieveAnimals $messageObject, $person, $location)
  {
    $this->person = $person;
    $baseMessageObject = $this->buildBaseRetrieveMessageObject($messageObject, $person);
    $completeMessageObject = $this->addRetrieveAnimalsData($baseMessageObject, $location);

    return $completeMessageObject;
  }

  /**
   * @param RetrieveAnimals $retrieveAnimals the baseMessageObject
   * @param Location $location
   * @return RetrieveAnimals
   */
  private function addRetrieveAnimalsData(RetrieveAnimals $retrieveAnimals, $location)
  {
    $retrieveAnimals->setLocation($location);
    $retrieveAnimals->setRelationNumberKeeper($this->person->getRelationNumberKeeper());

    return $retrieveAnimals;
  }
}