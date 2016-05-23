<?php

namespace AppBundle\Component;

use AppBundle\Constant\Constant;
use AppBundle\Enumerator\AnimalType;
use AppBundle\Entity\Ram;
use AppBundle\Entity\DeclareLoss;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\Person;

/**
 * Class LossMessageBuilderAPIController
 * @package AppBundle\Controller
 */
class LossMessageBuilder extends MessageBuilderBase
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
     * Accept front-end input and create a complete NSFO+IenR Message.
     *
     * @param DeclareLoss $messageObject the message received from the front-end
     * @param Person $person
     * @return DeclareLoss
     */
    public function buildMessage(DeclareLoss $messageObject, Person $person)
    {
        $this->person = $person;
        $baseMessageObject = $this->buildBaseMessageObject($messageObject, $person);
        $completeMessageObject = $this->addDeclareLossData($baseMessageObject);

        return $completeMessageObject;
    }

    /**
     * @param DeclareLoss $declareLoss the baseMessageObject
     * @return DeclareLoss
     */
    private function addDeclareLossData(DeclareLoss $declareLoss)
    {
        $animal = $declareLoss->getAnimal();
        $animal->setDateOfDeath($declareLoss->getDateOfDeath());
        $animal->setIsAlive(false);

        $declareLoss->setAnimal($animal);
        //TODO For FASE 2 retrieve the correct location & company for someone having more than one location and/or company.
        $declareLoss->setLocation($this->person->getCompanies()[0]->getLocations()[0]);
        return $declareLoss;
    }

}