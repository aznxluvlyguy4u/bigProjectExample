<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\Message;
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