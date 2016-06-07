<?php

namespace AppBundle\Component;

use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareTagsTransfer;
use AppBundle\Enumerator\TagStateType;
use AppBundle\Setting\ActionFlagSetting;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\Person;

/**
 * Class TagTransferMessageBuilder
 * @package AppBundle\Component
 */
class TagTransferMessageBuilder extends MessageBuilderBase {
  /**
   * @var Client|Person
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
   * @param Client|Person $person
   * @return ArrayCollection
   */
  public function buildMessage(DeclareTagsTransfer $messageObject, $person)
  {
    $this->person = $person;
    $baseMessageObject = $this->buildBaseMessageObject($messageObject, $person);
    $completeMessageObject = $this->addDeclareEartagsTransferData($baseMessageObject);

    return $completeMessageObject;
  }

  /**
   * @param DeclareTagsTransfer $messageObject the message received
   * @return DeclareTagsTransfer
   */
  private function addDeclareEartagsTransferData(DeclareTagsTransfer $messageObject)
  {
    //TODO For FASE 2 retrieve the correct location & company for someone having more than one location and/or company.
    $location = $this->person->getCompanies()[0]->getLocations()[0];
    $messageObject->setLocation($location);

    foreach($messageObject->getTags() as $tag) {
      $tag->setTagStatus(TagStateType::TRANSFERRING_TO_NEW_OWNER);
      $this->em->persist($tag);
      $this->em->flush();
    }

    if(ActionFlagSetting::TAG_TRANSFER != null) {
      $messageObject->setAction(ActionFlagSetting::TAG_TRANSFER);
    }

    foreach($messageObject->getTagTransferRequests() as $tagTransferRequest) {
      $this->em->persist($tagTransferRequest);
      $this->em->flush();
    }


    return $messageObject;
  }
}