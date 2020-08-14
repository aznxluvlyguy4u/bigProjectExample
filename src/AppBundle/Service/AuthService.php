<?php


namespace AppBundle\Service;


use AppBundle\Entity\Address;
use AppBundle\Entity\Company;
use AppBundle\Entity\CompanyAddress;
use AppBundle\Entity\Country;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationAddress;
use AppBundle\Entity\Registration;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Enumerator\RegistrationStatus;
use AppBundle\Util\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Client;
use AppBundle\Entity\MobileDevice;
use AppBundle\Entity\Person;
use AppBundle\Output\MenuBarOutput;
use AppBundle\Enumerator\Country as CountryCodeEnum;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\HeaderValidation;
use AppBundle\Validation\PasswordValidator;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthService extends AuthServiceBase
{

    /** @var ValidatorInterface $validator */
    private $validator;

    /** @var CompanyService $companyService */
    private $companyService;

    /**
     * @param ValidatorInterface $validator
     * @param CompanyService $companyService
     */
    public function setProperties(ValidatorInterface $validator, CompanyService $companyService)
    {
        $this->validator = $validator;
        $this->companyService = $companyService;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function signUpUser(Request $request)
    {
        /** @var Registration $registration */
        $registration = $this->getBaseSerializer()->deserializeToObject($request->getContent(), Registration::class);

        $errors = $this->validator->validate($registration);
        Validator::throwExceptionWithFormattedErrorMessageIfHasErrors($errors);

        $registration->setStatus(RegistrationStatus::NEW);

        $registration->__construct();

        $ubn = $registration->getUbn();

//        if (!Validator::hasValidUbnFormat($ubn)) {
//            throw new PreconditionFailedHttpException($this->translateUcFirstLower('UBN IS NOT A VALID NUMBER').': '.$ubn);
//        }

        $existingLocation = $this->getManager()->getRepository(Location::class)->findOneByActiveUbn($ubn);

        if ($existingLocation) {
            throw new PreconditionFailedHttpException($this->translateUcFirstLower('A LOCATION WITH THIS UBN ALREADY EXISTS').': '.$ubn);
        }

        $existingNewRegistration = $this->getManager()->getRepository(Registration::class)->findOneBy(['status' => RegistrationStatus::NEW, 'ubn' => $ubn]);

        if ($existingNewRegistration) {
            throw new PreconditionFailedHttpException($this->translateUcFirstLower('A NEW REGISTRATION ALREADY EXISTS FOR THIS UBN').': '.$ubn);
        }

        $errors = $this->validator->validate($registration);
        Validator::throwExceptionWithFormattedErrorMessageIfHasErrors($errors);

        $this->getManager()->persist($registration);
        $this->getManager()->flush();

       if (!$this->emailService->sendNewRegistrationEmails($registration)) {

           $registration->setStatus(RegistrationStatus::FAILED_SENDING_EMAILS);
           $this->getManager()->persist($registration);
           $this->getManager()->flush();

           throw new PreconditionFailedHttpException($this->translateUcFirstLower('THE REGISTRATIONS EMAILS HAVE NOT BEEN SENT'));
       }

        return new JsonResponse(
            $this->getBaseSerializer()->getDecodedJson($registration, [JmsGroup::REGISTRATION]),
            Response::HTTP_OK
        );
    }

    /**
     * @param $registrationId
     * @return JsonResponse
     * @throws Exception
     */
    public function processRegistration($registrationId) {
        /** @var Registration $registration */
        $registration = $this->getManager()->getRepository(Registration::class)->findOneBy(['status' => 'NEW', 'registrationId' => $registrationId]);

        /**
         * TODO currently "registration" is whitelisted in the security.yml firewall. Remove this whitelisting if the feature requires authentication!
         * Currently this endpoint is acccessed through a link in an email. Therefore the authentication has to be deactivated
         */

        if (!$registration) {
            throw new PreconditionFailedHttpException('This registration is already processed or could not be found');
        }

        $client = new Client();
        $location = new Location();
        $company = $this->companyService->generateAndSetDebtorCode(new Company());
        $companyAddress = new CompanyAddress();
        $locationAddress = new LocationAddress();

        $country = $this->getManager()
            ->getRepository(Country::class)
            ->findOneByCode(CountryCodeEnum::NL);

        $client
            ->setFirstName($registration->getFirstName())
            ->setLastName($registration->getLastName())
            ->setEmailAddress($registration->getEmailAddress())
            ->setCellphoneNumber($registration->getPhoneNumber())
            ->setRelationNumberKeeper($registration->getBrs())
            ->setIsActive(true);

        // TODO generate a default password and email it to the new user
        // If the emailaddress ends with @nsfo.nl it is a dummy email address
        
        $companyAddress
            ->setStreetName($registration->getStreetName())
            ->setAddressNumber($registration->getAddressNumber())
            ->setAddressNumberSuffix($registration->getAddressNumberSuffix())
            ->setPostalCode($registration->getPostalCode())
            ->setCity($registration->getCity())
            ->setCountryDetails($country);

        $locationAddress
            ->setStreetName($registration->getStreetName())
            ->setAddressNumber($registration->getAddressNumber())
            ->setAddressNumberSuffix($registration->getAddressNumberSuffix())
            ->setPostalCode($registration->getPostalCode())
            ->setCity($registration->getCity())
            ->setCountryDetails($country);

        $company
            ->setCompanyName($registration->getCompanyName())
            ->setIsActive(true)
            ->setAddress($companyAddress)
            ->addLocation($location)
            ->setAnimalHealthSubscription(false)
            ->setIsRevealHistoricAnimals(true)
            ->setOwner($client)
            ->setTelephoneNumber($registration->getPhoneNumber())
            ->setCreatedAt($registration->getCreatedAt())
        ;

        $location
            ->setIsActive(true)
            ->setUbn($registration->getUbn())
            ->setCompany($company)
            ->setAddress($locationAddress);

        $registration->setStatus(RegistrationStatus::COMPLETED);

        $this->getManager()->persist($client);
        $this->getManager()->persist($location);
        $this->getManager()->persist($company);
        $this->getManager()->persist($locationAddress);
        $this->getManager()->persist($companyAddress);
        $this->getManager()->persist($registration);

        $this->getManager()->flush();

        return new JsonResponse(
            $this->getBaseSerializer()->getDecodedJson($client, [JmsGroup::REGISTRATION]),
            Response::HTTP_OK
        );
    }

    /**
     * @param $credentials
     * @return array
     */
    public static function getCredentialsFromBasicAuthHeader($credentials): array
    {
        $credentials = str_replace('Basic ', '', $credentials);
        $credentials = base64_decode($credentials);

        $inputValues = explode(":", $credentials);
        $inputValues = !$inputValues ? [] : $inputValues;

        return [
          JsonInputConstant::EMAIL_ADDRESS => ArrayUtil::get(0, $inputValues),
          JsonInputConstant::PASSWORD => ArrayUtil::get(1, $inputValues),
        ];
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function authorizeUser(Request $request)
    {
        $registrationToken = null;
        $deviceId = null;
        if($request->getMethod() === 'GET') {
            $credentials = $request->headers->get(Constant::AUTHORIZATION_HEADER_NAMESPACE);
            $inputValues = AuthService::getCredentialsFromBasicAuthHeader($credentials);
            $emailAddress = $inputValues[JsonInputConstant::EMAIL_ADDRESS];
            $password = $inputValues[JsonInputConstant::PASSWORD];
        } else {
            $requestData = RequestUtil::getContentAsArrayCollection($request);

            $emailAddress = $requestData[JsonInputConstant::EMAIL_ADDRESS];
            $password = $requestData[JsonInputConstant::PASSWORD];
            $registrationToken = $requestData[JsonInputConstant::REGISTRATION_TOKEN];
            $deviceId = $requestData[JsonInputConstant::DEVICE_ID];
        }

        if($emailAddress != null && $password != null) {
            $emailAddress = strtolower($emailAddress);
            $client = $this->getManager()->getRepository(Client::class)->findActiveOneByEmailAddress($emailAddress);
            if($client == null) {
                return ResultUtil::unauthorized();
            }

            if(!$client->getIsActive()) {
                return ResultUtil::unauthorized();
            }

            if($client->getEmployer() != null) {
                if(!$client->getEmployer()->isActive()) {
                    return ResultUtil::unauthorized();
                }
            }

            if($client->getCompanies()->count() > 0) {
                $companies = $client->getCompanies();
                foreach ($companies as $company) {
                    if(!$company->isActive()){
                        return ResultUtil::unauthorized();
                    }
                }
            }

            if($this->encoder->isPasswordValid($client, $password)) {
                /** @var Client $client */
                $result = [
                    "access_token"=>$client->getAccessToken(),
                    "user" => MenuBarOutput::create($this->getManager(), $client)
                ];

                $client->setLastLoginDate(new \DateTime());
                $this->getManager()->persist($client);
                ActionLogWriter::loginUser($this->getManager(), $client, $client, true);

                if(!empty($registrationToken) && !empty($deviceId)) {
                    $mobileDevice = $this->getManager()->getRepository(MobileDevice::class)->findOneBy(['uuid' => $deviceId]);
                    if($mobileDevice == null) {
                        $mobileDevice = new MobileDevice();
                        $mobileDevice->setUuid($deviceId);
                        $mobileDevice->setOwner($client);
                        $client->addMobileDevice($mobileDevice);
                    }

                    if($mobileDevice->getRegistrationToken() != $registrationToken) {
                        $mobileDevice->setRegistrationToken($registrationToken);
                        $mobileDevice->setUpdatedAt(new \DateTime());
                    }
                    $this->getManager()->persist($mobileDevice);
                    $this->getManager()->persist($client);
                    $this->getManager()->flush();
                }

                return new JsonResponse(array("access_token"=>$client->getAccessToken(),
                    Constant::RESULT_NAMESPACE => $result), 200);
            }
            ActionLogWriter::loginUser($this->getManager(), $client, null, false);
        }

        return ResultUtil::unauthorized();

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
        $content = RequestUtil::getContentAsArrayCollection($request);
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
