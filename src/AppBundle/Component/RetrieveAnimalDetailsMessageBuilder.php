<?php

namespace AppBundle\Component;

use AppBundle\Entity\Client;
use AppBundle\Entity\Location;
use AppBundle\Entity\RetrieveAnimalDetails;
use Doctrine\ORM\EntityManager;
use AppBundle\Entity\Person;

class RetrieveAnimalDetailsMessageBuilder extends MessageBuilderBase{
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
   * @param RetrieveAnimalDetails $messageObject the message received
   * @param Client|Person $person
   * @param Person $loggedInUser
   * @param Location $location
   * @return RetrieveAnimalDetails
   */
  public function buildMessage(RetrieveAnimalDetails $messageObject, $person, $loggedInUser, $location)
  {
    $this->person = $person;
    $baseMessageObject = $this->buildBaseRetrieveMessageObject($messageObject, $person, $loggedInUser);
    $completeMessageObject = $this->addRetrieveAnimalDetailsData($baseMessageObject, $location);

    return $completeMessageObject;
  }

  /**
   * @param RetrieveAnimalDetails $retrieveAnimalDetails the baseMessageObject
   * @param Location $location
   * @return RetrieveAnimalDetails
   */
  private function addRetrieveAnimalDetailsData(RetrieveAnimalDetails $retrieveAnimalDetails, $location)
  {
    $retrieveAnimalDetails->setLocation($location);
    $retrieveAnimalDetails->setRelationNumberKeeper($this->person->getRelationNumberKeeper());

    return $retrieveAnimalDetails;
  }
}