<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Output\AdminDashboardOutput;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Validation\AdminValidator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

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