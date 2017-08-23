<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Person;
use AppBundle\Entity\Token;
use AppBundle\Enumerator\TokenType;
use AppBundle\Output\MenuBarOutput;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\HeaderValidation;
use AppBundle\Validation\PasswordValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AuthService extends ControllerServiceBase
{

    /** @var UserPasswordEncoderInterface */
    private $encoder;
    /** @var EmailService */
    private $emailService;

    public function __construct(EntityManagerInterface $em, IRSerializer $serializer, CacheService $cacheService, UserService $userService, UserPasswordEncoderInterface $encoder, EmailService $emailService)
    {
        parent::__construct($em, $serializer, $cacheService, $userService);

        $this->encoder = $encoder;
        $this->emailService = $emailService;
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function signUpUser(Request $request)
    {
        return ResultUtil::errorResult('no online registration available at the moment', 403);

        //TODO There is no registration page at the moment, so the route below is blocked
        $credentials = $request->headers->get(Constant::AUTHORIZATION_HEADER_NAMESPACE);
        $credentials = str_replace('Basic ', '', $credentials);
        $credentials = base64_decode($credentials);

        list($username, $password) = explode(":", $credentials);

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
        $content = RequestUtil::getContentAsArray($request);

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

        $encodedPassword = $this->encoder->encodePassword($client, $password);
        $client->setPassword($encodedPassword);

        /** @var Client $client */
        $client = $this->em->getRepository(Client::class)->persist($client);

        return new JsonResponse(array("access_token" => $client->getAccessToken()), 200);
    }


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
            $client = $this->clientRepository->findActiveOneByEmailAddress($emailAddress);
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

            if($this->encoder->isPasswordValid($client, $password)) {
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
     * @param Request $request
     * @return JsonResponse
     */
    public function changePassword(Request $request)
    {
        /*
        {
            "new_password":"Tm90TXlGaXJzdFBhc3N3b3JkMQ==" //base64 encoded 'NotMyFirstPassword1'
        }
        */

        $client = $this->getAccountOwner($request);
        $loggedInUser = $this->getUser();
        $content = RequestUtil::getContentAsArray($request);
        $log = ActionLogWriter::passwordChange($this->em, $client, $loggedInUser);

        $enteredOldPassword = base64_decode($content->get('current_password'));

        if(!$this->encoder->isPasswordValid($client, $enteredOldPassword)) {
            return new JsonResponse(array(Constant::MESSAGE_NAMESPACE => "CURRENT PASSWORD NOT VALID", Constant::CODE_NAMESPACE => 401), 401);
        }

        $newPassword = base64_decode($content->get('new_password'));

        //Validate password format
        $passwordValidator = new PasswordValidator($newPassword);
        if(!$passwordValidator->getIsPasswordValid()) {
            return $passwordValidator->createJsonErrorResponse();
        }

        $encodedOldPassword = $client->getPassword();
        $encodedNewPassword = $this->encoder->encodePassword($client, $newPassword);
        $client->setPassword($encodedNewPassword);

        $this->em->persist($client);
        $this->em->flush();

        //Validate password change
        $client = $this->getAccountOwner($request);
        $encodedPasswordInDatabase = $client->getPassword();

        if($encodedPasswordInDatabase == $encodedNewPassword) {

            $this->emailService->sendNewPasswordEmail($client->getEmailAddress());

            $log = ActionLogWriter::completeActionLog($this->em, $log);

            return new JsonResponse(array("code" => 200, "message"=>"Password has been changed"), 200);

        } else if($encodedPasswordInDatabase == $encodedOldPassword) {
            return new JsonResponse(array("code" => 428, "message"=>"Password has not been changed"), 428);

        } else if($encodedPasswordInDatabase == null) {
            return new JsonResponse(array("code" => 500, "message"=>"Password in database is null"), 500);
        }

        return new JsonResponse(array("code" => 401, "message"=>"Password in database doesn't match new or old password"), 401);

    }


    /**
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
        $client = $this->clientRepository->findActiveOneByEmailAddress($emailAddress);
        $log = ActionLogWriter::passwordReset($this->em, $client, $emailAddress);

        //Verify if email is correct
        if($client == null) {
            return new JsonResponse(array("code" => 428, "message"=>"No user found with emailaddress: " . $emailAddress), 428);
        }

        //Create a new password
        $passwordLength = 9;
        $newPassword = $this->persistNewPassword($client);
        $this->emailService->emailNewPasswordToPerson($client, $newPassword);

        $log = ActionLogWriter::completeActionLog($this->em, $log);

        return new JsonResponse(array("code" => 200,
            "message"=>"Your new password has been emailed to: " . $emailAddress), 200);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function generatePasswordsForNewClients(Request $request)
    {
        return ResultUtil::errorResult('Forbidden', 403);

        $migrationResults = $this->getClientMigratorService()->generateNewPasswordsAndEmailsForMigratedClients(RequestUtil::getContentAsArray($request));

        return ResultUtil::successResult($migrationResults);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function validateUbnInHeader(Request $request)
    {
        $client = $this->getAccountOwner($request);
        $headerValidation = new HeaderValidation($this->em, $request, $client);

        if($headerValidation->isInputValid()) {
            return ResultUtil::successResult('UBN IS VALID');
        } else {
            return $headerValidation->createJsonErrorResponse();
        }
    }


    /**
     *
     * @param Person $person
     * @param int $passwordLength
     * @return string
     */
    public function persistNewPassword($person, $passwordLength = 9)
    {
        $newPassword = Utils::randomString($passwordLength);

        $encodedNewPassword = $this->encoder->encodePassword($person, $newPassword);
        $person->setPassword($encodedNewPassword);

        $this->em->persist($person);
        $this->em->flush();

        return $newPassword;
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function isAccessTokenValid(Request $request)
    {
        $token = null;
        $response = null;
        $content = RequestUtil::getContentAsArray($request);

        //Get token header to read token value
        if($request->headers->has(Constant::ACCESS_TOKEN_HEADER_NAMESPACE)) {

            $environment = $content->get('env');
            $tokenCode = $request->headers->get(Constant::ACCESS_TOKEN_HEADER_NAMESPACE);
            $token = $this->em->getRepository(Token::class)
                ->findOneBy(array("code" => $tokenCode, "type" => TokenType::ACCESS));

            if ($token != null) {
                if ($environment == 'USER') {
                    if ($token->getOwner() instanceof Client) {
                        $response = array(
                            'token_status' => 'valid',
                            'token' => $tokenCode
                        );
                        return new JsonResponse($response, 200);
                    } elseif ($token->getOwner() instanceof Employee ) {
                        $ghostTokenCode = $request->headers->get(Constant::GHOST_TOKEN_HEADER_NAMESPACE);
                        $ghostToken = $this->em->getRepository(Token::class)
                            ->findOneBy(array("code" => $ghostTokenCode, "type" => TokenType::GHOST));

                        if($ghostToken != null) {
                            $response = array(
                                'token_status' => 'valid',
                                'token' => $tokenCode
                            );
                            return new JsonResponse($response, 200);
                        }
                    } else {
                        $response = array(
                            'error' => 401,
                            'errorMessage' => 'No AccessToken provided'
                        );
                    }
                }
            }

            if ($environment == 'ADMIN') {
                if ($token->getOwner() instanceof Employee) {
                    $response = array(
                        'token_status' => 'valid',
                        'token' => $tokenCode
                    );
                    return new JsonResponse($response, 200);
                } else {
                    $response = array(
                        'error' => 401,
                        'errorMessage' => 'No AccessToken provided'
                    );
                }
            }

            $response = array(
                'error'=> 401,
                'errorMessage'=> 'No AccessToken provided'
            );
        } else {
            //Mandatory AccessToken was not provided
            $response = array(
                'error'=> 401,
                'errorMessage'=> 'Mandatory AccessToken header was not provided'
            );
        }

        return new JsonResponse($response, 401);
    }
}