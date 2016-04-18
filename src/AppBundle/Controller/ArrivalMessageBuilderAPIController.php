<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class ArrivalMessageBuilderAPIController
 * @package AppBundle\Controller
 */
class ArrivalMessageBuilderAPIController extends MessageBuilderBaseAPIController
{

    /**
     *
     * Accept front-end input and create a complete NSFO+IenR Message.
     *
     * @param Request $request
     * @param $requestId
     * @return ArrayCollection
     */
    public function buildMessage(Request $request, $requestId)
    {
        //Convert front-end message into an array
        $content = $this->getContentAsArray($request);
//        $content = new ArrayCollection();

        //Add general message data to the array
        $content = $this->addGeneralMessageData($content, $requestId);
        $content = $this->addRelationNumberKeeper($content, $request);

        //Add DeclareArrival specific data to the array
        $content = $this->addDeclareArrivalData($content);

        //Serialize after added properties to JSON
        $jsonMessage = $this->serializeToJSON($content);

        return $jsonMessage;
    }

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