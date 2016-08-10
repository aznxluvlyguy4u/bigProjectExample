<?php

namespace AppBundle\Controller;

use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\Client;
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
use AppBundle\Setting\MigrationSetting;
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
 * @Route("/api/v1/admins/auth")
 */
class AdminAuthAPIController extends APIController {

  /**
   * Retrieve a valid access token.
   *
   *   {
   *     "name"="Authorization header",
   *     "dataType"="string",
   *     "requirement"="Base64 encoded",
   *     "format"="Authorization: Basic xxxxxxx==",
   *     "description"="Basic Authentication, Base64 encoded string with delimiter"
   *   }

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
      $admin = $this->getDoctrine()->getRepository(Employee::class)->findOneBy(array("emailAddress"=>$emailAddress));
      if($admin == null) {
        return new JsonResponse(array("errorCode" => 401, "errorMessage"=>"Unauthorized"), 401);
      }

      if($encoder->isPasswordValid($admin, $password)) {
        return new JsonResponse(array("access_token"=>$admin->getAccessToken()), 200);
      }
    }

    return new JsonResponse(array("errorCode" => 401, "errorMessage"=>"Unauthorized"), 401);

  }


  /**
   * Reset password when not logged in.
   *
   * {
   *    "name"="Authorization header",
   *    "dataType"="string",
   *    "requirement"="Base64 encoded",
   *    "description"="Basic Authentication header with a Base64 encoded secret, semicolon separated value, with delimiter"
   * }
   *
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
    $content = $this->getContentAsArray($request);
    $em = $this->getDoctrine()->getManager();
    $emailAddress = strtolower($content->get('email_address'));

    /** @var Employee $admin */
    $admin = $em->getRepository(Employee::class)->findOneByEmailAddress($emailAddress);

    //Verify if email is correct
    if($admin == null) {
      return new JsonResponse(array("code" => 428, "message"=>"No user found with emailaddress: " . $emailAddress), 428);
    }

    //Create a new password
    $passwordLength = 9;
    $newPassword = $this->persistNewPassword($admin, $passwordLength);
    $this->emailNewPasswordToPerson($admin, $newPassword);

    return new JsonResponse(array("code" => 200,
        "message"=>"Your new password has been emailed to: " . $emailAddress), 200);
  }

}
