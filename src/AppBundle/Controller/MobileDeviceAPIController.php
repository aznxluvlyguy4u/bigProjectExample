<?php

namespace AppBundle\Controller;

use AppBundle\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

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