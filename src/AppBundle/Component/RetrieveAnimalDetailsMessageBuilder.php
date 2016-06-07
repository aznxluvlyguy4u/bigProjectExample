<?php

namespace AppBundle\Component;

use AppBundle\Entity\Client;
use AppBundle\Entity\RetrieveAnimalDetails;
use Doctrine\ORM\EntityManager;
use AppBundle\Entity\Person;

class RetrieveAnimalDetailsMessageBuilder extends MessageBuilderBase{
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
   * Create a complete NSFO+IenR Message.
   *
   * @param RetrieveAnimalDetails $messageObject the message received
   * @param Client|Person $person
   * @return RetrieveAnimalDetails
   */
  public function buildMessage(RetrieveAnimalDetails $messageObject, $person)
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
  private function addRetrieveAnimalDetailsData(RetrieveAnimalDetails $retrieveAnimalDetails)
  {

    //TODO For FASE 2 retrieve the correct location & company for someone having more than one location and/or company.
    $retrieveAnimalDetails->setLocation($this->person->getCompanies()[0]->getLocations()[0]);
    $retrieveAnimalDetails->setRelationNumberKeeper($this->person->getRelationNumberKeeper());

    return $retrieveAnimalDetails;
  }
}