<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Controller\AdminAPIControllerInterface;
use AppBundle\Entity\Client;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Token;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\TokenType;
use AppBundle\Output\AccessLevelOverviewOutput;
use AppBundle\Output\AdminOverviewOutput;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use AppBundle\Validation\CreateAdminValidator;
use AppBundle\Validation\EditAdminValidator;
use AppBundle\Validation\EmployeeValidator;
use Symfony\Component\HttpFoundation\Request;

class AdminService extends AuthServiceBase implements AdminAPIControllerInterface
{
    const timeLimitInMinutes = 3;

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getAdmins(Request $request)
    {
        if (!AdminValidator::isAdmin($this->getEmployee(), AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        $admins = $this->getManager()->getRepository(Employee::class)->findBy(array('isActive' => true));
        $result = AdminOverviewOutput::createAdminsOverview($admins);

        return ResultUtil::successResult($result);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createAdmin(Request $request)
    {
        $admin = $this->getEmployee();
        if (!AdminValidator::isAdmin($admin, AccessLevelType::SUPER_ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        $content = RequestUtil::getContentAsArray($request);

        $log = ActionLogWriter::createAdmin($this->getManager(), $admin, $content);

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

        $inputValidator = new CreateAdminValidator($this->getManager(), $content);
        if (!$inputValidator->getIsValid()) {
            return $inputValidator->createJsonResponse();
        }

        // Create new admin
        $newAdmin = new Employee($accessLevel, $firstName, $lastName, $emailAddress);

        // Send Email with passwords to Owner & Users
        $password = AuthService::persistNewPassword($this->encoder, $this->getManager(), $newAdmin);
        $this->emailService->emailNewPasswordToPerson($newAdmin, $password, true, true);

        $this->getManager()->persist($newAdmin);
        $this->getManager()->flush();

        /** @var Employee $admin */
        $admin = $this->getManager()->getRepository(Employee::class)->findOneBy(array(
            'emailAddress' => $newAdmin->getEmailAddress(),
            'isActive' => $newAdmin->getIsActive()
        ));

        $result = AdminOverviewOutput::createAdminOverview($admin);

        $log = ActionLogWriter::completeActionLog($this->getManager(), $log);

        return ResultUtil::successResult($result);
    }


    /**
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function editAdmin(Request $request)
    {
        $admin = $this->getEmployee();
        if (!AdminValidator::isAdmin($admin, AccessLevelType::SUPER_ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        $content = RequestUtil::getContentAsArray($request);
        $log = ActionLogWriter::editAdmin($this->getManager(), $admin, $content);

        // Validate content
        $personId = $content->get('person_id');
        $firstName = $content->get('first_name');
        $lastName = $content->get('last_name');
        $emailAddress = $content->get('email_address');
        $accessLevel = $content->get('access_level');

        $inputValidator = new EditAdminValidator($this->getManager(), $content);
        if (!$inputValidator->getIsValid()) {
            return $inputValidator->createJsonResponse();
        }

        /** @var Employee $admin */
        $admin = $this->getManager()->getRepository(Employee::class)->findOneByPersonId($personId);

        $admin->setFirstName($firstName);
        $admin->setLastName($lastName);
        $admin->setEmailAddress($emailAddress);
        $admin->setAccessLevel($accessLevel);

        $this->getManager()->persist($admin);
        $this->getManager()->flush();

        /** @var Employee $newAdmin */
        $newAdmin = $this->getManager()->getRepository(Employee::class)->findOneBy(array(
            'emailAddress' => $admin->getEmailAddress(),
            'isActive' => $admin->getIsActive()
        ));
        $result = AdminOverviewOutput::createAdminOverview($newAdmin);

        $log = ActionLogWriter::completeActionLog($this->getManager(), $log);

        return ResultUtil::successResult($result);
    }


    /**
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deactivateAdmin(Request $request)
    {
        $admin = $this->getEmployee();
        if (!AdminValidator::isAdmin($admin, AccessLevelType::SUPER_ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        $content = RequestUtil::getContentAsArray($request);

        $personId = $content->get('person_id');
        /** @var Employee $adminToDeactivate */
        $adminToDeactivate = $this->getManager()->getRepository(Employee::class)->findOneBy(['personId' => $personId]);
        $log = ActionLogWriter::deactivateAdmin($this->getManager(), $admin, $adminToDeactivate);

        //Validate input
        if($adminToDeactivate == null) {
            return ResultUtil::errorResult('ADMIN NOT FOUND', 428);
        }

        //deactivate
        $adminToDeactivate->setIsActive(false);
        $this->getManager()->persist($adminToDeactivate);
        $this->getManager()->flush();

        return ResultUtil::successResult('ok');
    }


    /**
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getTemporaryGhostToken(Request $request) {

        //User must be an Employee and not a Client
        $employee = $this->getEmployee();
        if (!AdminValidator::isAdmin($employee, AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        $content = RequestUtil::getContentAsArray($request);
        $personId = $content->get(JsonInputConstant::PERSON_ID);

        /** @var Client $client */
        $client = $this->getManager()->getRepository(Client::class)->findOneBy(['personId' => $personId]);

        $existingGhostToken = $this->getManager()->getRepository(Token::class)->findOneBy(array('owner' => $client, 'admin' => $employee));
        if($existingGhostToken != null) {
            $this->getManager()->remove($existingGhostToken);
        }

        $ghostToken = new Token(TokenType::GHOST);
        $ghostToken->setOwner($client);
        $ghostToken->setAdmin($employee);
        $employee->addToken($ghostToken);
        $client->addToken($ghostToken);

        $this->getManager()->persist($ghostToken);
        $this->getManager()->flush();

        $result = array(Constant::GHOST_TOKEN_NAMESPACE => $ghostToken->getCode());

        return ResultUtil::successResult($result);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyGhostToken(Request $request)
    {

        /* This endpoint has no prehook accesstoken & verified ghosttoken validation,
           because this is where the ghosttoken needs to be verified.
           Therefore this endpoint needs its own custom verification:
           1. validate if accessToken is a valid accessToken
           2. validate if accessToken belongs to an Employee and not a Client
        */

        //User must have a valid accessToken
        $tokenValidation = parent::isAccessTokenValid($request);
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
                $ghostToken = $this->getManager()->getRepository(Token::class)->findOneBy(array('code' => $ghostTokenCode));
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
                                $this->getManager()->remove($ghostToken);
                                $message = 'GHOST TOKEN EXPIRED AND WAS DELETED. VERIFY GHOST TOKENS WITHIN 3 MINUTES';
                                $code = 428;

                            } else { //not expired
                                $ghostToken->setIsVerified(true);
                                $this->getManager()->persist($ghostToken);
                                $message = 'GHOST TOKEN IS VERIFIED';
                                $code = 200;
                            }
                            $this->getManager()->flush();
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
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getAccessLevelTypes(Request $request)
    {
        $admin = $this->getEmployee();
        if (!AdminValidator::isAdmin($admin, AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        $result = AccessLevelOverviewOutput::create();

        return ResultUtil::successResult($result);
    }

}