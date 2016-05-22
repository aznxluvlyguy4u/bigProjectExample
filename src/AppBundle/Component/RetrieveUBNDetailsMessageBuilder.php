<?php

namespace AppBundle\Component;

use AppBundle\Entity\RetrieveUBNDetails;
use Doctrine\ORM\EntityManager;
use AppBundle\Entity\Person;

class RetrieveUBNDetailsMessageBuilder extends MessageBuilderBase
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
   * @param RetrieveUBNDetails $messageObject the message received
   * @param Person $person
   * @return RetrieveUBNDetails
   */
  public function buildMessage(RetrieveUBNDetails $messageObject, Person $person)
  {
    $this->person = $person;
    $baseMessageObject = $this->buildBaseRetrieveMessageObject($messageObject, $person);
    $completeMessageObject = $this->addRetrieveUBNDetailsData($baseMessageObject);
    $completeMessageObject->setAnimalType(3);

    return $completeMessageObject;
  }

  /**
   * @param RetrieveUBNDetails $retrieveUBNDetails the baseMessageObject
   * @return RetrieveUBNDetails
   */
  private function addRetrieveUBNDetailsData(RetrieveUBNDetails $retrieveUBNDetails)
  {
    //TODO For FASE 2 retrieve the correct location & company for someone having more than one location and/or company.
    $retrieveUBNDetails->setRelationNumberKeeper($this->person->getRelationNumberKeeper());

    return $retrieveUBNDetails;
  }

}