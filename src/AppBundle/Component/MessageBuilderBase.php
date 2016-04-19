<?php

namespace AppBundle\Component;

use AppBundle\Entity\Client as Client;
use AppBundle\Entity\DeclareBase as DeclareBase;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class MessageBuilderBaseAPIController
 * @package AppBundle\Controller
 */
class MessageBuilderBase
{
    /**
     * Most of the default values are set in the constructor of DeclareBase.
     * Here the values are set for the variables that could not easily
     * be set in the constructor.
     *
     * @param object $messageObject the message received from the front-end as an entity from a class that is extended from DeclareBase.
     * @param string $relationNumberKeeper
     * @return ArrayCollection the base message
     */
    protected function buildBaseMessageObject($messageObject, Client $client)
    {
        //Generate new requestId
        $requestId = $this->getNewRequestId();

        //Add general data to content
        $messageObject->setRequestId($requestId);
        $messageObject->setMessageId($requestId);;

        //Add relationNumberKeeper to content
        $relationNumberKeeper = $client->getRelationNumberKeeper();
        $messageObject->setRecoveryIndicator($relationNumberKeeper);

        return $messageObject;
    }

    /**
     * Generate a pseudo random requestId of MAX length 20
     *
     * @return string
     */
    private function getNewRequestId()
    {
        $maxLengthRequestId = 20;
        return join('', array_map(function($value) { return $value == 1 ? mt_rand(1, 9) :
            mt_rand(0, 9); }, range(1, $maxLengthRequestId)));
    }
}