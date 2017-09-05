<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Employee;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AdminAuthService extends AuthServiceBase
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function authorizeUser(Request $request)
    {
        $credentials = $request->headers->get(Constant::AUTHORIZATION_HEADER_NAMESPACE);
        $credentials = str_replace('Basic ', '', $credentials);
        $credentials = base64_decode($credentials);

        list($emailAddress, $password) = explode(":", $credentials);
        if($emailAddress != null && $password != null) {
            $emailAddress = strtolower($emailAddress);

            /** @var Employee $admin */
            $admin = $this->getManager()->getRepository(Employee::class)->findActiveOneByEmailAddress($emailAddress);
            if($admin == null) {
                return new JsonResponse(array("errorCode" => 401, "errorMessage"=>"Unauthorized"), 401);
            }

            if($this->encoder->isPasswordValid($admin, $password)) {
                $result = [
                    "access_token"=>$admin->getAccessToken(),
                    "user" =>[
                        "first_name" => $admin->getFirstName(),
                        "last_name" => $admin->getLastName(),
                        "access_level" => $admin->getAccessLevel()
                    ]
                ];

                return ResultUtil::successResult($result);
            }
        }

        return new JsonResponse(array("errorCode" => 401, "errorMessage"=>"Unauthorized"), 401);

    }


    /**
     * TODO switch to the new password reset request and confirmation endpoint in AuthService
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPassword(Request $request)
    {
        /*
        {
            "email_address":"example@example.com"
        }
        */
        $content = RequestUtil::getContentAsArray($request);
        $emailAddress = strtolower($content->get('email_address'));

        $admin = $this->getManager()->getRepository(Employee::class)->findActiveOneByEmailAddress($emailAddress);
        $log = ActionLogWriter::adminPasswordReset($this->getManager(), $admin, $emailAddress);

        //Verify if email is correct
        if($admin == null) {
            return new JsonResponse(array("code" => 428, "message"=>"No user found with emailaddress: " . $emailAddress), 428);
        }

        //Create a new password
        $passwordLength = 9;
        $newPassword = AuthService::persistNewPassword($this->encoder, $this->getManager(), $admin, $passwordLength);
        $emailSuccessfullySent = $this->emailService->emailNewPasswordToPerson($admin, $newPassword);

        if ($emailSuccessfullySent) {
            $log = ActionLogWriter::completeActionLog($this->getManager(), $log);

            return new JsonResponse(array("code" => 200,
                "message"=>"Your new password has been emailed to: " . $emailAddress), 200);
        }

        return ResultUtil::errorResult('Error sending email', 500);
    }
}