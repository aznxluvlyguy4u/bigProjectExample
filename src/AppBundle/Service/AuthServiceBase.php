<?php

namespace AppBundle\Service;



use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Client;
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

    public function __construct(BaseSerializer $baseSerializer,
                                CacheService $cacheService,
                                EmailService $emailService,
                                EntityManagerInterface $manager,
                                UserService $userService,
                                UserPasswordEncoderInterface $encoder,
                                TwigEngine $templatingService)
    {
        parent::__construct($baseSerializer, $cacheService, $manager, $userService);
        $this->emailService = $emailService;
        $this->encoder = $encoder;
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
     *      "email_address":"example@example.com"
     *      "dashboard_type":"admin/client/vwa",
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
        $dashboardType = $dashboardType === null ? $content->get('dashboard_type') : $dashboardType;

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
                $person = null;
                $userActionType = null;
                break;
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

        return ResultUtil::successResult('Password reset request processed for email address: '.$emailAddress);
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
}