<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Employee;
use AppBundle\Util\ResultUtil;
use Symfony\Component\HttpFoundation\Request;

class AdminAuthService extends AuthServiceBase
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function authorizeUser(Request $request)
    {
        $credentials = $request->headers->get(Constant::AUTHORIZATION_HEADER_NAMESPACE);
        $inputValues = AuthService::getCredentialsFromBasicAuthHeader($credentials);
        $emailAddress = $inputValues[JsonInputConstant::EMAIL_ADDRESS];
        $password = $inputValues[JsonInputConstant::PASSWORD];

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

                $admin->setLastLoginDate(new \DateTime());
                $this->getManager()->persist($admin);
                $this->getManager()->flush();

                return ResultUtil::successResult($result);
            }
        }

        return new JsonResponse(array("errorCode" => 401, "errorMessage"=>"Unauthorized"), 401);

    }


}