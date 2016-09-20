<?php

namespace AppBundle\Component;
use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareTagReplace;
use AppBundle\Entity\Location;
use AppBundle\Entity\Person;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

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
   * @param ObjectManager $em
   * @param string $currentEnvironment
   */
  public function __construct(ObjectManager $em, $currentEnvironment) {
    parent::__construct($em, $currentEnvironment);
  }

  /**
   *
   * Create a complete NSFO+IenR Message.
   *
   * @param DeclareTagReplace $messageObject the message received
   * @param Client|Person $person
   * @param Person $loggedInUser
   * @param Location $location
   * @return ArrayCollection
   */
  public function buildMessage(DeclareTagReplace $messageObject, $person, $loggedInUser, $location) {
    $this->person = $person;
    $baseMessageObject = $this->buildBaseMessageObject($messageObject, $person, $loggedInUser);
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