<?php

namespace AppBundle\Controller;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Service\ProfileService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/api/v1/profiles")
 */
class ProfileAPIController extends APIController implements ProfileAPIControllerInterface {

  /**
   *
   * Get company profile
   *
   * @Route("/company")
   * @param Request $request
   * @Method("GET")
   * @return array
   */
  public function getCompanyProfile(Request $request)
  {
      return $this->get(ProfileService::class)->getCompanyProfile($request);
  }


  /**
   *
   * Get info for login data view
   * @Route("/login-info")
   * @param Request $request
   * @Method("GET")
   * @return jsonResponse
   */
  public function getLoginData(Request $request)
  {
      return $this->get(ProfileService::class)->getLoginData($request);
  }

  /**
   *
   * Update company profile
   *
   * @Route("/company")
   * @param Request $request
   * @Method("PUT")
   * @return array
   */
  public function editCompanyProfile(Request $request)
  {
      return $this->get(ProfileService::class)->editCompanyProfile($request);
  }

}