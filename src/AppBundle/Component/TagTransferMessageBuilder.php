<?php

namespace AppBundle\Component;

use AppBundle\Entity\DeclareTagsTransfer;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\Person;

/**
 * Class TagTransferMessageBuilder
 * @package AppBundle\Component
 */
class TagTransferMessageBuilder extends MessageBuilderBase {
  /**
   * @var Person
   */
  private $person;

  /**
   * TagTransferMessageBuilder constructor.
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
   * @param DeclareTagsTransfer $messageObject the message received
   * @param string $relationNumberKeeper
   * @return ArrayCollection
   */
  public function buildMessage(DeclareTagsTransfer $messageObject, Person $person)
  {
    $this->person = $person;
    $baseMessageObject = $this->buildBaseMessageObject($messageObject, $person);
    $completeMessageObject = $this->addDeclareEartagsTransferData($baseMessageObject);

    return $completeMessageObject;
  }

  /**
   * @param DeclareTagsTransfer $messageObject the message received
   * @param Person $person
   * @return DeclareTagsTransfer
   */
  private function addDeclareEartagsTransferData(DeclareTagsTransfer $messageObject)
  {
    //TODO For FASE 2 retrieve the correct location & company for someone having more than one location and/or company.
    $messageObject->setLocation($this->person->getCompanies()[0]->getLocations()[0]);
    return $messageObject;
  }
}