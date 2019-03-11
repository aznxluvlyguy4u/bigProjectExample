<?php


namespace AppBundle\Service;

use Enqueue\Util\UUID;
use Symfony\Component\HttpFoundation\Request;

class FeedbackQueueInvoiceMessageService
{
    /** @var BaseSerializer */
    private $serializer;

    /** @var AwsFeedbackQueueService */
    private $feedbackQueueService;

    public function instantiateServices(BaseSerializer $serializer, AwsFeedbackQueueService $awsFeedbackQueue) {
        $this->serializer = $serializer;
        $this->feedbackQueueService = $awsFeedbackQueue;
    }

    public function createBatchInvoiceMessage(Request $request) {
        $requestJson = json_decode($request->getContent(), true);
        $date = $requestJson["controlDate"];
        return $this->feedbackQueueService->send($date, "BATCH_INVOICE_GENERATION", UUID::generate());
    }
}