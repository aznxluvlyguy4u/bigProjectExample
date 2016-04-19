<?php

namespace AppBundle\Component;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class ArrivalMessageBuilderAPIController
 * @package AppBundle\Controller
 */
class ArrivalMessageBuilder extends MessageBuilderBase
{

// TODO: If JSON Serializer is not needed in any message builder, delete this.
//
//    /**
//     * @var \JMS\Serializer\Serializer
//     */
//    private $serializer;
//
//    public function __construct($serializer)
//    {
//        $this->serializer = $serializer;
//    }
//
//    public function jsonSerializerExample($content)
//    {
//        $jsonMessage = $this->serializer->serialize($content, 'json');
//        return $jsonMessage;
//    }

    /**
     *
     * Accept front-end input and create a complete NSFO+IenR Message.
     *
     * @param ArrayCollection $content, the message received from the front-end
     * @param string $relationNumberKeeper
     * @return ArrayCollection
     */
    public function buildMessage($content, $relationNumberKeeper)
    {
        $content = $this->buildBaseMessageArray($content, $relationNumberKeeper);
        $content = $this->addDeclareArrivalData($content);

        return $content;
    }

    /**
     * @param ArrayCollection $content
     * @return ArrayCollection
     */
    private function addDeclareArrivalData(ArrayCollection $content)
    {

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