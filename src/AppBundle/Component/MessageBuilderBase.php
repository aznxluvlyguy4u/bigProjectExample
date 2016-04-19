<?php

namespace AppBundle\Component;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class MessageBuilderBaseAPIController
 * @package AppBundle\Controller
 */
class MessageBuilderBase
{
    const requestState = 'open';
    const action = "C";
    const recoveryIndicator = "N";
    const relationNumberKeeperNameSpace = "relation_number_keeper";

    /**
     * @param Request $request the message received from the front-end
     * @param $requestId a unique ID used to identify individual messages
     * @return ArrayCollection the base message
     */
    protected function buildBaseMessageArray($request)
    {
        //Convert front-end message into an array
        //$content = $this->getContentAsArray($request);

        //Add general message data to the array
        $content = $this->addGeneralMessageData($request);
        //$content = $this->addRelationNumberKeeper($request, $request);

        return $content;
    }

    /**
     * @param ArrayCollection $content array to which the extra data should be added to.
     * @param string $requestId a unique ID used to identify individual messages
     * @return ArrayCollection the base message
     */
    private function addGeneralMessageData(ArrayCollection $content)
    {
        //Generate new requestId
        $requestId = $this->getNewRequestId();

        //Add general data to content
        $content->set('request_state', $this::requestState);
        $content->set('request_id', $requestId);
        $content->set('message_id', $requestId);
        $content->set('log_date', new \DateTime());
        $content->set('action', $this::action);
        $content->set('recovery_indicator', $this::recoveryIndicator);
        //FIXME
        $content->set('relation_number_keeper', "123332");


        return $content;
    }

    /**
     * @param ArrayCollection $content array to which the extra data should be added to.
     * @param Request $request the message received from the front-end
     * @return ArrayCollection the input message to which the relationNumberKeeper has been added.
     */
    private function addRelationNumberKeeper(ArrayCollection $content, Request $request)
    {
        //Use token to retrieve the correct client from the database
        $client = $this->getClient($request);
        $relationNumberKeeper = $client->getRelationNumberKeeper();
        $content->set($this::relationNumberKeeperNameSpace, $relationNumberKeeper);

        return $content;
    }

    //TODO Check: It is assumed the token is already verified in the prehook so no "if" checks are used here.
    /**
     * @param Request $request the message received from the front-end
     * @return \AppBundle\Entity\Client the client who belongs the token in the request.
     */
    private function getClient(Request $request)
    {
        $token = $this->getToken($request);

        $em = $this->getDoctrine()->getEntityManager();
        $client = $em->getRepository('AppBundle:Client')->getByToken($token);

        return $client;
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