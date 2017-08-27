<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/api/v1/profiles-admin")
 */
class AdminProfileAPIController extends APIController implements AdminProfileAPIControllerInterface {

  /**
   *
   * Get admin profile
   *
   * @Route("")
   * @param Request $request
   * @Method("GET")
   * @return jsonResponse
   */
  public function getAdminProfile(Request $request)
  {
      return $this->get('app.admin.profile')->getAdminProfile($request);
  }


  /**
   *
   * Update admin profile
   *
   * Example of a request.
   * {
   *    "first_name": "Fox",
   *    "last_name": "McCloud",
   *    "email_address": "arwing001@lylat.com",
   *    "new_password": "Tm90TXlGaXJzdFBhc3N3b3JkMQ==" //base64 encoded 'NotMyFirstPassword1'
   * }
   *
   * @Route("")
   * @param Request $request
   * @Method("PUT")
   * @return jsonResponse
   */
  public function editAdminProfile(Request $request)
  {
      return $this->get('app.admin.profile')->editAdminProfile($request);
  }

}