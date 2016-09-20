<?php

namespace AppBundle\Component;

use AppBundle\Entity\Client;
use AppBundle\Entity\Location;
use AppBundle\Entity\RetrieveAnimals;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Entity\Person;

class RetrieveAnimalsMessageBuilder extends MessageBuilderBase
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
   * Create a complete NSFO+IenR Message.
   *
   * @param RetrieveAnimals $messageObject the message received
   * @param Client|Person $person
   * @param Person $loggedInUser
   * @param Location $location
   * @return RetrieveAnimals
   */
  public function buildMessage(RetrieveAnimals $messageObject, $person, $loggedInUser, $location)
  {
    $this->person = $person;
    $baseMessageObject = $this->buildBaseRetrieveMessageObject($messageObject, $person, $loggedInUser);
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