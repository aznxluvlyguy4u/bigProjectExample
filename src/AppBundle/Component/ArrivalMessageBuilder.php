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

    /**
     * @var \JMS\Serializer\Serializer
     */
    private $serializer;

    public function __construct($serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     *
     * Accept front-end input and create a complete NSFO+IenR Message.
     *
     * @param Request $request, the message received from the front-end
     * @param $requestId
     * @return ArrayCollection
     */
    public function buildMessage($request, $requestId)
    {
        $content = $this->buildBaseMessageArray($request, $requestId);

        $content = $this->addDeclareArrivalData($content);

//        $jsonMessage = $this->serializeToJSON($content);
        $jsonMessage = $this->serializer->serialize($content, 'json');

        return $jsonMessage;
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