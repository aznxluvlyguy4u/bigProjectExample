<?php

namespace AppBundle\Component;

use AppBundle\Entity\RetrieveTags;
use AppBundle\Enumerator\AnimalType;
use AppBundle\Setting\ActionFlagSetting;
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
   * @param RetrieveTags $messageObject the message received
   * @param Client|Person $person
   * @return ArrayCollection
   */
  public function buildMessage(RetrieveTags $messageObject, $person)
  {
    $this->person = $person;
    $baseMessageObject = $this->buildBaseRetrieveMessageObject($messageObject, $person);
    $completeMessageObject = $this->addRetrieveEartagsData($baseMessageObject);

    return $completeMessageObject;
  }

  /**
   * @param RetrieveTags $messageObject the message received
   * @return RetrieveTags
   */
  private function addRetrieveEartagsData(RetrieveTags $messageObject)
  {
    if(ActionFlagSetting::TAG_SYNC != null) {
      $messageObject->setAction(ActionFlagSetting::TAG_SYNC);
    }

    //TODO For FASE 2 retrieve the correct location & company for someone having more than one location and/or company.
    $messageObject->setLocation($this->person->getCompanies()[0]->getLocations()[0]);
    return $messageObject;
  }
}