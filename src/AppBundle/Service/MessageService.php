<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\Message;
use AppBundle\Util\ResultUtil;
use Symfony\Component\HttpFoundation\Request;

class MessageService extends ControllerServiceBase
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getMessages(Request $request) {
        $client = $this->getAccountOwner($request);
        $location = $this->getSelectedLocation($request);

        if (!$client || !$location) {
            return ResultUtil::successResult([]);
        }

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

        $results = $this->getManager()->getConnection()->query($sql)->fetchAll();

        return ResultUtil::successResult($results);
    }


    /**
     * @param Request $request
     * @param $messageId
     * @return JsonResponse
     */
    public function changeReadStatus(Request $request, $messageId)
    {
        $message = $this->getManager()->getRepository(Message::class)->findOneBy(['messageId' => $messageId]);
        $message->setRead(true);

        $this->getManager()->persist($message);
        $this->getManager()->flush();

        return ResultUtil::successResult('ok');
    }


    /**
     * @param Request $request
     * @param $messageId
     * @return JsonResponse
     */
    public function hideMessage(Request $request, $messageId)
    {
        $message = $this->getManager()->getRepository(Message::class)->findOneBy(['messageId' => $messageId]);
        $message->setHidden(true);

        $this->getManager()->persist($message);
        $this->getManager()->flush();

        return ResultUtil::successResult('ok');
    }
}