<?php

namespace AppBundle\Controller;

use AppBundle\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/api/v1/messages")
 */
class MessageAPIController extends APIController
{
    /**
     * @param Request $request
     * @Method("GET")
     * @Route("")
     * @return jsonResponse
     */
    public function getMessages(Request $request)
    {
        return $this->get('app.messages')->getMessages($request);
    }

    /**
     * @param Request $request
     * @param string $messageId
     *
     * @Route("/read/{messageId}")
     * @Method("PUT")
     * @return jsonResponse
     */
    public function changeReadStatus(Request $request, $messageId)
    {
        return $this->get('app.messages')->changeReadStatus($request, $messageId);
    }

    /**
     * @param Request $request
     * @param string $messageId
     *
     * @Route("/hide/{messageId}")
     * @Method("PUT")
     * @return jsonResponse
     */
    public function hideMessage(Request $request, $messageId)
    {
        return $this->get('app.messages')->hideMessage($request, $messageId);
    }
}