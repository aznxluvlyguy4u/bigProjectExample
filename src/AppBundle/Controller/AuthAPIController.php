<?php

namespace AppBundle\Controller;

use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use AppBundle\Entity\ClientMigrationData;
use AppBundle\Migration\ClientMigration;
use AppBundle\Entity\CompanyAddress;
use AppBundle\Entity\Employee;
use AppBundle\Entity\LocationAddress;
use AppBundle\Entity\Person;
use AppBundle\Entity\Location;
use AppBundle\Entity\Company;
use AppBundle\Enumerator\MigrationStatus;
use AppBundle\Setting\MigrationSetting;
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
   *   requirements={
   *     {
   *       "name"="Authorization header",
   *       "dataType"="string",
   *       "requirement"="Base64 encoded",
   *       "description"="Basic Authentication header with a Base64 encoded secret, semicolon seperated value, with delimiter"
   *     }
   *   },
   *   resource = true,
   *   description = "Register a user",
   *   input = "AppBundle\Entity\Client",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/register")
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
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Validate whether an accesstoken is valid or not.",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/validate-token")
   * @Method("GET")
   */
  public function validateToken(Request $request)
  {
    return $this->isAccessTokenValid($request);
  }

  /**
   * Retrieve a valid access token.
   *
   * @ApiDoc(
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
   *   description = "Retrieve a valid access token for a registered and activated user",
   *   output = "AppBundle\Entity\Client"
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
      $client = $this->getDoctrine()->getRepository('AppBundle:Client')->findOneBy(array("emailAddress"=>$emailAddress));
      if($client == null) {
        return new JsonResponse(array("errorCode" => 401, "errorMessage"=>"Unauthorized"), 401);
      }

      if($encoder->isPasswordValid($client, $password)) {
        return new JsonResponse(array("access_token"=>$client->getAccessToken()), 200);
      }
    }

    return new JsonResponse(array("errorCode" => 401, "errorMessage"=>"Unauthorized"), 401);

  }

  /**
   * Change password when already logged in.
   *
   * @ApiDoc(
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

    $client = $this->getAuthenticatedUser($request);
    $content = $this->getContentAsArray($request);
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

    $this->getDoctrine()->getEntityManager()->persist($client);
    $this->getDoctrine()->getEntityManager()->flush();

    //Validate password change
    $client = $this->getAuthenticatedUser($request);
    $encodedPasswordInDatabase = $client->getPassword();

    if($encodedPasswordInDatabase == $encodedNewPassword) {

      $emailAddress = $client->getEmailAddress();

      //Confirmation message back to the sender
      $message = \Swift_Message::newInstance()
          ->setSubject('Nieuw wachtwoord voor NSFO dierregistratiesysteem')
          ->setFrom('info@stormdelta.com')
          ->setTo($emailAddress)
          ->setBody(
              $this->renderView(
              // app/Resources/views/...
                  'User/change_password_email.html.twig',
                  array('firstName' => $client->getFirstName(),
                      'lastName' => $client->getLastName(),
                      'relationNumberKeeper' => $client->getRelationNumberKeeper())
              ),
              'text/html'
          )
          ->setSender('info@stormdelta.com')
      ;

      $this->get('mailer')->send($message);

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
        "email_address":"name@email.com"
    }
    */
    $content = $this->getContentAsArray($request);
    $emailAddress = strtolower($content->get('email_address'));

    $client = $this->getClientByEmail($emailAddress);
    //Verify if email is correct
    if($client == null) {
      return new JsonResponse(array("code" => 428, "message"=>"No user found with emailaddress: " . $emailAddress), 428);
    }

    //Create a new password
    $passwordLength = 9;
    $newPassword = Utils::randomString($passwordLength);

    $encoder = $this->get('security.password_encoder');
    $encodedNewPassword = $encoder->encodePassword($client, $newPassword);
    $client->setPassword($encodedNewPassword);

    $this->getDoctrine()->getEntityManager()->persist($client);
    $this->getDoctrine()->getEntityManager()->flush();


    //Confirmation message back to the sender
    $message = \Swift_Message::newInstance()
        ->setSubject('Nieuw wachtwoord voor NSFO dierregistratiesysteem')
        ->setFrom('info@stormdelta.com')
        ->setTo($emailAddress)
        ->setBody(
            $this->renderView(
            // app/Resources/views/...
                'User/reset_password_email.html.twig',
                array('firstName' => $client->getFirstName(),
                    'lastName' => $client->getLastName(),
                    'userName' => $client->getUsername(),
                    'email' => $client->getEmailAddress(),
                    'relationNumberKeeper' => $client->getRelationNumberKeeper(),
                    'password' => $newPassword)
            ),
            'text/html'
        )
        ->setSender('info@stormdelta.com')
    ;

    $this->get('mailer')->send($message);

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
    $employee = $this->getAuthenticatedEmployee($request);
    if($employee == null) {
      return new JsonResponse(array("errorCode" => 403, "errorMessage" => "Forbidden"), 403);
    }

    $doctrine = $this->getDoctrine();
    $encoder = $this->get('security.password_encoder');
    $content = $this->getContentAsArray($request);

    $newClients = $doctrine->getRepository(Constant::CLIENT_REPOSITORY)->getClientsWithoutAPassword();
    
    $migrationResults = ClientMigration::generateNewPasswordsAndEmailsForMigratedClients($newClients, $doctrine, $encoder, $content);

    return new JsonResponse($migrationResults, 200);
  }


  /**
   * Validate whether a ubn in the header is valid or not.
   *
   * @ApiDoc(
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Validate whether a ubn in the header is valid or not.",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/validate-ubn")
   * @Method("GET")
   */
  public function validateUbnInHeader(Request $request)
  {
    $client = $this->getAuthenticatedUser($request);
    $headerValidation = new HeaderValidation($this->getDoctrine()->getManager(), $request, $client);

    if($headerValidation->isInputValid()) {
      return new JsonResponse("UBN IS VALID", 200);
    } else {
      return $headerValidation->createJsonErrorResponse();
    }
  }

}
