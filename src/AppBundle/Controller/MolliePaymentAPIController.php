<?php

namespace AppBundle\Controller;

use AppBundle\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class MollieAPIController
 * @package AppBundle\Controller
 * @Route("/api/v1/mollie")
 */
class MolliePaymentAPIController extends APIController
{
    /**
     * @Method("POST")
     * @Route("")
     * @param Request $request
     * @return JsonResponse
     */
    public function createMolliePayment(Request $request)
    {
        return $this->get('app.mollie.payment')->createMolliePayment($request);
    }

    /**
     * @param Request $request
     * @Route("/update/{id}")
     * @Method("POST")
     * @return JsonResponse
     */
    public function paymentWebHook(Request $request, $id)
    {
        return $this->get('app.mollie.payment')->paymentWebHook($request, $id);
    }

    /**
     * @Method("GET")
     * @Route("/{id}")
     * @return JsonResponse
     * @param $id
     */
    public function getMolliePayment($id)
    {
        return $this->get('app.mollie.payment')->getMolliePayment($id);
    }
}