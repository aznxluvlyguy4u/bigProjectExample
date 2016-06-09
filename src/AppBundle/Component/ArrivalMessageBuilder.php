<?php

namespace AppBundle\Component;

use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Enumerator\AnimalType;
use AppBundle\Setting\ActionFlagSetting;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\Person;

/**
 * Class ArrivalMessageBuilderAPIController
 * @package AppBundle\Controller
 */
class ArrivalMessageBuilder extends MessageBuilderBase
{
    /**
     * @var Client|Person
     */
    private $person;

    public function __construct(EntityManager $em)
    {
        parent::__construct($em);
    }

    /**
     *
     * Accept front-end input and create a complete NSFO+IenR Message.
     *
     * @param DeclareArrival $messageObject the message received from the front-end
     * @param Client|Person $person
     * @return DeclareArrival
     */
    public function buildMessage(DeclareArrival $messageObject, $person)
    {
        $this->person = $person;
        $baseMessageObject = $this->buildBaseMessageObject($messageObject, $person);
        $completeMessageObject = $this->addDeclareArrivalData($baseMessageObject);

        return $completeMessageObject;
    }

    /**
     * @param DeclareArrival $messageObject the baseMessageObject
     * @return DeclareArrival
     */
    private function addDeclareArrivalData(DeclareArrival $messageObject)
    {
        $messageObject->setAnimalType(AnimalType::sheep);
        if(ActionFlagSetting::DECLARE_ARRIVAL != null) {
            $messageObject->setAction(ActionFlagSetting::DECLARE_ARRIVAL);
        }

        //TODO For FASE 2 retrieve the correct location & company for someone having more than one location and/or company.
        $messageObject->setLocation($this->person->getCompanies()[0]->getLocations()[0]);
        return $messageObject;
    }

}