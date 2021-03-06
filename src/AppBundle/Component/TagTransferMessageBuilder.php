<?php

namespace AppBundle\Component;

use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareTagsTransfer;
use AppBundle\Entity\Location;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\TagStateType;
use Doctrine\Common\Persistence\ObjectManager;

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
   * @param DeclareTagsTransfer $messageObject the message received
   * @param Client|Person $person
   * @param Person $loggedInUser
   * @param Location $location
   * @return DeclareTagsTransfer
   */
  public function buildMessage(DeclareTagsTransfer $messageObject, $person, $loggedInUser, $location)
  {
    $this->person = $person;
    $baseMessageObject = $this->buildBaseMessageObject($messageObject, $person, $loggedInUser, $location);
    $completeMessageObject = $this->addDeclareEartagsTransferData($baseMessageObject, $location);

    return $completeMessageObject;
  }

  /**
   * @param DeclareTagsTransfer $messageObject the message received
   * @param Location $location
   * @return DeclareTagsTransfer
   */
  private function addDeclareEartagsTransferData(DeclareTagsTransfer $messageObject, $location)
  {
    $messageObject->setLocation($location);

    foreach($messageObject->getTags() as $tag) {
      $tag->setTagStatus(TagStateType::TRANSFERRING_TO_NEW_OWNER);
      $this->em->persist($tag);
      $this->em->flush();
    }

    foreach($messageObject->getTagTransferRequests() as $tagTransferRequest) {
      $this->em->persist($tagTransferRequest);
      $this->em->flush();
    }


    return $messageObject;
  }
}