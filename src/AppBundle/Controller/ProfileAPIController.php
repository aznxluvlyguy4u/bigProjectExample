<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use AppBundle\Output\CompanyProfileOutput;
use AppBundle\Output\LoginOutput;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/api/v1/profiles")
 */
class ProfileAPIController extends APIController {

  /**
   *
   * Get company profile
   *
   * @Route("/company")
   * @Method("GET")
   */
  public function getCompanyProfile(Request $request) {
    $client = $this->getAuthenticatedUser($request);

    //TODO Phase 2: Give back a specific company and location of that company. The CompanyProfileOutput already can process a ($client, $company, $location) method signature.
    $outputArray = CompanyProfileOutput::create($client);

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $outputArray), 200);
  }


  /**
   *
   * Get info for login data view
   * @Route("/login-info")
   * @Method("GET")
   */
  public function getLoginDataIR(Request $request) {
    $client = $this->getAuthenticatedUser($request);

    $outputArray = LoginOutput::create($client);

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $outputArray), 200);
  }

}