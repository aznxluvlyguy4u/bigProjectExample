<?php

namespace AppBundle\Component;

use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareLoss;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\AnimalType;
use Doctrine\Common\Persistence\ObjectManager;
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
     * @var Client|Person
     */
    private $person;

    public function __construct(ObjectManager $em, $currentEnvironment)
    {
        parent::__construct($em, $currentEnvironment);
    }

    /**
     *
     * Accept front-end input and create a complete NSFO+IenR Message.
     *
     * @param DeclareLoss $messageObject the message received from the front-end
     * @param Client|Person $person
     * @param Person $loggedInUser
     * @param Location $location
     * @return DeclareLoss
     */
    public function buildMessage(DeclareLoss $messageObject, $person, $loggedInUser, $location)
    {
        $this->person = $person;
        $baseMessageObject = $this->buildBaseMessageObject($messageObject, $person, $loggedInUser);
        $completeMessageObject = $this->addDeclareLossData($baseMessageObject, $location);

        return $completeMessageObject;
    }

    /**
     * @param DeclareLoss $declareLoss the baseMessageObject
     * @param Location $location
     * @return DeclareLoss
     */
    private function addDeclareLossData(DeclareLoss $declareLoss, $location)
    {
        $animal = $declareLoss->getAnimal();
        $declareLoss->setAnimal($animal);
        $declareLoss->setLocation($location);

        Utils::setResidenceToPending($animal, $location);
        
        $declareLoss->setAnimalType(AnimalType::sheep);

        return $declareLoss;
    }

}