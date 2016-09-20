<?php

namespace AppBundle\Component;

use AppBundle\Entity\Client;
use AppBundle\Entity\Location;
use AppBundle\Entity\RetrieveTags;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\Person;

/**
 * Class TagSyncMessageBuilder
 * @package AppBundle\Component
 */
class TagSyncMessageBuilder extends MessageBuilderBase {
  /**
   * @var Client|Person
   */
  private $person;

  /**
   * TagSyncMessageBuilder constructor.
   * @param ObjectManager $em
   * @param string $currentEnvironment
   */
  public function __construct(ObjectManager $em, $currentEnvironment)
  {
    parent::__construct($em, $currentEnvironment);
  }

  /**
   *
   * Create a complete NSFO+IenR Message.
   *
   * @param RetrieveTags $messageObject the message received
   * @param Client|Person $person
   * @param Person $loggedInUser
   * @param Location $location
   * @return ArrayCollection
   */
  public function buildMessage(RetrieveTags $messageObject, $person, $loggedInUser, $location)
  {
    $this->person = $person;
    $baseMessageObject = $this->buildBaseRetrieveMessageObject($messageObject, $person, $loggedInUser);
    $completeMessageObject = $this->addRetrieveEartagsData($baseMessageObject, $location);

    return $completeMessageObject;
  }

  /**
   * @param RetrieveTags $messageObject the message received
   * @param Location $location
   * @return RetrieveTags
   */
  private function addRetrieveEartagsData(RetrieveTags $messageObject, $location)
  {
    $messageObject->setLocation($location);
    return $messageObject;
  }
}