<?php

namespace AppBundle\Component;
use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareTagReplace;
use AppBundle\Entity\Location;
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
   * @param string $currentEnvironment
   */
  public function __construct(EntityManager $em, $currentEnvironment) {
    parent::__construct($em, $currentEnvironment);
  }

  /**
   *
   * Create a complete NSFO+IenR Message.
   *
   * @param DeclareTagReplace $messageObject the message received
   * @param Client|Person $person
   * @param Location $location
   * @return ArrayCollection
   */
  public function buildMessage(DeclareTagReplace $messageObject, $person, $location) {
    $this->person = $person;
    $baseMessageObject = $this->buildBaseMessageObject($messageObject, $person);
    $completeMessageObject = $this->addDeclareTagReplaceData($baseMessageObject, $location);

    return $completeMessageObject;
  }

  /**
   * @param DeclareTagReplace $messageObject the message received
   * @param Location $location
   * @return DeclareTagReplace
   */
  private function addDeclareTagReplaceData(DeclareTagReplace $messageObject, $location) {

    if($messageObject->getReplaceDate() == null) {
      $messageObject->setReplaceDate($messageObject->getLogDate());
    }

    $messageObject->setLocation($location);
    return $messageObject;
  }
}