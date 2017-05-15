<?php
/**
 * Created by IntelliJ IDEA.
 * User: werner
 * Date: 12-5-17
 * Time: 14:58
 */

namespace AppBundle\Controller;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Constant\Endpoint;
use AppBundle\Entity\Invoice;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class MollieAPIController
 * @package AppBundle\Controller
 * @Route("/api/v1/mollie")
 */
class MollieAPIController extends APIController
{
    /**
     * @Method("POST")
     * @Route("")
     * @param Request $request
     * @return JsonResponse
     */
    public function createMolliePayment(Request $request){
        $content = $this->getContentAsArray($request);
        /** @var Invoice $invoice */
        $invoice = $this->getManager()->getRepository(Invoice::class)->findOneBy(array('id' => $content['id']));
        $mollie = $this->container->get('app.mollie.service');
        $payment = $mollie->createPayment($invoice);
        $invoice->setMollieId($payment->id);
        $this->persistAndFlush($invoice);
        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $payment), 200);
    }

    /**
     * @param Request $request
     * @Route("/update/{id}")
     * @Method("POST")
     * @return JsonResponse
     */
    public function paymentWebHook(Request $request, $id) {
        /** @var Invoice $invoice */
        $invoice = $this->getManager()->getRepository(Invoice::class)->findOneBy(array('id' => $id));
        $mollie = $this->container->get('app.mollie.service');
        $payment = $mollie->getPayment($invoice);
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
        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $payment), 200);
    }

    /**
     * @Method("GET")
     * @Route("/{id}")
     * @return JsonResponse
     * @param $id
     */
    public function getMolliePayment($id){
        $invoice = $this->getManager()->getRepository(Invoice::class)->findOneBy(array('id' => $id));
        $mollie = new \Mollie_API_Client();
        $mollie->setApiKey("test_CgTemR5kRzGMEnyJfxvCyFV5JV4Ang");
        $payment = $mollie->payments->get($invoice->getMollieId());
        $this->persistAndFlush($invoice);
        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $payment), 200);
    }
}