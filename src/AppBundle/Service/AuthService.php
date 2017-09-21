<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\DashboardType;
use AppBundle\Output\MenuBarOutput;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\HeaderValidation;
use AppBundle\Validation\PasswordValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AuthService extends AuthServiceBase
{

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
        $client = $this->getManager()->getRepository(Client::class)->persist($client);

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
            $client = $this->getManager()->getRepository(Client::class)->findActiveOneByEmailAddress($emailAddress);
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

                $client->setLastLoginDate(new \DateTime());
                $this->getManager()->persist($client);
                ActionLogWriter::loginUser($this->getManager(), $client, $client, true);

                return new JsonResponse(array("access_token"=>$client->getAccessToken(),
                    Constant::RESULT_NAMESPACE => $result), 200);
            }
            ActionLogWriter::loginUser($this->getManager(), $client, null, false);
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
            "current_password":"Tm90TXlGaXJzdFBhc3N3b3JkMQ==" //base64 encoded 'MyFirstPassword1'
            "new_password":"Tm90TXlGaXJzdFBhc3N3b3JkMQ==" //base64 encoded 'NotMyFirstPassword1'
        }
        */

        $client = $this->getAccountOwner($request);
        $loggedInUser = $this->getUser();
        $content = RequestUtil::getContentAsArray($request);
        $log = ActionLogWriter::passwordChange($this->getManager(), $client, $loggedInUser);

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

        $this->getManager()->persist($client);
        $this->getManager()->flush();

        //Validate password change
        $client = $this->getAccountOwner($request);
        $encodedPasswordInDatabase = $client->getPassword();

        if($encodedPasswordInDatabase == $encodedNewPassword) {

            $this->emailService->sendNewPasswordEmail($client->getEmailAddress());

            $log = ActionLogWriter::completeActionLog($this->getManager(), $log);

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
        $headerValidation = new HeaderValidation($this->getManager(), $request, $client);

        if($headerValidation->isInputValid()) {
            return ResultUtil::successResult('UBN IS VALID');
        } else {
            return $headerValidation->createJsonErrorResponse();
        }
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function isAccessTokenValid(Request $request)
    {
        return parent::isAccessTokenValid($request);
    }


    /**
     * @param UserPasswordEncoderInterface $encoder
     * @param EntityManagerInterface $manager
     * @param Person $person
     * @param int $passwordLength
     * @return string
     */
    public static function persistNewPassword(UserPasswordEncoderInterface $encoder, EntityManagerInterface $manager,
                                              Person $person, $passwordLength = 9)
    {
        $newPassword = Utils::randomString($passwordLength);

        $encodedNewPassword = $encoder->encodePassword($person, $newPassword);
        $person->setPassword($encodedNewPassword);

        $manager->persist($person);
        $manager->flush();

        return $newPassword;
    }


}