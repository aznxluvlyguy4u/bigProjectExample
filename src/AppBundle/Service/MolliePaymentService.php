<?php

namespace AppBundle\Service;


use AppBundle\Entity\Invoice;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class MolliePaymentService extends ControllerServiceBase
{
    /** @var MollieService */
    private $mollieService;

    public function __construct(BaseSerializer $baseSerializer, CacheService $cacheService, EntityManagerInterface $manager, UserService $userService, MollieService $mollieService)
    {
        parent::__construct($baseSerializer, $cacheService, $manager, $userService);

        $this->mollieService = $mollieService;
    }


    /**
     * @param Request $request
     * @return \AppBundle\Component\HttpFoundation\JsonResponse
     */
    public function createMolliePayment(Request $request)
    {
        $content = RequestUtil::getContentAsArray($request);
        /** @var Invoice $invoice */
        $invoice = $this->getManager()->getRepository(Invoice::class)->findOneBy(['id' => $content['id']]);
        $payment = $this->mollieService->createPayment($invoice);
        $invoice->setMollieId($payment->id);
        $this->persistAndFlush($invoice);
        return ResultUtil::successResult($payment);
    }


    /**
     * @param Request $request
     * @param $id
     * @return \AppBundle\Component\HttpFoundation\JsonResponse
     */
    public function paymentWebHook(Request $request, $id)
    {
        /** @var Invoice $invoice */
        $invoice = $this->getManager()->getRepository(Invoice::class)->findOneBy(['id' => $id]);
        $payment = $this->mollieService->getPayment($invoice);
        switch ($payment->status){
            case 'paid':
                $invoice->setStatus("PAID");
                break;

            case 'cancelled':
                $invoice->setStatus("CANCELLED");
                break;

            default:
                $invoice->setStatus("CANCELLED");
                break;
        }
        $this->persistAndFlush($invoice);
        return ResultUtil::successResult($payment);
    }


    /**
     * @param $id
     * @return \AppBundle\Component\HttpFoundation\JsonResponse
     */
    public function getMolliePayment($id)
    {
        $invoice = $this->getManager()->getRepository(Invoice::class)->findOneBy(['id' => $id]);
        $mollie = new \Mollie_API_Client();
        $mollie->setApiKey("test_CgTemR5kRzGMEnyJfxvCyFV5JV4Ang");
        $payment = $mollie->payments->get($invoice->getMollieId());
        $this->persistAndFlush($invoice);
        return ResultUtil::successResult($payment);
    }
}