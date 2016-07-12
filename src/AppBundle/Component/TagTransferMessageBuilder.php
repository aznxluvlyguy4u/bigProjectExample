<?php

namespace AppBundle\Component;

use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareTagsTransfer;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\TagStateType;
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
   * @param string $currentEnvironment
   */
  public function __construct(EntityManager $em, $currentEnvironment)
  {
    parent::__construct($em, $currentEnvironment);
  }

  /**
   *
   * Create a complete NSFO+IenR Message.
   *
   * @param DeclareTagsTransfer $messageObject the message received
   * @param Client|Person $person
   * @param Location $location
   * @return ArrayCollection
   */
  public function buildMessage(DeclareTagsTransfer $messageObject, $person, $location)
  {
    $this->person = $person;
    $baseMessageObject = $this->buildBaseMessageObject($messageObject, $person);
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