<?php

namespace AppBundle\Component;
use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareTagReplace;
use AppBundle\Entity\Person;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

/**
 * Class TagReplaceMessageBuilder
 * @package AppBundle\Component
 */
class TagReplaceMessageBuilder extends MessageBuilderBase {
  /**
   * @var Client|Person
   */
  private $person;

  /**
   * TagReplaceMessageBuilder constructor.
   * @param EntityManager $em
   */
  public function __construct(EntityManager $em) {
    parent::__construct($em);
  }

  /**
   *
   * Create a complete NSFO+IenR Message.
   *
   * @param DeclareTagReplace $messageObject the message received
   * @param Client|Person $person
   * @return ArrayCollection
   */
  public function buildMessage(DeclareTagReplace $messageObject, $person) {
    $this->person = $person;
    $baseMessageObject = $this->buildBaseMessageObject($messageObject, $person);
    $completeMessageObject = $this->addDeclareTagReplaceData($baseMessageObject);

    return $completeMessageObject;
  }

  /**
   * @param DeclareTagReplace $messageObject the message received
   * @return DeclareTagReplace
   */
  private function addDeclareTagReplaceData(DeclareTagReplace $messageObject) {

    //TODO For FASE 2 retrieve the correct location & company for someone having more than one location and/or company.
    $messageObject->setLocation($this->person->getCompanies()[0]->getLocations()[0]);
    return $messageObject;
  }
}