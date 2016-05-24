<?php

namespace AppBundle\Component;

use AppBundle\Entity\RetrieveUbnDetails;
use AppBundle\Setting\ActionFlagSetting;
use Doctrine\ORM\EntityManager;
use AppBundle\Entity\Person;

class RetrieveUbnDetailsMessageBuilder extends MessageBuilderBase
{

  /**
   * @var Person
   */
  private $person;

  public function __construct(EntityManager $em)
  {
    parent::__construct($em);
  }

  /**
   *
   * Create a complete NSFO+IenR Message.
   *
   * @param RetrieveUbnDetails $messageObject the message received
   * @param Person $person
   * @return RetrieveUbnDetails
   */
  public function buildMessage(RetrieveUbnDetails $messageObject, Person $person)
  {
    $this->person = $person;
    $baseMessageObject = $this->buildBaseRetrieveMessageObject($messageObject, $person);
    $completeMessageObject = $this->addRetrieveUbnDetailsData($baseMessageObject);
    $completeMessageObject->setAnimalType(3);

    return $completeMessageObject;
  }

  /**
   * @param RetrieveUbnDetails $retrieveUbnDetails the baseMessageObject
   * @return RetrieveUbnDetails
   */
  private function addRetrieveUbnDetailsData(RetrieveUbnDetails $retrieveUbnDetails)
  {
    if(ActionFlagSetting::RETRIEVE_UBN_DETAILS != null) {
      $retrieveUbnDetails->setAction(ActionFlagSetting::RETRIEVE_UBN_DETAILS);
    }

    //TODO For FASE 2 retrieve the correct location & company for someone having more than one location and/or company.
    $retrieveUbnDetails->setRelationNumberKeeper($this->person->getRelationNumberKeeper());

    return $retrieveUbnDetails;
  }

}