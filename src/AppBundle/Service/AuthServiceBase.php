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
use Doctrine\ORM\EntityManagerInterface;
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
        $content = RequestUtil::getContentAsArray($request);
        if (!$content->containsKey(JsonInputConstant::EMAIL_ADDRESS)) {
            return ResultUtil::errorResult(self::ERROR_EMAIL_ADDRESS_EMPTY, Response::HTTP_BAD_REQUEST);
        }

        $emailAddress = trim(strtolower($content->get(JsonInputConstant::EMAIL_ADDRESS)));
        $dashboardType = $dashboardType === null ? $content->get(JsonInputConstant::DASHBOARD_TYPE) : $dashboardType;

        switch ($dashboardType) {
            case DashboardType::ADMIN:
                $person = $this->getManager()->getRepository(Employee::class)
                    ->findOneBy(['isActive' => true, 'emailAddress' => $emailAddress]);
                $userActionType = UserActionType::ADMIN_PASSWORD_RESET;
                break;
            case DashboardType::CLIENT:
                $person = $this->getManager()->getRepository(Client::class)
                    ->findOneBy(['isActive' => true, 'emailAddress' => $emailAddress]);
                $userActionType = UserActionType::USER_PASSWORD_RESET;
                break;
            case DashboardType::VWA;
                $person = $this->getManager()->getRepository(VwaEmployee::class)
                    ->findOneBy(['isActive' => true, 'emailAddress' => $emailAddress]);
                $userActionType = UserActionType::VWA_PASSWORD_RESET;
                break;
            default:
                $message = "Valid '" . JsonInputConstant::DASHBOARD_TYPE . "' is missing. Allowed values: " . implode(', ', DashboardType::getConstants());
                return ResultUtil::errorResult($message, 428);
        }

        $log = ActionLogWriter::passwordResetRequest($this->getManager(), $person, $userActionType, $emailAddress);

        if ($person !== null) {
            try {

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

            } catch (\Exception $exception) {
                //TODO ActionLog error

                return ResultUtil::errorResult('Er is iets fouts gegaan, probeer het nogmaals', Response::HTTP_CONFLICT);
            }
        }

        return ResultUtil::successResult('Password reset request processed for email address: ' . $emailAddress);
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
        $content = RequestUtil::getContentAsArray($request);
        if (!$content->containsKey(JsonInputConstant::EMAIL_ADDRESS)) {
            return ResultUtil::errorResult(self::ERROR_EMAIL_ADDRESS_EMPTY, Response::HTTP_BAD_REQUEST);
        }

        $currentEmail = base64_decode($content->get('current_email'));
        $emailAddress = trim(strtolower(base64_decode($content->get(JsonInputConstant::EMAIL_ADDRESS))));
        $password = base64_decode($content->get(JsonInputConstant::PASSWORD));

        if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            return ResultUtil::errorResult('Dit is geen valide e-mail', Response::HTTP_BAD_REQUEST);
        }
        $dashboardType = $dashboardType === null ? $content->get(JsonInputConstant::DASHBOARD_TYPE) : $dashboardType;

        $personType = null;
        switch ($dashboardType) {
            case DashboardType::ADMIN:
                $person = $this->getManager()->getRepository(Employee::class)
                    ->findOneBy(['isActive' => true, 'emailAddress' => $currentEmail]);
                $userActionType = UserActionType::ADMIN_EMAIL_CHANGE;
                $personType = Employee::class;
                break;
            case DashboardType::CLIENT:
                $person = $this->getManager()->getRepository(Client::class)
                    ->findOneBy(['isActive' => true, 'emailAddress' => $currentEmail]);
                $userActionType = UserActionType::USER_EMAIL_CHANGE;
                $personType = Client::class;
                break;
            case DashboardType::VWA;
                $person = $this->getManager()->getRepository(VwaEmployee::class)
                    ->findOneBy(['isActive' => true, 'emailAddress' => $currentEmail]);
                $userActionType = UserActionType::VWA_EMAIL_CHANGE;
                $personType = VwaEmployee::class;
                break;
            default:
                $message = "Valid '" . JsonInputConstant::DASHBOARD_TYPE . "' is missing. Allowed values: " . implode(', ', DashboardType::getConstants());
                return ResultUtil::errorResult($message, 428);
        }

        if($personType != null) {
            $uniquePerson = $this->getManager()
                ->getRepository($personType)
                ->findOneBy(['emailAddress' => $emailAddress]);
            if ($uniquePerson)
                return ResultUtil::errorResult('Er bestaat al een gebruiker met dit e-mail', Response::HTTP_BAD_REQUEST);
        }

        if ($person !== null && $personType != null) {
            try {
                if(!$this->encoder->isPasswordValid($person, $password))
                    return ResultUtil::errorResult('Het wachtwoord is niet correct', Response::HTTP_BAD_REQUEST);

                $token = $person->getEmailChangeToken();
                if($token == null){
                    $token = new EmailChangeConfirmation();
                }

                $token->setCreationDate(new \DateTime())
                    ->setToken(StringUtil::getResetToken())
                    ->setEmailAddress($emailAddress)
                    ->setPerson($person);

                $person->setEmailChangeToken($token);
                $this->getManager()->persist($person);
                $this->getManager()->flush();

                $isEmailSent = $this->emailService->emailChangeConfirmationToken($person);
                if ($isEmailSent) {
                    return ResultUtil::successResult('E-mail change request processed for email address: ' . $emailAddress);
                }

            } catch (\Exception $exception) {
                return ResultUtil::errorResult($exception->getMessage(), Response::HTTP_CONFLICT);
            }
        }

        return ResultUtil::successResult('E-mail change request processed for email address: ' . $emailAddress);
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
            $emailToken = $this->getManager()
                ->getRepository(EmailChangeConfirmation::class)
                ->findOneBy(['token' => $token]);
        }

        if ($emailToken) {
            try {
                if($emailToken->getEmailConfirmationTokenAgeInDays() < self::PASSWORD_RESET_EXPIRATION_DAYS) {
                    $person = $emailToken->getPerson();
                    if($person != null) {


                        $person->setEmailAddress($emailToken->getEmailAddress());
                        $person->setEmailChangeToken(null);
                        $this->getManager()->remove($emailToken);
                        $person->setAccessToken(Utils::generateTokenCode());
                        $this->getManager()->persist($person);
                        $this->getManager()->flush();
                        return $this->getTemplatingService()->renderResponse('Status/email_change_success.html.twig', [], $response);
                    }
                }
                //ActionLogWriter::passwordResetConfirmation($this->getManager(), $person);


            } catch (\Exception $exception) {
                //TODO ActionLog error

            }
            //TODO ActionLog error
        }
        //TODO ActionLog error

        return $this->getTemplatingService()->renderResponse('Status/email_change_failed.html.twig', [], $response);
    }
}