<?php

namespace AppBundle\Controller;

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
use AppBundle\Validation\AdminValidator;
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
class AdminAPIController extends APIController {

  const timeLimitInMinutes = 3;

  /**
   * Retrieve a list of all Admins
   *
   * @ApiDoc(
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
   *   description = "Retrieve a list of all Admins",
   *   output = "AppBundle\Entity\Employee"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("GET")
   */
  public function getAdmins(Request $request)
  {
    $admin = $this->getAuthenticatedEmployee($request);
    $adminValidator = new AdminValidator($admin, AccessLevelType::SUPER_ADMIN);
    if(!$adminValidator->getIsAccessGranted()) { //validate if user is at least a SUPER_ADMIN
      return $adminValidator->createJsonErrorResponse();
    }
    
    $repository = $this->getDoctrine()->getRepository(Employee::class);

    $admins = $repository->findAll();
    $result = AdminOverviewOutput::createAdminsOverview($admins);

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $result), 200);
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
    $employee = $this->getAuthenticatedEmployee($request);
    $employeeValidation = new EmployeeValidator($employee);
    if(!$employeeValidation->getIsValid()) {
      return $employeeValidation->createJsonErrorResponse();
    }

    $content = $this->getContentAsArray($request);
    $companyId = $content->get(JsonInputConstant::COMPANY_ID);

    $company = $this->getDoctrine()->getRepository(Company::class)->find($companyId);
    $client = $company->getOwner();

    $existingGhostToken = $this->getDoctrine()->getRepository(Token::class)->findOneBy(array('owner' => $client, 'admin' => $employee));
    if($existingGhostToken != null) {
      $this->getDoctrine()->getEntityManager()->remove($existingGhostToken);
    }

    $ghostToken = new Token(TokenType::GHOST);
    $ghostToken->setOwner($client);
    $ghostToken->setAdmin($employee);
    $employee->addToken($ghostToken);
    $client->addToken($ghostToken);

    $this->getDoctrine()->getEntityManager()->persist($client);
    $this->getDoctrine()->getEntityManager()->flush();

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

    //User must be an Employee and not a Client
    $employee = $this->getAuthenticatedEmployee($request);
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
                $this->getDoctrine()->getEntityManager()->remove($ghostToken);
                $message = 'GHOST TOKEN EXPIRED AND WAS DELETED. VERIFY GHOST TOKENS WITHIN 3 MINUTES';
                $code = 428;

              } else { //not expired
                $ghostToken->setIsVerified(true);
                $this->getDoctrine()->getEntityManager()->persist($ghostToken);
                $message = 'GHOST TOKEN IS VERIFIED';
                $code = 200;
              }
              $this->getDoctrine()->getEntityManager()->flush();
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
   * Migrate accessTokens from string field to array field.
   *
   * @Route("/migrate")
   * @Method("POST")
   */
  public function migrateTokensToArray(Request $request) {

    //User must be an Employee and not a Client
    $employee = $this->getAuthenticatedEmployee($request);
    $employeeValidation = new EmployeeValidator($employee);
    if(!$employeeValidation->getIsValid()) {
      return $employeeValidation->createJsonErrorResponse();
    }

    $persons = $this->getDoctrine()->getRepository(Person::class)->findAll();

    foreach ($persons as $person) {
      if(sizeof($person->getTokens()) == 0) {
        $token = new Token(TokenType::ACCESS, $person->getAccessToken());
        $person->addToken($token);
        $token->setOwner($person);
        $this->getDoctrine()->getEntityManager()->persist($person);
        $this->getDoctrine()->getEntityManager()->flush();
      }
    }
    
    return new JsonResponse("ok", 200);
  }


  /**
   * Retrieve a list of all Admin access level types
   *
   * @ApiDoc(
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
   *   description = "Retrieve a list of all Admin access level types",
   *   output = "AppBundle\Entity\Employee"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-access-levels")
   * @Method("GET")
   */
  public function getAccessLevelTypes(Request $request)
  {
    $admin = $this->getAuthenticatedEmployee($request);
    $adminValidator = new AdminValidator($admin);
    if(!$adminValidator->getIsAccessGranted()) { //validate if user is at least an ADMIN
      return $adminValidator->createJsonErrorResponse();
    }

    $result = AccessLevelOverviewOutput::create();

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $result), 200);
  }
}