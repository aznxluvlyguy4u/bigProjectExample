<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\Message;
use AppBundle\Enumerator\MessageType;
use AppBundle\Util\ActionLogWriter;
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

        $invoiceResults = $this->getManager()->getRepository(Message::class)->getInvoiceMessages($client, $location);
        $results = $this->getManager()->getRepository(Message::class)->getNonInvoiceMessages($client, $location);
        $results = array_merge($results, $invoiceResults);
        $newResults = [];
        foreach($results as $result) {
            $result['type_message'] = $this->translator->trans($result['type']);
            $result['url'] = $this->getMessageUrlByType($result['type']);
            $newResults[] = $result;
        }
        return ResultUtil::successResult($newResults);
    }

    private function getMessageUrlByType($type) {
        switch ($type) {
            case MessageType::DECLARE_ARRIVAL: {
                $messageUrl = 'main/arrival/history';
                break;
            }
            case MessageType::DECLARE_DEPART: {
                $messageUrl = 'main/departure/history';
                break;
            }
            case MessageType::NEW_INVOICE: {
                $messageUrl = 'main/invoices/overview';
                break;
            }
            default:
                $messageUrl = 'main/messages';
                break;
        }
        return $messageUrl;
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

        ActionLogWriter::changeMessageReadStatus($this->getManager(), $this->getAccountOwner($request), $this->getUser(), $message);

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

        ActionLogWriter::changeMessageHideStatus($this->getManager(), $this->getAccountOwner($request), $this->getUser(), $message);

        return ResultUtil::successResult('ok');
    }
}