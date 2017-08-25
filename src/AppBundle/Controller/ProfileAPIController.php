<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use AppBundle\FormInput\CompanyProfile;
use AppBundle\Output\CompanyProfileOutput;
use AppBundle\Output\LoginOutput;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\RequestUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;

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
   * @return jsonResponse
   */
  public function getCompanyProfile(Request $request)
  {
      return $this->get('app.profile')->getCompanyProfile($request);
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
      return $this->get('app.profile')->getLoginData($request);
  }

  /**
   *
   * Update company profile
   *
   * @Route("/company")
   * @param Request $request
   * @Method("PUT")
   * @return jsonResponse
   */
  public function editCompanyProfile(Request $request)
  {
      return $this->get('app.profile')->editCompanyProfile($request);
  }

}