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
     * @param ArrayCollection $content the message received from the front-end
     * @param string $relationNumberKeeper
     * @return ArrayCollection the base message
     */
    protected function buildBaseMessageArray($content, $relationNumberKeeper)
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

        //Add relationNumberKeeper to content
        $content->set($this::relationNumberKeeperNameSpace, $relationNumberKeeper);

        return $content;
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