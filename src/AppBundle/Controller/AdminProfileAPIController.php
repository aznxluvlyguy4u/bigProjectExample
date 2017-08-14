<?php

namespace AppBundle\Controller;

use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\FormInput\AdminProfile;
use AppBundle\Output\AdminOverviewOutput;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\AdminActionLogWriter;
use AppBundle\Validation\AdminValidator;
use AppBundle\Validation\EditAdminProfileValidator;
use AppBundle\Validation\PasswordValidator;
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
  public function getAdminProfile(Request $request) {

    $admin = $this->getEmployee();
    $adminValidator = new AdminValidator($admin, AccessLevelType::ADMIN);
    if (!$adminValidator->getIsAccessGranted()) { //validate if user is at least an ADMIN
      return $adminValidator->createJsonErrorResponse();
    }

    $outputArray = AdminOverviewOutput::createAdminOverview($admin);

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $outputArray), 200);
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
  public function editAdminProfile(Request $request) {

    $admin = $this->getEmployee();
    $adminValidator = new AdminValidator($admin, AccessLevelType::ADMIN);
    if (!$adminValidator->getIsAccessGranted()) { //validate if user is at least an ADMIN
      return $adminValidator->createJsonErrorResponse();
    }
    $encoder = $this->get('security.password_encoder');
    $content = $this->getContentAsArray($request);

    $em = $this->getManager();
    
    //Validate input
    $inputValidator = new EditAdminProfileValidator($em, $content, $admin);
    if (!$inputValidator->getIsValid()) {
      return $inputValidator->createJsonResponse();
    }
    
    //If password is changed: validate and encrypt it
    $newPassword = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::NEW_PASSWORD, $content);
    $passwordChangeLog = null;
    if($newPassword != null) {
      $newPassword = base64_decode($newPassword);

      //Validate password format
      $passwordValidator = new PasswordValidator($newPassword);
      if(!$passwordValidator->getIsPasswordValid()) {
        return $passwordValidator->createJsonErrorResponse();
      }
      $encodedNewPassword = $encoder->encodePassword($admin, $newPassword);
      $content->set(JsonInputConstant::NEW_PASSWORD, $encodedNewPassword);
      $passwordChangeLog = AdminActionLogWriter::passwordChangeAdminInProfile($em, $admin);
    }

    $valuesLog = AdminActionLogWriter::editOwnAdminProfile($em, $admin, $content);

    //Persist updated changes and return the updated values
    $client = AdminProfile::update($admin, $content);
    $this->getDoctrine()->getManager()->persist($admin);
    $this->getDoctrine()->getManager()->flush();

    ActionLogWriter::completeActionLog($em, $valuesLog);
    if ($passwordChangeLog) {
        ActionLogWriter::completeActionLog($em, $passwordChangeLog);
    }

    $outputArray = AdminOverviewOutput::createAdminOverview($admin);

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $outputArray), 200);
  }

}