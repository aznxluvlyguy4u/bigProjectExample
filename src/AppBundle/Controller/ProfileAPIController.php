<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use AppBundle\FormInput\CompanyProfile;
use AppBundle\Output\CompanyProfileOutput;
use AppBundle\Output\LoginOutput;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\DoctrineUtil;
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
  public function getCompanyProfile(Request $request) {
    $client = $this->getAuthenticatedUser($request);
    $location = $this->getSelectedLocation($request);

    //TODO Phase 2: Give back a specific company and location of that company. The CompanyProfileOutput already can process a ($client, $company, $location) method signature.
    $company = $location->getCompany();

    $outputArray = CompanyProfileOutput::create($client, $company, $location);

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $outputArray), 200);
  }


  /**
   *
   * Get info for login data view
   * @Route("/login-info")
   * @param Request $request
   * @Method("GET")
   * @return jsonResponse
   */
  public function getLoginData(Request $request) {
    $client = $this->getAuthenticatedUser($request);

    $outputArray = LoginOutput::create($client);

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $outputArray), 200);
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
  public function editCompanyProfile(Request $request) {
    $om = $this->getDoctrine()->getManager();
    
    $client = $this->getAuthenticatedUser($request);
    $loggedInUser = $this->getLoggedInUser($request);
    $content = $this->getContentAsArray($request);
    $location = $this->getSelectedLocation($request);

    //TODO Phase 2: Give back a specific company and location of that company. The CompanyProfileOutput already can process a ($client, $company, $location) method signature.
    $company = $location->getCompany();

    //Persist updated changes and return the updated values
    $client = CompanyProfile::update($client, $content, $company);
    $om->persist($client);
    $log = ActionLogWriter::updateProfile($om, $client, $loggedInUser, $company);
    DoctrineUtil::flushClearAndGarbageCollect($om); //Only flush after persisting both the client and ActionLogWriter
    
    $outputArray = CompanyProfileOutput::create($client, $company, $location);

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $outputArray), 200);
  }

}