<?php

namespace AppBundle\Controller;

use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Client;
use AppBundle\Entity\Company;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Person;
use AppBundle\Entity\Token;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\TokenType;
use AppBundle\Output\AccessLevelOverviewOutput;
use AppBundle\Output\AdminOverviewOutput;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\AdminActionLogWriter;
use AppBundle\Validation\AdminValidator;
use AppBundle\Validation\CreateAdminValidator;
use AppBundle\Validation\EditAdminValidator;
use AppBundle\Validation\EmployeeValidator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/api/v1/admins")
 */
class AdminAPIController extends APIController implements AdminAPIControllerInterface {

  const timeLimitInMinutes = 3;

  /**
   * Retrieve a list of all Admins
   *
   * @ApiDoc(
   *   section = "Admins",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the admin that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Retrieve a list of all Admins"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("GET")
   */
  public function getAdmins(Request $request)
  {
    $admin = $this->getEmployee();
    $adminValidator = new AdminValidator($admin, AccessLevelType::SUPER_ADMIN);
    if(!$adminValidator->getIsAccessGranted()) { //validate if user is at least a SUPER_ADMIN
        return $adminValidator->createJsonErrorResponse();
    }
    
    $repository = $this->getDoctrine()->getRepository(Employee::class);

    $admins = $repository->findBy(array('isActive' => true));
    $result = AdminOverviewOutput::createAdminsOverview($admins);

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $result), 200);
  }


  /**
   *
   * Create new Admin
   *
   * @ApiDoc(
   *   section = "Admins",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the admin that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Create new Admin"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("POST")
   */
    public function createAdmin(Request $request) {
        $actionBy = $this->getEmployee();
        $adminValidator = new AdminValidator($actionBy, AccessLevelType::SUPER_ADMIN);
        if (!$adminValidator->getIsAccessGranted()) { //validate if user is at least a SUPER_ADMIN
            return $adminValidator->createJsonErrorResponse();
        }

        $em = $this->getDoctrine()->getManager();
        $content = $this->getContentAsArray($request);

        $log = AdminActionLogWriter::createAdmin($em, $actionBy, $content);

        // Validate content
        $firstName = $content->get('first_name');
        $lastName = $content->get('last_name');
        $emailAddress = $content->get('email_address');
        $accessLevel = $content->get('access_level');

        if(empty($firstName) || empty($lastName) || empty($emailAddress) || empty($accessLevel)) {
            return new JsonResponse(array(
                'code'=> 400,
                "message" => "REQUIRED VALUES MISSING"), 400);
        }

        $inputValidator = new CreateAdminValidator($em, $content);
        if (!$inputValidator->getIsValid()) {
          return $inputValidator->createJsonResponse();
        }

        // Create new admin
        $newAdmin = new Employee($accessLevel, $firstName, $lastName, $emailAddress);

        // Send Email with passwords to Owner & Users
        $password = $this->persistNewPassword($newAdmin);
        $this->emailNewPasswordToPerson($newAdmin, $password, false, true);

        $em->persist($newAdmin);
        $em->flush();

        $repository = $this->getDoctrine()->getRepository(Employee::class);
        $actionBy = $repository->findOneBy(array(
            'emailAddress' => $newAdmin->getEmailAddress(),
            'isActive' => $newAdmin->getIsActive()
            ));

        $result = AdminOverviewOutput::createAdminOverview($actionBy);

        $log = AdminActionLogWriter::completeAdminCreateOrEditActionLog($em, $log, $newAdmin);

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $result), 200);
  }


  /**
   *
   * Edit Admins.
   *
   * @ApiDoc(
   *   section = "Admins",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the admin that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Edit Admins"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("PUT")
   */
    public function editAdmin(Request $request) {
        $actionBy = $this->getEmployee();
        $adminValidator = new AdminValidator($actionBy, AccessLevelType::SUPER_ADMIN);
        if (!$adminValidator->getIsAccessGranted()) { //validate if user is at least a SUPER_ADMIN
            return $adminValidator->createJsonErrorResponse();
        }

        $em = $this->getDoctrine()->getManager();
        $content = $this->getContentAsArray($request);
        $log = AdminActionLogWriter::editAdmin($em, $actionBy, $content);

        // Validate content
        $personId = $content->get('person_id');
        $firstName = $content->get('first_name');
        $lastName = $content->get('last_name');
        $emailAddress = $content->get('email_address');
        $accessLevel = $content->get('access_level');

        $inputValidator = new EditAdminValidator($em, $content);
        if (!$inputValidator->getIsValid()) {
          return $inputValidator->createJsonResponse();
        }

        $repository = $this->getDoctrine()->getRepository(Employee::class);
        $admin = $repository->findOneByPersonId($personId);

        $admin->setFirstName($firstName);
        $admin->setLastName($lastName);
        $admin->setEmailAddress($emailAddress);
        $admin->setAccessLevel($accessLevel);

        $em->persist($admin);
        $em->flush();

        $newAdmin = $repository->findOneBy(array(
            'emailAddress' => $admin->getEmailAddress(),
            'isActive' => $admin->getIsActive()
        ));
        $result = AdminOverviewOutput::createAdminOverview($newAdmin);

        $log = AdminActionLogWriter::completeAdminCreateOrEditActionLog($em, $log, $admin);

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $result), 200);
  }


  /**
   * Deactivate a list of Admins
   *
   * @ApiDoc(
   *   section = "Admins",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the admin that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Deactivate a list of Admins"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-deactivate")
   * @Method("PUT")
   */
  public function deactivateAdmin(Request $request)
  {
    $actionBy = $this->getEmployee();
    $adminValidator = new AdminValidator($actionBy, AccessLevelType::SUPER_ADMIN);
    if(!$adminValidator->getIsAccessGranted()) { //validate if user is at least a SUPER_ADMIN
      return $adminValidator->createJsonErrorResponse();
    }

    $em = $this->getDoctrine()->getManager();
    $repository = $this->getDoctrine()->getRepository(Employee::class);
    
    $content = $this->getContentAsArray($request);

    $personId = $content->get('person_id');
    /** @var Employee $adminToDeactivate */
    $adminToDeactivate = $repository->findOneBy(['personId' => $personId]);
    $log = AdminActionLogWriter::deactivateAdmin($em, $actionBy, $adminToDeactivate);

    //Validate input
    if($adminToDeactivate == null) {
        return new JsonResponse(array(Constant::RESULT_NAMESPACE => 'ADMIN NOT FOUND'), 428);
    }

    //deactivate
    $adminToDeactivate->setIsActive(false);
    $em->persist($adminToDeactivate);
    $em->flush();

      $log = ActionLogWriter::completeActionLog($em, $log);

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => 'ok'), 200);
  }


  /**
   *
   * Get ghost accesstoken to 
   *
   * @Route("/ghost")
   * @Method("POST")
   */
  public function getTemporaryGhostToken(Request $request) {

    //User must be an Employee and not a Client
    $employee = $this->getEmployee();
    $employeeValidation = new EmployeeValidator($employee);
    if(!$employeeValidation->getIsValid()) {
      return $employeeValidation->createJsonErrorResponse();
    }

    $content = $this->getContentAsArray($request);
    $personId = $content->get(JsonInputConstant::PERSON_ID);

    /** @var Client $client */
    $client = $this->getDoctrine()->getRepository(Client::class)->findOneBy(['personId' => $personId]);

    $existingGhostToken = $this->getDoctrine()->getRepository(Token::class)->findOneBy(array('owner' => $client, 'admin' => $employee));
    if($existingGhostToken != null) {
      $this->getDoctrine()->getManager()->remove($existingGhostToken);
    }

    $ghostToken = new Token(TokenType::GHOST);
    $ghostToken->setOwner($client);
    $ghostToken->setAdmin($employee);
    $employee->addToken($ghostToken);
    $client->addToken($ghostToken);

    $this->getDoctrine()->getManager()->persist($ghostToken);
    $this->getDoctrine()->getManager()->flush();

    $result = array(Constant::GHOST_TOKEN_NAMESPACE => $ghostToken->getCode());

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $result), 200);
  }

  /**
   *
   * Verify ghost token.
   *
   * @Route("/verify-ghost-token")
   * @Method("PUT")
   */
  public function verifyGhostToken(Request $request) {

    /* This endpoint has no prehook accesstoken & verified ghosttoken validation,
       because this is where the ghosttoken needs to be verified.
       Therefore this endpoint needs its own custom verification:
       1. validate if accessToken is a valid accessToken
       2. validate if accessToken belongs to an Employee and not a Client
    */

    //User must have a valid accessToken
    $tokenValidation = $this->isAccessTokenValid($request);
    if($tokenValidation->getStatusCode() != 200) {
        return $tokenValidation;
    }
    $tokenCode = $request->headers->get(Constant::ACCESS_TOKEN_HEADER_NAMESPACE);

    //User must be an Employee and not a Client
    $employee = $this->getEmployee($tokenCode);
    $employeeValidation = new EmployeeValidator($employee);
    if(!$employeeValidation->getIsValid()) {
      return $employeeValidation->createJsonErrorResponse();
    }

    /* GhostToken verification */
    $ghostTokenCode = null;
    if ($request->headers->has(Constant::GHOST_TOKEN_HEADER_NAMESPACE)) {
      $ghostTokenCode = $request->headers->get(Constant::GHOST_TOKEN_HEADER_NAMESPACE);

      if ($ghostTokenCode != null){
        $ghostToken = $this->getDoctrine()->getRepository(Token::class)->findOneBy(array('code' => $ghostTokenCode));
        if ($ghostToken != null) {

          //First verify if the employee verifying this ghost token is the same employee as the one that created the ghost token
          if($ghostToken->getAdmin() != $employee ) {
            $message = 'UNAUTHORIZED, VERIFYING EMPLOYEE MUST BY IDENTICAL TO THE ONE THAT CREATED THE GHOST TOKEN';
            $code = 401;
          } else {
            
            //Then verify if ghostToken has already been verified or not
            if($ghostToken->getIsVerified()) {
              $message = 'GHOST TOKEN HAS ALREADY BEEN VERIFIED';
              $code = 200;

            } else {
              $now = new \DateTime();
              $timeExpiredInMinutes = ($now->getTimestamp() - $ghostToken->getCreationDateTime()->getTimeStamp())/60;
              $isGhostTokenExpired = $timeExpiredInMinutes > self::timeLimitInMinutes;

              if ($isGhostTokenExpired){
                $this->getDoctrine()->getManager()->remove($ghostToken);
                $message = 'GHOST TOKEN EXPIRED AND WAS DELETED. VERIFY GHOST TOKENS WITHIN 3 MINUTES';
                $code = 428;

              } else { //not expired
                $ghostToken->setIsVerified(true);
                $this->getDoctrine()->getManager()->persist($ghostToken);
                $message = 'GHOST TOKEN IS VERIFIED';
                $code = 200;
              }
              $this->getDoctrine()->getManager()->flush();
            } 
          }

        } else {
          $message = 'NO GHOST TOKEN FOUND FOR GIVEN CODE';
          $code = 428; 
        }
      } else {
        $message = 'GHOST TOKEN FIELD IS EMPTY';
        $code = 428;
      }
    } else {
      $message = 'GHOST TOKEN HEADER MISSING';
      $code = 428;
    }

    $result = array(
        Constant::CODE_NAMESPACE => $code,
        Constant::MESSAGE_NAMESPACE => $message,
        Constant::GHOST_TOKEN_NAMESPACE => $ghostTokenCode);

    return new JsonResponse([Constant::RESULT_NAMESPACE => $result], $code);
  }


  /**
   * Retrieve a list of all Admin access level types
   *
   * @ApiDoc(
   *   section = "Admins",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the admin that is registered with the API"
   *     }
   *   },
   *
   *   resource = true,
   *   description = "Retrieve a list of all Admin access level types"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-access-levels")
   * @Method("GET")
   */
  public function getAccessLevelTypes(Request $request)
  {
    $admin = $this->getEmployee();
    $adminValidator = new AdminValidator($admin);
    if(!$adminValidator->getIsAccessGranted()) { //validate if user is at least an ADMIN
      return $adminValidator->createJsonErrorResponse();
    }

    $result = AccessLevelOverviewOutput::create();

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $result), 200);
  }
}