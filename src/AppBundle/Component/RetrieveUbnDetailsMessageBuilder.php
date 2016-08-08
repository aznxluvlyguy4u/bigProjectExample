<?php

namespace AppBundle\Component;

use AppBundle\Entity\Client;
use AppBundle\Entity\RetrieveUbnDetails;
use AppBundle\Enumerator\AnimalType;
use Doctrine\ORM\EntityManager;
use AppBundle\Entity\Person;

class RetrieveUbnDetailsMessageBuilder extends MessageBuilderBase
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
   * @param RetrieveUbnDetails $messageObject the message received
   * @param Client|Person $person
   * @return RetrieveUbnDetails
   */
  public function buildMessage(RetrieveUbnDetails $messageObject, $person)
  {
    $this->person = $person;
    $baseMessageObject = $this->buildBaseRetrieveMessageObject($messageObject, $person);
    $completeMessageObject = $this->addRetrieveUbnDetailsData($baseMessageObject);

    return $completeMessageObject;
  }

  /**
   * @param RetrieveUbnDetails $retrieveUbnDetails the baseMessageObject
   * @return RetrieveUbnDetails
   */
  private function addRetrieveUbnDetailsData(RetrieveUbnDetails $retrieveUbnDetails)
  {
    $retrieveUbnDetails->setAnimalType(AnimalType::sheep);

    //TODO For FASE 2 retrieve the correct location & company for someone having more than one location and/or company.
    $retrieveUbnDetails->setRelationNumberKeeper($this->person->getRelationNumberKeeper());

    return $retrieveUbnDetails;
  }

}