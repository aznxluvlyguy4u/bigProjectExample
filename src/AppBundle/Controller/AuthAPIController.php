<?php

namespace AppBundle\Controller;

use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\Client;
use AppBundle\Entity\ClientRepository;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Migration\ClientMigration;
use AppBundle\Entity\CompanyAddress;
use AppBundle\Entity\Employee;
use AppBundle\Entity\LocationAddress;
use AppBundle\Entity\Person;
use AppBundle\Entity\Location;
use AppBundle\Entity\Company;
use AppBundle\Enumerator\MigrationStatus;
use AppBundle\Output\MenuBarOutput;
use AppBundle\Setting\MigrationSetting;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Validation\AdminValidator;
use AppBundle\Validation\HeaderValidation;
use AppBundle\Validation\PasswordValidator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Doctrine\Common\Collections\ArrayCollection;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/api/v1/auth")
 */
class AuthAPIController extends APIController {

  /**
   * Register a user
   *
   * @ApiDoc(
   *   section = "Auth",
   *   requirements={
   *     {
   *       "name"="Authorization header",
   *       "dataType"="string",
   *       "requirement"="Base64 encoded",
   *       "description"="Basic Authentication header with a Base64 encoded secret, semicolon seperated value, with delimiter"
   *     }
   *   },
   *   resource = true,
   *   description = "Register a user"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/signup")
   * @Method("POST")
   */
  public function registerUser(Request $request)
  {
    return new JsonResponse(array("code" => 403, "message" => "no online registration available at the moment"), 403);

    //TODO There is no registration page at the moment, so the route below is blocked
    $credentials = $request->headers->get(Constant::AUTHORIZATION_HEADER_NAMESPACE);
    $credentials = str_replace('Basic ', '', $credentials);
    $credentials = base64_decode($credentials);

    list($username, $password) = explode(":", $credentials);

    $encoder = $this->get('security.password_encoder');

    /*
    {
        "first_name":"Jane",
        "last_name":"Doe",
        "ubn":"123",
        "email_address": "",
        "postal_code":"1234AB",
        "home_number":"12"
    }
    */

    //Get content to array
    $content = $this->getContentAsArray($request);

    $firstName = $content['first_name'];
    $lastName = $content['lastName'];
    $ubn = $content['ubn'];
    $emailAddress = $content['email_address'];
    $postalCode = $content['postal_code'];
    $homeNumber = $content['home_number'];

    $client = new Client();
    $client->setFirstName($firstName);
    $client->setLastName($lastName);
    $client->setRelationNumberKeeper(uniqid(mt_rand(0,9999999)));
    $client->setEmailAddress($emailAddress);
    $client->setUsername($username);

    $encodedPassword = $encoder->encodePassword($client, $password);
    $client->setPassword($encodedPassword);

    $client = $this->getDoctrine()->getRepository('AppBundle:Client')->persist($client);

    return new JsonResponse(array("access_token" => $client->getAccessToken()), 200);
  }

  /**
   * Validate whether an accesstoken is valid or not.
   *
   * @ApiDoc(
   *   section = "Auth",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Validate whether an accesstoken is valid or not."
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/validate-token")
   * @Method("POST")
   */
  public function validateToken(Request $request)
  {
    return $this->isAccessTokenValid($request);
  }

  /**
   * Retrieve a valid access token.
   *
   * @ApiDoc(
   *   section = "Auth",
   *   requirements={
   *     {
   *       "name"="Authorization header",
   *       "dataType"="string",
   *       "requirement"="Base64 encoded",
   *       "format"="Authorization: Basic xxxxxxx==",
   *       "description"="Basic Authentication, Base64 encoded string with delimiter"
   *     }
   *   },
   *   resource = true,
   *   description = "Retrieve a valid access token for a registered and activated user"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/authorize")
   * @Method("GET")
   */
  public function authorizeUser(Request $request)
  {
    $credentials = $request->headers->get(Constant::AUTHORIZATION_HEADER_NAMESPACE);
    $credentials = str_replace('Basic ', '', $credentials);
    $credentials = base64_decode($credentials);

    list($emailAddress, $password) = explode(":", $credentials);
    if($emailAddress != null && $password != null) {
      $encoder = $this->get('security.password_encoder');
      $emailAddress = strtolower($emailAddress);
      $client = $this->getActiveClientByEmail($emailAddress);
      if($client == null) {
        return new JsonResponse(array("errorCode" => 401, "errorMessage"=>"Unauthorized"), 401);
      }

      if(!$client->getIsActive()) {
        return new JsonResponse(array("errorCode" => 401, "errorMessage"=>"Unauthorized"), 401);
      }

      if($client->getEmployer() != null) {
          if(!$client->getEmployer()->isActive()) {
            return new JsonResponse(array("errorCode" => 401, "errorMessage"=>"Unauthorized"), 401);
          }
      }

      if($client->getCompanies()->count() > 0) {
          $companies = $client->getCompanies();
          foreach ($companies as $company) {
              if(!$company->isActive()){
                  return new JsonResponse(array("errorCode" => 401, "errorMessage"=>"Unauthorized"), 401);
              }
          }
      }

        if($encoder->isPasswordValid($client, $password)) {
        /** @var Client $client */
        $result = [
            "access_token"=>$client->getAccessToken(),
            "user" => MenuBarOutput::create($client)
        ];

        return new JsonResponse(array("access_token"=>$client->getAccessToken(),
                         Constant::RESULT_NAMESPACE => $result), 200);

        //TODO If Frontend is updated use the following JsonResponse
        //return new JsonResponse(array(Constant::RESULT_NAMESPACE => $result), 200);
      }
    }

    return new JsonResponse(array("errorCode" => 401, "errorMessage"=>"Unauthorized"), 401);

  }

  /**
   * Change password when already logged in.
   *
   * @ApiDoc(
   *   section = "Auth",
   *   requirements={
   *     {
   *       "name"="Authorization header",
   *       "dataType"="string",
   *       "requirement"="Base64 encoded",
   *       "description"="Basic Authentication header with a Base64 encoded secret, semicolon separated value, with delimiter"
   *     }
   *   },
   *   resource = true,
   *   description = "Reset login password"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/password-change")
   * @Method("PUT")
   */
  public function changePassword(Request $request)
  {
    /*
    {
        "new_password":"Tm90TXlGaXJzdFBhc3N3b3JkMQ==" //base64 encoded 'NotMyFirstPassword1'
    }
    */
    $encoder = $this->get('security.password_encoder');

    $om = $this->getDoctrine()->getManager();
    $client = $this->getAccountOwner($request);
    $loggedInUser = $this->getUser();
    $content = $this->getContentAsArray($request);
    $log = ActionLogWriter::passwordChange($om, $client, $loggedInUser);

    $enteredOldPassword = base64_decode($content->get('current_password'));

    if(!$encoder->isPasswordValid($client, $enteredOldPassword)) {
      return new JsonResponse(array(Constant::MESSAGE_NAMESPACE => "CURRENT PASSWORD NOT VALID", Constant::CODE_NAMESPACE => 401), 401);
    }

    $newPassword = base64_decode($content->get('new_password'));

    //Validate password format
    $passwordValidator = new PasswordValidator($newPassword);
    if(!$passwordValidator->getIsPasswordValid()) {
        return $passwordValidator->createJsonErrorResponse();
    }

    $encodedOldPassword = $client->getPassword();
    $encodedNewPassword = $encoder->encodePassword($client, $newPassword);
    $client->setPassword($encodedNewPassword);

    $this->getDoctrine()->getManager()->persist($client);
    $this->getDoctrine()->getManager()->flush();

    //Validate password change
    $client = $this->getAccountOwner($request);
    $encodedPasswordInDatabase = $client->getPassword();

    if($encodedPasswordInDatabase == $encodedNewPassword) {

      $emailAddress = $client->getEmailAddress();

      $mailerSourceAddress = $this->getParameter('mailer_source_address');

      //Confirmation message back to the sender
      $message = \Swift_Message::newInstance()
          ->setSubject(Constant::NEW_PASSWORD_MAIL_SUBJECT_HEADER)
          ->setFrom($mailerSourceAddress)
          ->setTo($emailAddress)
          ->setBody(
              $this->renderView(
                // app/Resources/views/...
                'User/change_password_email.html.twig'
              ),
              'text/html'
          )
          ->setSender($mailerSourceAddress);

      $this->get('mailer')->send($message);

      $log = ActionLogWriter::completeActionLog($om, $log);

      return new JsonResponse(array("code" => 200, "message"=>"Password has been changed"), 200);

    } else if($encodedPasswordInDatabase == $encodedOldPassword) {
      return new JsonResponse(array("code" => 428, "message"=>"Password has not been changed"), 428);

    } else if($encodedPasswordInDatabase == null) {
      return new JsonResponse(array("code" => 500, "message"=>"Password in database is null"), 500);
    }

    return new JsonResponse(array("code" => 401, "message"=>"Password in database doesn't match new or old password"), 401);

  }

  /**
   * Reset password when not logged in.
   *
   * @ApiDoc(
   *   section = "Auth",
   *   requirements={
   *     {
   *       "name"="Authorization header",
   *       "dataType"="string",
   *       "requirement"="Base64 encoded",
   *       "description"="Basic Authentication header with a Base64 encoded secret, semicolon separated value, with delimiter"
   *     }
   *   },
   *   resource = true,
   *   description = "Reset login password"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/password-reset")
   * @Method("PUT")
   */
  public function resetPassword(Request $request)
  {
    /*
    {
        "email_address":"example@example.com"
    }
    */
    $om = $this->getDoctrine()->getManager();
    $content = $this->getContentAsArray($request);
    $emailAddress = strtolower($content->get('email_address'));
    $client = $this->getActiveClientByEmail($emailAddress);
    $log = ActionLogWriter::passwordReset($om, $client, $emailAddress);

    //Verify if email is correct
    if($client == null) {
      return new JsonResponse(array("code" => 428, "message"=>"No user found with emailaddress: " . $emailAddress), 428);
    }

    //Create a new password
    $passwordLength = 9;
    $newPassword = $this->persistNewPassword($client);
    $this->emailNewPasswordToPerson($client, $newPassword);

    $log = ActionLogWriter::completeActionLog($om, $log);

    return new JsonResponse(array("code" => 200,
        "message"=>"Your new password has been emailed to: " . $emailAddress), 200);
  }

  /**
   * Generate new passwords for new clients and store them.
   *
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/generate-passwords")
   * @Method("POST")
   *
   * @param Request $request
   */
  public function generatePasswordsForNewClients(Request $request)
  {
    return new JsonResponse(array("code" => 403, "message" => "Forbidden"), 403);
    
    $migrationResults = $this->getClientMigratorService()->generateNewPasswordsAndEmailsForMigratedClients($this->getContentAsArray($request));

    return new JsonResponse($migrationResults, 200);
  }


  /**
   * Validate whether a ubn in the header is valid or not.
   *
   * @ApiDoc(
   *   section = "Auth",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Validate whether a ubn in the header is valid or not."
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/validate-ubn")
   * @Method("GET")
   */
  public function validateUbnInHeader(Request $request)
  {
    $client = $this->getAccountOwner($request);
    $headerValidation = new HeaderValidation($this->getDoctrine()->getManager(), $request, $client);

    if($headerValidation->isInputValid()) {
      return new JsonResponse("UBN IS VALID", 200);
    } else {
      return $headerValidation->createJsonErrorResponse();
    }
  }


  public function generatePersonIds(Request $request)
  {
    $admin = $this->getEmployee();
    $adminValidator = new AdminValidator($admin, AccessLevelType::SUPER_ADMIN);
    if (!$adminValidator->getIsAccessGranted()) { //validate if user is at least a SUPER_ADMIN
      return $adminValidator->createJsonErrorResponse();
    }


    $personsWithoutPersonId = $this->getDoctrine()->getRepository(Person::class)->findOneBy(['personId' => null]);

    $i = 0;
    $flushBatchSize = 50;

    foreach ($personsWithoutPersonId as $person) {
      $person->setPersonId(Utils::generatePersonId());
      $this->getDoctrine()->getManager()->persist($person);

      $i++;
      if($i%$flushBatchSize == 0) { //flush per batch
        $this->getDoctrine()->getManager()->flush();
      }
    }
    $this->getDoctrine()->getManager()->flush();

    return new JsonResponse(array("code" => 200,
        "message"=>"Person IDs have been generated for all persons that did not have one yet"), 200);
  }
}
