<?php

namespace AppBundle\Component;

use AppBundle\Entity\RetrieveTags;
use AppBundle\Enumerator\AnimalType;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\Person;

/**
 * Class TagSyncMessageBuilder
 * @package AppBundle\Component
 */
class TagSyncMessageBuilder extends MessageBuilderBase {
  /**
   * @var Person
   */
  private $person;

  /**
   * TagSyncMessageBuilder constructor.
   * @param EntityManager $em
   */
  public function __construct(EntityManager $em)
  {
    parent::__construct($em);
  }

  /**
   *
   * Create a complete NSFO+IenR Message.
   *
   * @param RetrieveEartags $messageObject the message received
   * @param string $relationNumberKeeper
   * @return ArrayCollection
   */
  public function buildMessage(RetrieveTags $messageObject, Person $person)
  {
    $this->person = $person;
    $baseMessageObject = $this->buildBaseMessageObject($messageObject, $person);
    $completeMessageObject = $this->addRetrieveEartagsData($baseMessageObject);

    return $completeMessageObject;
  }

  /**
   * @param RetrieveTags $messageObject the message received
   * @param Person $person
   * @return RetrieveTags
   */
  private function addRetrieveEartagsData(RetrieveTags $messageObject)
  {
    //TODO For FASE 2 retrieve the correct location & company for someone having more than one location and/or company.
    $messageObject->setLocation($this->person->getCompanies()[0]->getLocations()[0]);
    return $messageObject;
  }
}