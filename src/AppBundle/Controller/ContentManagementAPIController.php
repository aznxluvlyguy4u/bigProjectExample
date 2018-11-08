<?php

namespace AppBundle\Controller;


use AppBundle\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ContentManagementAPIController
 * @package AppBundle\Controller
 * @Route("/api/v1/cms")
 */
class ContentManagementAPIController extends APIController implements ContentManagementAPIControllerInterface
{
    /**
     *
     * Get Content from intro text and contact info.
     *
     * @Route("")
     * @param Request $request
     * @Method("GET")
     * @return jsonResponse
     */
    public function getContentManagement(Request $request)
    {
        return $this->get('app.content_management')->getContentManagement($request);
    }


    /**
     *
     * Update Content
     *
     * Example of a request.
     * {
     *    "dashboard": "Welcome to our awesome system!",
     *    "contact_info": "Postbus ... E-mail-adres: kantoor@nsfo.nl"
     * }
     *
     * @Route("")
     * @param Request $request
     * @Method("PUT")
     * @return jsonResponse
     */
    public function editContentManagement(Request $request)
    {
        return $this->get('app.content_management')->editContentManagement($request);
    }
}