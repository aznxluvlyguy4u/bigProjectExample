<?php

namespace AppBundle\Component;

use AppBundle\Entity\Client;
use AppBundle\Entity\Location;
use AppBundle\Entity\RetrieveTags;
use Doctrine\ORM\EntityManager;
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
   * @param RetrieveTags $messageObject the message received
   * @param Client|Person $person
   * @param Location $location
   * @return ArrayCollection
   */
  public function buildMessage(RetrieveTags $messageObject, $person, $location)
  {
    $this->person = $person;
    $baseMessageObject = $this->buildBaseRetrieveMessageObject($messageObject, $person);
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