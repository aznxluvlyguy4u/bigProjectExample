<?php

namespace AppBundle\Controller;

use AppBundle\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/api/v1/admin/dashboard")
 */
class DashboardAdminAPIController extends APIController
{
    /**
     * Get data for dashboard view
     *
     * @param Request $request the request object
     * @Route("")
     * @Method("GET")
     * @return JsonResponse
     */
    public function getAdminDashBoard(Request $request)
    {
        return $this->get('app.dashboard')->getAdminDashBoard($request);
    }

}