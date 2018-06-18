<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/api/v1/mobile")
 */
class MobileDeviceAPIController extends APIController
{
    /**
     * @param Request $request
     * @Method("POST")
     * @Route("/logout")
     * @return jsonResponse
     */
    public function logout(Request $request){
        return $this->get('AppBundle\Service\MobileService')->logout($request);
    }

    /**
     * @param Request $request
     * @Method("POST")
     * @Route("/validate-registration-token")
     * @return jsonResponse
     */
    public function validateRegistrationToken(Request $request) {
        return $this->get('AppBundle\Service\MobileService')->validateRegistrationToken($request);
    }
}