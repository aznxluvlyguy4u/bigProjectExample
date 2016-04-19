<?php

namespace AppBundle\Component;

use AppBundle\Entity\Ram;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\Client as Client;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class ArrivalMessageBuilderAPIController
 * @package AppBundle\Controller
 */
class ArrivalMessageBuilder extends MessageBuilderBase
{

    /**
     *
     * Accept front-end input and create a complete NSFO+IenR Message.
     *
     * @param DeclareArrival $messageObject the message received from the front-end
     * @param string $relationNumberKeeper
     * @return ArrayCollection
     */
    public function buildMessage(DeclareArrival $messageObject, Client $client)
    {
        $messageObject = $this->buildBaseMessageObject($messageObject, $client);
        $messageObject = $this->addDeclareArrivalData($messageObject);

        return $messageObject;
    }

    /**
     * @param ArrayCollection $content
     * @return ArrayCollection
     */
    private function addDeclareArrivalData(DeclareArrival $messageObject)
    {

        //TODO if sheep exists retrieve, if it does not create ne

        //TODO filter on ULN or Pedigree code
        //Get whole animal from database
        //if Pedigree code > add ULN

        //animal = doctrineget....

        //FIXME
        //This is a simulation of retrieving animal data from the database.
        //Change this part by using the AnimalAPIController.php to send mock animal objects to the database, and retrieving that data.
//        $content = new ArrayCollection();
//        $animal = $content['animal'];
//        $newAnimalDetails = array_merge($animal,
//            array('type' => 'Ram',
//                'animal_type' => 3,
//                'animal_category' => 1,
//            ));
//        $content->set('animal', $newAnimalDetails);


        $animal = new Ram();
        $animal->setAnimalType(3);
        $animal->setAnimalCategory(1);

        $messageObject->setAnimal($animal);

        return $messageObject;
    }

}