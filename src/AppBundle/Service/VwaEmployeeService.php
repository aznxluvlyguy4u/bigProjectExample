<?php

namespace AppBundle\Service;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Controller\VwaEmployeeAPIControllerInterface;
use AppBundle\Entity\VwaEmployee;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Output\MenuBarOutput;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\Validator;
use AppBundle\Validation\AdminValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class VwaEmployeeService
 * @package AppBundle\Service
 */
class VwaEmployeeService extends AuthServiceBase implements VwaEmployeeAPIControllerInterface
{
    const VWA_PASSWORD_LENGTH = 9;
    const MIN_VWA_PASSWORD_LENGTH = 6;

    //ErrorMessages that should be prevented by frontend validation
    const ERROR_EMAIL_ADDRESS_EDIT_EMPTY = "If 'email_address' key exists, its value cannot be empty";
    const ERROR_EMAIL_ADDRESS_EMPTY = "'email address' cannot be empty.";
    const ERROR_EMAIL_ADDRESS_INVALID = 'Het opgegeven e-mailadres heeft geen geldig format';
    const ERROR_FIRST_NAME_EDIT_EMPTY = "If 'first_name' key exists, its value cannot be empty";
    const ERROR_FIRST_NAME_EMPTY = "'first_name' cannot be empty.";
    const ERROR_LAST_NAME_EDIT_EMPTY = "If 'last_name' key exists, its value cannot be empty";
    const ERROR_LAST_NAME_EMPTY = "'last_name' cannot be empty.";
    const ERROR_VWA_EMPLOYEE_MISSING = 'No VWA Employee found for given id: ';
    const ERROR_VWA_EMPLOYEE_DEACTIVATED = 'VWA Employee has been deactivated';

    //ErrorMessages the user is able to see
    const ERROR_VWA_EMPLOYEE_ALREADY_EXISTS = 'VWA Medewerker bestaat al';


    /**
     * @param Request $request
     * @return JsonResponse
     */
    function getAll(Request $request)
    {
        if(!AdminValidator::isAdmin($this->getUser(), AccessLevelType::SUPER_ADMIN))
        { return AdminValidator::getStandardErrorResponse(); }

        $vwaEmployees = $this->getManager()->getRepository(VwaEmployee::class)->findAll();
        $output = $this->getBaseSerializer()->getDecodedJson($vwaEmployees, [JmsGroup::VWA]);
        return ResultUtil::successResult($output);
    }


    /**
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    function getById(Request $request, $id)
    {
        if (strtolower($id) === 'me' && $this->getUser() instanceof VwaEmployee) {
            $vwaEmployee = $this->getUser();

        } else {
            if(!AdminValidator::isAdmin($this->getUser(), AccessLevelType::SUPER_ADMIN))
            { return AdminValidator::getStandardErrorResponse(); }

            $vwaEmployee = $this->getManager()->getRepository(VwaEmployee::class)->findOneBy(['personId' => $id]);
        }

        if ($vwaEmployee === null) {
            return ResultUtil::errorResult(self::ERROR_VWA_EMPLOYEE_MISSING . $id, Response::HTTP_BAD_REQUEST);
        }

        $output = $this->getBaseSerializer()->getDecodedJson($vwaEmployee, [JmsGroup::VWA, JmsGroup::DETAILS]);
        return ResultUtil::successResult($output);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    function create(Request $request)
    {
        $admin = $this->getEmployee();
        if(!AdminValidator::isAdmin($admin, AccessLevelType::SUPER_ADMIN))
        { return AdminValidator::getStandardErrorResponse(); }

        $content = RequestUtil::getContentAsArray($request);

        $firstName = $content->get(JsonInputConstant::FIRST_NAME);
        $lastName = $content->get(JsonInputConstant::LAST_NAME);
        $emailAddress = trim(strtolower($content->get(JsonInputConstant::EMAIL_ADDRESS)));

        //Validate
        $errorMessage = '';
        if ($firstName === null || $firstName === '') { $errorMessage .= self::ERROR_FIRST_NAME_EMPTY; }
        if ($lastName === null || $lastName === '') { $errorMessage .= self::ERROR_LAST_NAME_EMPTY; }
        if ($emailAddress === null || $emailAddress === '') { $errorMessage .= self::ERROR_EMAIL_ADDRESS_EMPTY;
        } elseif (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) { $errorMessage .= self::ERROR_EMAIL_ADDRESS_INVALID; }

        if ($errorMessage !== '') {
            return ResultUtil::errorResult($errorMessage, Response::HTTP_BAD_REQUEST);
        }

        $vwaEmployee = $this->getManager()->getRepository(VwaEmployee::class)->findOneBy(['emailAddress' => $emailAddress]);

        $isReactivation = false;
        if ($vwaEmployee) {

            if ($vwaEmployee->getIsActive()) {
                return ResultUtil::errorResult(self::ERROR_VWA_EMPLOYEE_ALREADY_EXISTS, Response::HTTP_BAD_REQUEST);
            }

            $vwaEmployee->reactivate();
            $isReactivation = true;
        } else {
            $vwaEmployee = new VwaEmployee();
        }

        //Create new vwaEmployee
        $vwaEmployee->setFirstName($firstName);
        $vwaEmployee->setLastName($lastName);
        $vwaEmployee->setEmailAddress($emailAddress);
        $vwaEmployee->setCreatedBy($admin);

        $newPassword = AuthService::persistNewPassword($this->encoder, $this->getManager(),
            $vwaEmployee, self::VWA_PASSWORD_LENGTH);

        $emailData = [
            JsonInputConstant::EMAIL_ADDRESS => $emailAddress,
            JsonInputConstant::NEW_PASSWORD => $newPassword,
            JsonInputConstant::FIRST_NAME => $firstName,
            JsonInputConstant::LAST_NAME => $lastName,
        ];

        $wasSentSuccessfully = $this->emailService->sendVwaInvitationEmail($emailData);

        if ($wasSentSuccessfully) {
            $vwaEmployee->setInvitationDate(new \DateTime());
            $vwaEmployee->setInvitedBy($admin);
            $this->getManager()->persist($vwaEmployee);
            $this->getManager()->flush();
        }

        $output = $this->getBaseSerializer()->getDecodedJson($vwaEmployee, [JmsGroup::VWA, JmsGroup::DETAILS]);

        ActionLogWriter::createVwaEmployee($this->getManager(), $admin, $vwaEmployee, $isReactivation);

        return ResultUtil::successResult($output);
    }


    /**
     * @param string $id
     * @return JsonResponse|VwaEmployee
     */
    private function findByIdAndUser($id)
    {
        if (strtolower($id) === 'me') {
            if ($this->getUser() instanceof VwaEmployee) {
                $vwaEmployee = $this->getUser();
            } else {
                return ResultUtil::unauthorized();
            }

        } else {
            if(!AdminValidator::isAdmin($this->getUser(), AccessLevelType::SUPER_ADMIN))
            { return ResultUtil::unauthorized(); }

            $vwaEmployee = $this->getManager()->getRepository(VwaEmployee::class)->findOneBy(['personId' => $id]);
        }

        if ($vwaEmployee === null) {
            return ResultUtil::errorResult(self::ERROR_VWA_EMPLOYEE_MISSING . $id, Response::HTTP_BAD_REQUEST);
        }
        return $vwaEmployee;
    }


    /**
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    function edit(Request $request, $id)
    {
        $vwaEmployee = $this->findByIdAndUser($id);
        if ($vwaEmployee instanceof JsonResponse) { return $vwaEmployee; }

        if (!$vwaEmployee->getIsActive()) {
            return ResultUtil::errorResult(self::ERROR_VWA_EMPLOYEE_DEACTIVATED, Response::HTTP_BAD_REQUEST);
        }

        $content = RequestUtil::getContentAsArray($request);

        $anyValuesUpdated = false;
        $this->clearActionLogEditMessage();

        if ($content->containsKey(JsonInputConstant::FIRST_NAME))
        {
            $newFirstName = $content->get(JsonInputConstant::FIRST_NAME);
            if ($newFirstName !== $vwaEmployee->getFirstName()) {

                if ($newFirstName === null || $newFirstName === '') {
                    return ResultUtil::errorResult(self::ERROR_FIRST_NAME_EDIT_EMPTY, Response::HTTP_BAD_REQUEST);
                }

                $vwaEmployee->setFirstName($newFirstName);
                $anyValuesUpdated = true;
                $this->updateActionLogEditMessage('first_name', $vwaEmployee->getFirstName(), $newFirstName);
            }
        }


        if ($content->containsKey(JsonInputConstant::LAST_NAME))
        {
            $newLastName = $content->get(JsonInputConstant::LAST_NAME);
            if ($newLastName !== $vwaEmployee->getLastName()) {

                if ($newLastName === null || $newLastName === '') {
                    return ResultUtil::errorResult(self::ERROR_LAST_NAME_EDIT_EMPTY, Response::HTTP_BAD_REQUEST);
                }

                $vwaEmployee->setLastName($newLastName);
                $anyValuesUpdated = true;
                $this->updateActionLogEditMessage('last_name', $vwaEmployee->getLastName(), $newLastName);
            }
        }


        if ($content->containsKey(JsonInputConstant::EMAIL_ADDRESS))
        {
            $newEmailAddress = trim(strtolower($content->get(JsonInputConstant::EMAIL_ADDRESS)));
            if ($newEmailAddress !== $vwaEmployee->getEmailAddress()) {

                if (!filter_var($newEmailAddress, FILTER_VALIDATE_EMAIL)) {
                    return ResultUtil::errorResult(self::ERROR_EMAIL_ADDRESS_INVALID, Response::HTTP_BAD_REQUEST);

                } elseif ($newEmailAddress === null || $newEmailAddress === '') {
                    return ResultUtil::errorResult(self::ERROR_EMAIL_ADDRESS_EDIT_EMPTY, Response::HTTP_BAD_REQUEST);
                }

                $vwaEmployee->setEmailAddress($newEmailAddress);
                $anyValuesUpdated = true;
                $this->updateActionLogEditMessage('email_address', $vwaEmployee->getEmailAddress(), $newEmailAddress);
            }
        }


        if ($content->containsKey(JsonInputConstant::PASSWORD))
        {
            //Only allow password edit for own account. Admin is not allowed to edit the password.
            if ($this->getUser() instanceof VwaEmployee) {
                $newPassword = $content->get(JsonInputConstant::PASSWORD);
                $validationResult = Validator::validatePasswordFormat($newPassword, self::MIN_VWA_PASSWORD_LENGTH);
                if ($validationResult instanceof JsonResponse) {
                    return $validationResult;
                }

                $encodedNewPassword = $this->encoder->encodePassword($vwaEmployee, $newPassword);
                $vwaEmployee->setPassword($encodedNewPassword);
                $anyValuesUpdated = true;
                $this->updateActionLogEditMessage('password', '****', '****');

            } else {
                return ResultUtil::errorResult('VWA passwords may only be edited by the users themselves', Response::HTTP_UNAUTHORIZED);
            }
        }


        if ($anyValuesUpdated) {
            $vwaEmployee->setEditedBy($this->getUser());
            $this->getManager()->persist($vwaEmployee);
            $this->getManager()->flush();
            ActionLogWriter::editVwaEmployee($this->getManager(), $this->getUser(), $vwaEmployee,$this->getActionLogEditMessage());
        }

        $output = $this->getBaseSerializer()->getDecodedJson($vwaEmployee, [JmsGroup::VWA, JmsGroup::DETAILS]);
        return ResultUtil::successResult($output);
    }

    /**
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    function deactivate(Request $request, $id)
    {
        $vwaEmployee = $this->findByIdAndUser($id);
        if ($vwaEmployee instanceof JsonResponse) { return $vwaEmployee; }

        if (!$vwaEmployee->getIsActive()) {
            return ResultUtil::errorResult(self::ERROR_VWA_EMPLOYEE_DEACTIVATED, Response::HTTP_BAD_REQUEST);
        }

        $vwaEmployee->setIsActive(false);
        $vwaEmployee->setDeleteDate(new \DateTime());
        $vwaEmployee->setDeletedBy($this->getUser());

        $this->getManager()->persist($vwaEmployee);
        $this->getManager()->flush();

        ActionLogWriter::deleteVwaEmployee($this->getManager(), $this->getUser(), $vwaEmployee);

        $output = $this->getBaseSerializer()->getDecodedJson($vwaEmployee, [JmsGroup::VWA, JmsGroup::DETAILS]);
        return ResultUtil::successResult($output);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    function authorize(Request $request)
    {
        $credentials = $request->headers->get(Constant::AUTHORIZATION_HEADER_NAMESPACE);
        $credentials = str_replace('Basic ', '', $credentials);
        $credentials = base64_decode($credentials);

        list($emailAddress, $password) = explode(":", $credentials);

        if($emailAddress != null && $password != null) {
            $emailAddress = trim(strtolower($emailAddress));
            /** @var VwaEmployee $vwaEmployee */
            $vwaEmployee = $this->getManager()->getRepository(VwaEmployee::class)
                ->findOneBy(['isActive' => true, 'emailAddress' => $emailAddress]);

            if($vwaEmployee === null) {
                return ResultUtil::unauthorized();
            }

            if($this->encoder->isPasswordValid($vwaEmployee, $password)) {
                $result = [
                    "access_token"=>$vwaEmployee->getAccessToken(),
                    "user" => MenuBarOutput::createVwaEmployee($vwaEmployee),
                ];

                $vwaEmployee->setLastLoginDate(new \DateTime());
                $this->getManager()->persist($vwaEmployee);
                ActionLogWriter::loginVwaEmployee($this->getManager(), $vwaEmployee, true);

                return ResultUtil::successResult($result);
            }
            ActionLogWriter::loginVwaEmployee($this->getManager(), $vwaEmployee, false);
        }
        return ResultUtil::unauthorized();
    }


}