<?php

namespace AppBundle\Component;

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
     * @param ArrayCollection $content, the message received from the front-end
     * @param string $relationNumberKeeper
     * @return ArrayCollection
     */
    public function buildMessage($content, Client $client)
    {
        $content = $this->buildBaseMessageArray($content, $client);
        $content = $this->addDeclareArrivalData($content);

        return $content;
    }

    /**
     * @param ArrayCollection $content
     * @return ArrayCollection
     */
    private function addDeclareArrivalData(ArrayCollection $content)
    {

        //TODO filter on ULN or Pedigree code
        //Get whole animal from database
        //if Pedigree code > add ULN

        //animal = doctrineget....

        //FIXME
        $animal = $content['animal'];
        $newAnimalDetails = array_merge($animal,
            array('type' => 'Ram',
                'animal_type' => 3,
                'animal_category' => 1,
            ));

        $content->set('animal', $newAnimalDetails);




        return $content;
    }

}