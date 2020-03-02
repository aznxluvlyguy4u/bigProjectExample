<?php

namespace AppBundle\Service;



use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Client;
use AppBundle\Entity\EmailChangeConfirmation;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Person;
use AppBundle\Entity\VwaEmployee;
use AppBundle\Enumerator\DashboardType;
use AppBundle\Enumerator\UserActionType;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\StringUtil;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AuthServiceBase extends ControllerServiceBase
{
    const DEFAULT_PASSWORD_LENGTH = 9;
    const PASSWORD_RESET_EXPIRATION_DAYS = 1;

    const ERROR_EMAIL_ADDRESS_EMPTY = "'email address' cannot be empty.";

    /** @var EmailService */
    protected $emailService;
    /** @var UserPasswordEncoderInterface */
    protected $encoder;
    /** @var TwigEngine */
    private $templatingService;

    /**
     * @required
     *
     * @param EmailService $emailService
     */
    public function setEmailService(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * @required
     *
     * @param UserPasswordEncoderInterface $encoder
     */
    public function setEncoder(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    /**
     * @required
     *
     * @param TwigEngine $templatingService
     */
    public function setTemplatingService(TwigEngine $templatingService)
    {
        $this->templatingService = $templatingService;
    }


    /**
     * @return TwigEngine
     */
    public function getTemplatingService()
    {
        return $this->templatingService;
    }


    /**
     *
     *  Json body example
     *  {
     *      "dashboard_type":"admin/client/vwa",
     *      "email_address":"example@example.com"
     *  }
     *
     * @param Request $request
     * @param string $dashboardType
     * @return JsonResponse
     */
    function passwordResetRequest(Request $request, $dashboardType = null)
    {
        $content = RequestUtil::getContentAsArrayCollection($request);
        if (!$content->containsKey(JsonInputConstant::EMAIL_ADDRESS)) {
            return ResultUtil::errorResult(self::ERROR_EMAIL_ADDRESS_EMPTY, Response::HTTP_BAD_REQUEST);
        }

        $emailAddress = trim(strtolower($content->get(JsonInputConstant::EMAIL_ADDRESS)));
        $dashboardType = $dashboardType === null ? $content->get(JsonInputConstant::DASHBOARD_TYPE) : $dashboardType;

        try {

            $person = self::getPersonByEmailAddressAndDashboardType($emailAddress, $dashboardType);
            $userActionType = self::getUserActionTypeByDashboardType($dashboardType);

            $log = ActionLogWriter::passwordResetRequest($this->getManager(), $person, $userActionType, $emailAddress);

            if ($person !== null) {


                    $resetToken = false;
                    if ($person->getPasswordResetToken() === null || $person->getPasswordResetTokenCreationDate() === null) {
                        $resetToken = true;
                    } elseif ($person->getPasswordResetTokenAgeInDays() > self::PASSWORD_RESET_EXPIRATION_DAYS) {
                        $resetToken = true;
                    }

                    if ($resetToken) {
                        $person->setPasswordResetToken(StringUtil::getResetToken());
                        $person->setPasswordResetTokenCreationDate(new \DateTime());
                        $this->getManager()->persist($person);
                        $this->getManager()->flush();
                    }

                    $isEmailSent = $this->emailService->emailPasswordResetToken($person);
                    if ($isEmailSent) {
                        ActionLogWriter::completeActionLog($this->getManager(), $log);
                    }
            }

        } catch (\Exception $exception) {
            //TODO ActionLog error

            return ResultUtil::errorResult('Er is iets fouts gegaan, probeer het nogmaals', Response::HTTP_CONFLICT);
        }

        return ResultUtil::successResult('Password reset request processed for email address: ' . $emailAddress);
    }


    /**
     * @param string $dashboardType
     * @return string
     * @throws \Exception
     */
    public static function getUserActionTypeByDashboardType($dashboardType)
    {
        switch ($dashboardType) {
            case DashboardType::ADMIN: return UserActionType::ADMIN_PASSWORD_RESET;
            case DashboardType::CLIENT: return UserActionType::USER_PASSWORD_RESET;
            case DashboardType::VWA; return UserActionType::VWA_PASSWORD_RESET;
            default: throw self::invalidDashboardTypeException();
        }
    }


    /**
     * @param string $dashboardType
     * @return string
     * @throws \Exception
     */
    public static function getPersonChildClazzByDashboardType($dashboardType)
    {
        switch ($dashboardType) {
            case DashboardType::ADMIN: return Employee::class;
            case DashboardType::CLIENT: return Client::class;
            case DashboardType::VWA; return VwaEmployee::class;
            default: throw self::invalidDashboardTypeException();
        }
    }


    /**
     * @throws \Exception
     */
    private static function invalidDashboardTypeException()
    {
        $message = "Valid '" . JsonInputConstant::DASHBOARD_TYPE . "' is missing. Allowed values: " . implode(', ', DashboardType::getConstants());
        return new \Exception($message, Response::HTTP_PRECONDITION_REQUIRED);
    }


    /**
     * @param string $emailAddress
     * @param string $dashboardType
     * @return Client|Employee|VwaEmployee|Person
     * @throws \Exception
     */
    protected function getPersonByEmailAddressAndDashboardType($emailAddress, $dashboardType)
    {
        $clazz = self::getPersonChildClazzByDashboardType($dashboardType);
        return $this->getManager()->getRepository($clazz)
            ->findOneBy(['isActive' => true, 'emailAddress' => $emailAddress]);
    }


    /**
     * @param string $resetToken
     * @return string
     */
    function passwordResetConfirmation($resetToken)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/html');

        $person = null;
        if ($resetToken !== null) {
            $person = $this->getManager()->getRepository(Person::class)
                ->findOneBy(['isActive' => true, 'passwordResetToken' => $resetToken]);
        }

        if ($person) {
            try {
                $passwordLength = self::DEFAULT_PASSWORD_LENGTH;
                if ($person instanceof VwaEmployee) {
                    $passwordLength = VwaEmployeeService::VWA_PASSWORD_LENGTH;
                }
                $newPassword = AuthService::persistNewPassword($this->encoder, $this->getManager(),
                    $person, $passwordLength);

                if ($this->emailService->emailNewPasswordToPerson($person, $newPassword)) {
                    $person->setPasswordResetToken(null);
                    $person->setPasswordResetTokenCreationDate(null);
                    $this->getManager()->persist($person);
                    ActionLogWriter::passwordResetConfirmation($this->getManager(), $person);

                    return $this->getTemplatingService()->renderResponse('Status/password_reset_success.html.twig', [], $response);
                }
            } catch (\Exception $exception) {
                //TODO ActionLog error

            }
            //TODO ActionLog error
        }
        //TODO ActionLog error

        return $this->getTemplatingService()->renderResponse('Status/password_reset_failed.html.twig', [], $response);
    }

    /**
     *
     *  Json body example
     *  {
     *      "dashboard_type":"admin/client/vwa",
     *      "email_address":"example@example.com"
     *  }
     *
     * @param Request $request
     * @param string $dashboardType
     * @return JsonResponse
     */
    function emailChangeRequest(Request $request, $dashboardType = null)
    {
        $content = RequestUtil::getContentAsArrayCollection($request);
        if (!$content->containsKey(JsonInputConstant::EMAIL_ADDRESS)) {
            return ResultUtil::errorResult(self::ERROR_EMAIL_ADDRESS_EMPTY, Response::HTTP_BAD_REQUEST);
        }

        $newEmailAddress = trim(strtolower(base64_decode($content->get(JsonInputConstant::EMAIL_ADDRESS))));
        $password = base64_decode($content->get(JsonInputConstant::PASSWORD));

        if (!filter_var($newEmailAddress, FILTER_VALIDATE_EMAIL)) {
            return ResultUtil::errorResult($this->translator->trans('THIS IS NOT A VALID EMAIL ADDRESS'), Response::HTTP_BAD_REQUEST);
        }
        $dashboardType = $dashboardType === null ? $content->get(JsonInputConstant::DASHBOARD_TYPE) : $dashboardType;

        $loggedInUser = $this->getAccountOwner($request);

        try {

            $personByNewEmailAddress = self::getPersonByEmailAddressAndDashboardType($newEmailAddress, $dashboardType);

            if ($personByNewEmailAddress) {
                return ResultUtil::errorResult($this->translator->trans('A USER ALREADY EXISTS FOR THIS EMAIL ADDRESS'), Response::HTTP_BAD_REQUEST);
            }

            if ($loggedInUser !== null) {
                if(!$this->encoder->isPasswordValid($loggedInUser, $password)) {
                    return ResultUtil::errorResult($this->translator->trans('THE PASSWORD IS INCORRECT'), Response::HTTP_BAD_REQUEST);
                }

                $token = $loggedInUser->getEmailChangeToken();
                if($token == null){
                    $token = new EmailChangeConfirmation();
                }

                $token
                    ->setCreationDate(new \DateTime())
                    ->setToken(StringUtil::getResetToken())
                    ->setEmailAddress($newEmailAddress)
                    ->setPerson($loggedInUser)
                ;

                $loggedInUser->setEmailChangeToken($token);
                $this->getManager()->persist($token);
                $this->getManager()->persist($loggedInUser);
                $this->getManager()->flush();

                // ActionLog is only persisted for successful email change

                $isEmailSent = $this->emailService->emailChangeConfirmationToken($loggedInUser);
                if ($isEmailSent) {
                    return ResultUtil::successResult('E-mail change request processed for email address: ' . $newEmailAddress);
                }
            }

        } catch (\Exception $exception) {
            return ResultUtil::errorResult($exception->getMessage(), Response::HTTP_CONFLICT);
        }

        return ResultUtil::successResult('E-mail change request processed for email address: ' . $newEmailAddress);
    }

    /**
     * @param $token
     * @return Response
     * @throws \Twig\Error\Error
     */
    function emailChangeConfirmation($token)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/html');

        $emailToken = null;
        if ($token !== null) {
            /** @var EmailChangeConfirmation $emailToken */
            $emailToken = $this->getManager()
                ->getRepository(EmailChangeConfirmation::class)
                ->findOneBy(['token' => $token]);
        }

        try {
            if (!$emailToken ||
                $emailToken->getEmailConfirmationTokenAgeInDays() >= self::PASSWORD_RESET_EXPIRATION_DAYS)
            {
                return $this->getTemplatingService()->renderResponse('Status/email_change_expired.html.twig', [], $response);
            }

            $person = $emailToken->getPerson();
            if($person != null) {

                $oldEmailAddress = $person->getEmailAddress();
                $newEmailAddress = $emailToken->getEmailAddress();

                $person->setEmailAddress($newEmailAddress);
                $person->setEmailChangeToken(null);
                $this->getManager()->remove($emailToken);
                $person->setAccessToken(Utils::generateTokenCode());
                $this->getManager()->persist($person);
                $this->getManager()->flush();

                ActionLogWriter::emailChangeConfirmation($this->getManager(), $person, $oldEmailAddress, $newEmailAddress);

                return $this->getTemplatingService()->renderResponse('Status/email_change_success.html.twig', [], $response);
            }

        } catch (\Exception $exception) {
            $this->logExceptionAsError($exception);
            return $this->getTemplatingService()->renderResponse('Status/email_change_failed.html.twig', [], $response);
        }

        return $this->getTemplatingService()->renderResponse('Status/email_change_failed.html.twig', [], $response);
    }
}
