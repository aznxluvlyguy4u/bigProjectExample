<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Message;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;

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
    public function getMessages(Request $request) {
        $client = $this->getAuthenticatedUser($request);
        $location = $this->getSelectedLocation($request);
        $em = $this->getDoctrine()->getManager();

        $sql = "SELECT
                  receiver.last_name AS receiver_last_name,
                  receiver.first_name AS receiver_last_name,
                  receiver_location.ubn AS receiver_ubn,
                  receiver_company.company_name AS receiver_company,
                  sender.last_name AS sender_last_name,
                  sender.first_name AS sender_first_name,
                  sender_location.ubn AS sender_ubn,
                  sender_company.company_name AS sender_company,
                  message.message_id,
                  message.type,
                  message.subject,
                  message.message,
                  message.is_read,
                  message.creation_date,
                  message.is_hidden,
                  message.data,
                  declare_base_response.success_indicator
                FROM
                  message
                LEFT JOIN person AS receiver ON message.receiver_id = receiver.id
                LEFT JOIN person AS sender ON message.sender_id = sender.id
                LEFT JOIN location AS receiver_location ON message.receiver_location_id = receiver_location.id
                LEFT JOIN location AS sender_location ON message.sender_location_id = sender_location.id
                LEFT JOIN company AS receiver_company ON receiver_location.company_id = receiver_company.id
                LEFT JOIN company AS sender_company ON sender_location.company_id = sender_company.id
                INNER JOIN declare_base_response ON message.declare_base_response_id = declare_base_response.id
                WHERE
                    message.receiver_id = " . $client->getId() . " OR message.receiver_location_id = '" . $location->getId() . "'";

        $results = $em->getConnection()->query($sql)->fetchAll();

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $results), 200);
    }

    /**
     * @param Request $request
     * @param string $messageId
     *
     * @Route("/read/{messageId}")
     * @Method("PUT")
     * @return jsonResponse
     */
    public function changeReadStatus(Request $request, $messageId) {
        $client = $this->getAuthenticatedUser($request);

        /** @var Message $message */
        $repository = $this->getDoctrine()->getRepository(Message::class);
        $message = $repository->findOneBy(['messageId' => $messageId]);
        $message->setRead(true);

        $this->getDoctrine()->getManager()->persist($message);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => 'ok'), 200);
    }

    /**
     * @param Request $request
     * @param string $messageId
     *
     * @Route("/hide/{messageId}")
     * @Method("PUT")
     * @return jsonResponse
     */
    public function hideMessage(Request $request, $messageId) {
        $client = $this->getAuthenticatedUser($request);

        /** @var Message $message */
        $repository = $this->getDoctrine()->getRepository(Message::class);
        $message = $repository->findOneBy(['messageId' => $messageId]);
        $message->setHidden(true);

        $this->getDoctrine()->getManager()->persist($message);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => 'ok'), 200);
    }
}