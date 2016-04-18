<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class MessageBuilderBaseAPIController
 * @package AppBundle\Controller
 */
class MessageBuilderBaseAPIController extends APIController
{
    const requestState = 'open';
    const action = "C";
    const recoveryIndicator = "N";
    const relationNumberKeeperNameSpace = "relation_number_keeper";

    protected function addGeneralMessageData(ArrayCollection $content, $requestId)
    {
        //Add general data to content
        $content->set('request_state', $this::requestState);
        $content->set('request_id', $requestId);
        $content->set('message_id', $requestId);
        $content->set('log_date', new \DateTime());
        $content->set('action', $this::action);
        $content->set('recovery_indicator', $this::recoveryIndicator);

        return $content;
    }

    protected function addRelationNumberKeeper(ArrayCollection $content, Request $request)
    {
        //Use token to retrieve the correct client from the database
        $client = $this->getClient($request);
        $relationNumberKeeper = $client->getRelationNumberKeeper();
        $content->set($this::relationNumberKeeperNameSpace, $relationNumberKeeper);

        return $content;
    }

    //TODO Check: It is assumed the token is already verified in the prehook so no "if" checks are used here.
    private function getClient(Request $request)
    {
        $token = $this->getToken($request);

        $em = $this->getDoctrine()->getEntityManager();
        $client = $em->getRepository('AppBundle:Client')->getByToken($token);

        return $client;
    }
}