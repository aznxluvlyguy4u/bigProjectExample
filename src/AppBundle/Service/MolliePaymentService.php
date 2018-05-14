<?php

namespace AppBundle\Service;


use AppBundle\Constant\Environment;
use AppBundle\Entity\Invoice;
use AppBundle\Enumerator\InvoiceStatus;
use AppBundle\Enumerator\MollieEnums;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

class MolliePaymentService extends ControllerServiceBase
{
    /** @var MollieService */
    private $mollieService;

    private $key;

    public function instantiateServices(MollieService $mollieService) {
        $this->mollieService = $mollieService;
    }

    public function setKeys($prodApiKey, $testApiKey, $environment) {
        if ($environment == Environment::PROD){
            $this->key = $prodApiKey;
        }
        else {
            $this->key = $testApiKey;
        }
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
            case MollieEnums::PAID_STATUS:
                $invoice->setStatus(InvoiceStatus::PAID);
                $invoice->setPaidDate(new \DateTime());
                break;

            case MollieEnums::CANCELLED_STATUS:
                $invoice->setStatus(InvoiceStatus::CANCELLED);
                break;

            default:
                $invoice->setStatus(InvoiceStatus::CANCELLED);
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
        $mollie->setApiKey($this->key);
        $payment = $mollie->payments->get($invoice->getMollieId());
        $this->persistAndFlush($invoice);
        return ResultUtil::successResult($payment);
    }
}