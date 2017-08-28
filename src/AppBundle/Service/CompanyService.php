<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\BillingAddress;
use AppBundle\Entity\Client;
use AppBundle\Entity\Company;
use AppBundle\Entity\CompanyAddress;
use AppBundle\Entity\CompanyNote;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationAddress;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Output\CompanyNoteOutput;
use AppBundle\Output\CompanyOutput;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use AppBundle\Validation\CompanyValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


class CompanyService extends AuthServiceBase
{
    const DEFAULT_COUNTRY = '';

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getCompanies(Request $request)
    {
        // Validation if user is an admin
        $admin = $this->getEmployee();
        if (!AdminValidator::isAdmin($admin, AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        // Get all companies
        $em = $this->getManager();
        $query = $em->createQuery(
            'SELECT c,a,u,l,o,p,i         
            FROM AppBundle:Company c 
            LEFT JOIN c.locations l 
            LEFT JOIN c.owner o 
            LEFT JOIN c.companyUsers u 
            LEFT JOIN c.address a
            LEFT JOIN c.pedigrees p
            LEFT JOIN c.invoices i'
        );
        $companies = $query->getResult(Query::HYDRATE_ARRAY);

        // Generate Company Overview
        $result = CompanyOutput::createCompaniesOverview($companies);

        return ResultUtil::successResult($result);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createCompany(Request $request)
    {
        // Validation if user is an admin
        $admin = $this->getEmployee();
        if (!AdminValidator::isAdmin($admin, AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        // Validate content
        $content = RequestUtil::getContentAsArray($request);

        // TODO VALIDATE CONTENT
        $companyValidator = new CompanyValidator($this->getManager(), $content);
        if(!$companyValidator->getIsInputValid()) { return $companyValidator->createJsonResponse(); }

        // Create Owner
        $contentUsers = $content->get('users');
        $contentOwner = $content->get('owner');

        $emailAddressOwner = $contentOwner['email_address'];
        if(CompanyValidator::doesClientAlreadyExist($this->getManager(), $emailAddressOwner)) {
            return CompanyValidator::emailAddressIsInUseErrorMessage($emailAddressOwner);
        }

        $owner = new Client();
        $owner->setFirstName($contentOwner['first_name']);
        $owner->setLastName($contentOwner['last_name']);
        $owner->setEmailAddress($contentOwner['email_address']);
        $owner->setRelationNumberKeeper($content->get('company_relation_number'));
        $owner->setObjectType('Client');
        $owner->setIsActive(true);

        // Create Address
        $contentAddress = $content->get('address');
        $address = new CompanyAddress();
        $address->setStreetName($contentAddress['street_name']);
        $address->setAddressNumber($contentAddress['address_number']);

        if(isset($contentAddress['suffix'])) {
            $address->setAddressNumberSuffix($contentAddress['suffix']);
        }

        $address->setPostalCode($contentAddress['postal_code']);
        $address->setCity($contentAddress['city']);
        $address->setState($contentAddress['state']);
        $address->setCountry(ArrayUtil::get('country', $contentAddress, self::DEFAULT_COUNTRY));

        // Create Billing Address
        $contentBillingAddress = $content->get('billing_address');
        $billingAddress = new BillingAddress();
        $billingAddress->setStreetName($contentBillingAddress['street_name']);
        $billingAddress->setAddressNumber($contentBillingAddress['address_number']);

        if(isset($contentBillingAddress['suffix'])) {
            $billingAddress->setAddressNumberSuffix($contentBillingAddress['suffix']);
        }

        $billingAddress->setPostalCode($contentBillingAddress['postal_code']);
        $billingAddress->setCity($contentBillingAddress['city']);
        $billingAddress->setState($contentBillingAddress['state']);
        $billingAddress->setCountry(ArrayUtil::get('country', $contentBillingAddress, self::DEFAULT_COUNTRY));

        // Create Company
        $company = new Company();
        $company->setCompanyName($content->get('company_name'));
        $company->setTelephoneNumber($content->get('telephone_number'));
        $company->setCompanyRelationNumber($content->get('company_relation_number'));
        $company->setDebtorNumber($content->get('debtor_number'));
        $company->setVatNumber($content->get('vat_number'));
        $company->setChamberOfCommerceNumber($content->get('chamber_of_commerce_number'));
        $company->setAnimalHealthSubscription($content->get('animal_health_subscription'));
        if($content->get('subscription_date')) {
            $company->setSubscriptionDate(new \DateTime($content->get('subscription_date')));
        }
        $company->setIsActive(true);
        $company->setOwner($owner);
        $company->setAddress($address);
        $company->setBillingAddress($billingAddress);
        $company->setIsRevealHistoricAnimals(true);

        // Create Location
        $locations = new ArrayCollection();
        $contentLocations = $content->get('locations');
        $repository = $this->getManager()->getRepository(Location::class);

        foreach ($contentLocations as $contentLocation) {
            $location = $repository->findOneBy(array('ubn' => $contentLocation['ubn'], 'isActive' => true));

            if($location) {
                return new JsonResponse(
                    array(
                        Constant::CODE_NAMESPACE => 400,
                        Constant::MESSAGE_NAMESPACE => 'THIS UBN IS ALREADY REGISTERED IN ANOTHER COMPANY. UBN HAS TO BE UNIQUE.',
                        'data' => $contentLocation['ubn']
                    ),
                    400
                );
            }

            // Create Location Address
            $contentLocationAddress = $contentLocation['address'];
            $locationAddress = new LocationAddress();
            $locationAddress->setStreetName($contentLocationAddress['street_name']);
            $locationAddress->setAddressNumber($contentLocationAddress['address_number']);

            if(isset($contentLocationAddress['suffix'])) {
                $locationAddress->setAddressNumberSuffix($contentLocationAddress['suffix']);
            }

            $locationAddress->setPostalCode($contentLocationAddress['postal_code']);
            $locationAddress->setCity($contentLocationAddress['city']);
            $locationAddress->setState($contentLocationAddress['state']);
            $locationAddress->setCountry(ArrayUtil::get('country', $contentLocationAddress, self::DEFAULT_COUNTRY));

            $location = new Location();
            $location->setUbn($contentLocation['ubn']);
            $location->setAddress($locationAddress);
            $location->setCompany($company);
            $location->setIsActive(true);
            $locations->add($location);
        }

        $company->setLocations($locations);

        // Create Users
        foreach ($contentUsers as $contentUser) {

            $emailAddressUser = $contentUser['email_address'];
            if(CompanyValidator::doesClientAlreadyExist($this->getManager(), $emailAddressUser)) {
                return CompanyValidator::emailAddressIsInUseErrorMessage($emailAddressUser);
            }

            if($contentUser['primary_contactperson'] == false) {
                $user = new Client();
                $user->setFirstName($contentUser['first_name']);
                $user->setLastName($contentUser['last_name']);
                $user->setEmailAddress($emailAddressUser);
                $user->setObjectType('Client');
                $user->setIsActive(true);
                $user->setEmployer($company);
                $this->getManager()->persist($user);
            }
        }

        // Save to Database
        $this->getManager()->persist($company);
        $this->getManager()->flush();

        // Send Email with passwords to Owner & Users
        $password = AuthService::persistNewPassword($this->encoder, $this->getManager(), $company->getOwner());
        $this->emailService->emailNewPasswordToPerson($company->getOwner(), $password, false, true);

        foreach ($company->getCompanyUsers() as $user) {
            $password = AuthService::persistNewPassword($this->encoder, $this->getManager(), $user);
            $this->emailService->emailNewPasswordToPerson($user, $password, false, true);
        }

        //Update all LocationOfBirths of Animals, for locations belonging to this company
        //This information is necessary to show the most up to date information on the PedigreeCertificates
        $this->getManager()->getRepository(Animal::class)->updateLocationOfBirthByCompany($company);

        return ResultUtil::successResult('ok');
    }


    /**
     * @param Request $request
     * @param $companyId
     * @return JsonResponse
     */
    public function getCompany(Request $request, $companyId)
    {
        // Validation if user is an admin
        $admin = $this->getEmployee();
        if (!AdminValidator::isAdmin($admin, AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        // Get Company
        $company = $this->getManager()->getRepository(Company::class)->findOneByCompanyId($companyId);

        // Generate Company Details
        $result = CompanyOutput::createCompany($company);

        return ResultUtil::successResult($result);
    }


    /**
     * @param Request $request
     * @param $companyId
     * @return JsonResponse
     */
    public function updateCompany(Request $request, $companyId)
    {
        // Validation if user is an admin
        $admin = $this->getEmployee();
        if (!AdminValidator::isAdmin($admin, AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }
        // Validate content
        $content = RequestUtil::getContentAsArray($request);
        // TODO VALIDATE CONTENT

        // Get Company
        $company = $this->getManager()->getRepository(Company::class)->findOneByCompanyId($companyId);

        /**
         * @var Company $company
         * @var Client $owner
         */

        // Update Owner
        $contentOwner = $content->get('owner');

        $emailAddressOwner = $contentOwner['email_address'];
        $owner = $this->getManager()->getRepository(Client::class)->findOneBy(array('emailAddress' => $emailAddressOwner, 'isActive' => true));

        if(isset($contentOwner['person_id'])) {
            $contentPersonId = $contentOwner['person_id'];

            if($owner && $owner->getPersonId() != $contentPersonId) {
                return CompanyValidator::emailAddressIsInUseErrorMessage($emailAddressOwner);
            }

            $owner = $this->getManager()->getRepository(Client::class)->findOneByPersonId($contentPersonId);

            $owner->setFirstName($contentOwner['first_name']);
            $owner->setLastName($contentOwner['last_name']);
            $owner->setEmailAddress($contentOwner['email_address']);

            $this->getManager()->persist($owner);
            $this->getManager()->flush();
        } else {
            if($owner) {
                return CompanyValidator::emailAddressIsInUseErrorMessage($emailAddressOwner);
            }

            $owner = $company->getOwner();
            $owner->setIsActive(false);
            $this->getManager()->persist($owner);
            $this->getManager()->flush();

            $owner = new Client();
            $owner->setFirstName($contentOwner['first_name']);
            $owner->setLastName($contentOwner['last_name']);
            $owner->setEmailAddress($contentOwner['email_address']);
            $owner->setObjectType('Client');
            $owner->setIsActive(true);
            $company->setOwner($owner);
        }

        // Update Address
        $address = $company->getAddress();
        $contentAddress = $content->get('address');

        $address->setStreetName($contentAddress['street_name']);
        $address->setAddressNumber($contentAddress['address_number']);

        if(isset($contentAddress['suffix'])) {
            $address->setAddressNumberSuffix($contentAddress['suffix']);
        } else {
            $address->setAddressNumberSuffix('');
        }

        $address->setPostalCode($contentAddress['postal_code']);
        $address->setCity($contentAddress['city']);
        $address->setState($contentAddress['state']);
        $address->setCountry(ArrayUtil::get('country', $contentAddress, self::DEFAULT_COUNTRY));

        // Update Billing Address
        $billingAddress = $company->getBillingAddress();
        $contentBillingAddress = $content->get('billing_address');

        $billingAddress->setStreetName($contentBillingAddress['street_name']);
        $billingAddress->setAddressNumber($contentBillingAddress['address_number']);

        if(isset($contentBillingAddress['suffix'])) {
            $billingAddress->setAddressNumberSuffix($contentBillingAddress['suffix']);
        } else {
            $billingAddress->setAddressNumberSuffix('');
        }

        $billingAddress->setPostalCode($contentBillingAddress['postal_code']);
        $billingAddress->setCity($contentBillingAddress['city']);
        $billingAddress->setState($contentBillingAddress['state']);
        $billingAddress->setCountry(ArrayUtil::get('country', $contentBillingAddress, self::DEFAULT_COUNTRY));

        // Update Company
        $company->setCompanyName($content->get('company_name'));
        $company->setTelephoneNumber($content->get('telephone_number'));
        $company->setCompanyRelationNumber($content->get('company_relation_number'));
        $company->setDebtorNumber($content->get('debtor_number'));
        $company->setVatNumber($content->get('vat_number'));
        $company->setChamberOfCommerceNumber($content->get('chamber_of_commerce_number'));
        $company->setAnimalHealthSubscription($content->get('animal_health_subscription'));

        if($content->get('subscription_date')) {
            $company->setSubscriptionDate(new \DateTime($content->get('subscription_date')));
        }

        $company->getOwner()->setRelationNumberKeeper($content->get('company_relation_number'));

        // Update Location


        // Deleted Locations -> Set 'isActive' to false
        $contentDeletedLocations = $content->get('deleted_locations');
        foreach($contentDeletedLocations as $contentDeletedLocation) {
            $contentLocationId = $contentDeletedLocation['location_id'];
            /** @var Location $location */
            $location = $this->getManager()->getRepository(Location::class)->findOneByLocationId($contentLocationId);

            if ($location) {
                $location->setIsActive(false);
                $this->getManager()->persist($location);
                $this->getManager()->flush();
            }
        }

        // Updated Locations
        $contentLocations = $content->get('locations');
        $repository = $this->getManager()->getRepository(Location::class);
        foreach($contentLocations as $contentLocation) {
            $location = $repository->findOneBy(array('ubn' => $contentLocation['ubn'], 'isActive' => true));

            /**
             * @var Location $location
             */
            if(isset($contentLocation['location_id'])) {
                $contentLocationId = $contentLocation['location_id'];

                if($location && $location->getLocationId() != $contentLocationId) {
                    return new JsonResponse(
                        array(
                            Constant::CODE_NAMESPACE => 400,
                            Constant::MESSAGE_NAMESPACE => 'THIS UBN IS ALREADY REGISTERED IN ANOTHER COMPANY. UBN HAS TO BE UNIQUE.',
                            'data' => $contentLocation['ubn']
                        ),
                        400
                    );
                }

                $location = $repository->findOneByLocationId($contentLocationId);
                $location->setUbn($contentLocation['ubn']);
                $locationAddress = $location->getAddress();
                $contentLocationAddress = $contentLocation['address'];
                $locationAddress->setStreetName($contentLocationAddress['street_name']);
                $locationAddress->setAddressNumber($contentLocationAddress['address_number']);

                if(isset($contentLocationAddress['suffix'])) {
                    $locationAddress->setAddressNumberSuffix($contentLocationAddress['suffix']);
                } else {
                    $locationAddress->setAddressNumberSuffix('');
                }

                $locationAddress->setPostalCode($contentLocationAddress['postal_code']);
                $locationAddress->setCity($contentLocationAddress['city']);
                $locationAddress->setState($contentLocationAddress['state']);
                $locationAddress->setCountry(ArrayUtil::get('country', $contentLocationAddress, self::DEFAULT_COUNTRY));

                $this->getManager()->persist($location);
                $this->getManager()->flush();
            } else {
                if($location) {
                    return new JsonResponse(
                        array(
                            Constant::CODE_NAMESPACE => 400,
                            Constant::MESSAGE_NAMESPACE => 'THIS UBN IS ALREADY REGISTERED IN ANOTHER COMPANY. UBN HAS TO BE UNIQUE.',
                            'data' => $contentLocation['ubn']
                        ),
                        400
                    );
                }

                $contentLocationAddress = $contentLocation['address'];
                $locationAddress = new LocationAddress();
                $locationAddress->setStreetName($contentLocationAddress['street_name']);
                $locationAddress->setAddressNumber($contentLocationAddress['address_number']);

                if(isset($contentLocationAddress['suffix'])) {
                    $locationAddress->setAddressNumberSuffix($contentLocationAddress['suffix']);
                }

                $locationAddress->setPostalCode($contentLocationAddress['postal_code']);
                $locationAddress->setCity($contentLocationAddress['city']);
                $locationAddress->setState($contentLocationAddress['state']);
                $locationAddress->setCountry(ArrayUtil::get('country', $contentLocationAddress, self::DEFAULT_COUNTRY));

                $location = new Location();
                $location->setUbn($contentLocation['ubn']);
                $location->setAddress($locationAddress);
                $location->setCompany($company);
                $location->setIsActive(true);
                $company->addLocation($location);
            }
        }

        // Update Users

        // Deleted Users -> Set 'isActive' to false
        $contentDeletedUsers = $content->get('deleted_users');
        foreach ($contentDeletedUsers as $contentDeletedUser) {
            if(isset($contentDeletedUser['person_id'])) {
                $contentPersonId = $contentDeletedUser['person_id'];
                $user = $this->getManager()->getRepository(Client::class)->findOneByPersonId($contentPersonId);

                if ($user) {
                    /**
                     * @var Client $user
                     */
                    $user->setIsActive(false);
                    $this->getManager()->persist($user);
                    $this->getManager()->flush();
                }
            }
        }

        // Updated Users
        $contentUsers = $content->get('users');
        $newUsers = array();
        $repository = $this->getManager()->getRepository(Client::class);

        foreach($contentUsers as $contentUser) {
            $emailAddressUser = $contentUser['email_address'];
            $user = $repository->findOneBy(array('emailAddress' => $emailAddressUser, 'isActive' => true));

            if(isset($contentUser['person_id'])) {
                $contentPersonId = $contentUser['person_id'];

                if($user && $user->getPersonId() != $contentPersonId) {
                    return CompanyValidator::emailAddressIsInUseErrorMessage($emailAddressUser);
                }

                $user = $repository->findOneByPersonId($contentPersonId);

                $user->setFirstName($contentUser['first_name']);
                $user->setLastName($contentUser['last_name']);
                $user->setEmailAddress($contentUser['email_address']);

                $this->getManager()->persist($user);
                $this->getManager()->flush();
            } else {
                if($user) {
                    return CompanyValidator::emailAddressIsInUseErrorMessage($emailAddressUser);
                }

                $user = new Client();
                $user->setFirstName($contentUser['first_name']);
                $user->setLastName($contentUser['last_name']);
                $user->setEmailAddress($contentUser['email_address']);
                $user->setObjectType('Client');
                $user->setIsActive(true);
                $user->setEmployer($company);
                $company->addCompanyUser($user);
                array_push($newUsers, $user);
            }
        }

        $this->getManager()->persist($company);
        $this->getManager()->flush();

        foreach ($newUsers as $user) {
            $password = AuthService::persistNewPassword($this->encoder, $this->getManager(), $user);
            $this->emailService->emailNewPasswordToPerson($user, $password, false, true);
        }

        $this->getManager()->getRepository(Animal::class)->updateLocationOfBirthByCompany($company);

        return ResultUtil::successResult('ok');
    }


    /**
     * @param Request $request
     * @param $companyId
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function setCompanyInactive(Request $request, $companyId)
    {
        // Validation if user is an admin
        $admin = $this->getEmployee();
        if (!AdminValidator::isAdmin($admin, AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        // Validate content
        $content = RequestUtil::getContentAsArray($request);
        // TODO VALIDATE CONTENT

        // Get Content
        $isActive = $content->get('is_active');

        // Get Company
        /** @var Company $company */
        $company = $this->getManager()->getRepository(Company::class)->findOneByCompanyId($companyId);

        // Set Company inactive
        $company->setIsActive($isActive);
        $this->getManager()->persist($company);
        $this->getManager()->flush();

        return ResultUtil::successResult('ok');
    }


    /**
     * @param Request $request
     * @param $companyId
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getCompanyDetails(Request $request, $companyId)
    {
        // Validation if user is an admin
        $admin = $this->getEmployee();
        if (!AdminValidator::isAdmin($admin, AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        // Get Company
        $company = $this->getManager()->getRepository(Company::class)->findOneByCompanyId($companyId);

        // Generate Company Details
        $result = CompanyOutput::createCompanyDetails($company);

        return ResultUtil::successResult($result);
    }


    /**
     * @param Request $request
     * @param $companyId
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getCompanyNotes(Request $request, $companyId)
    {
        // Validation if user is an admin
        $admin = $this->getEmployee();
        if (!AdminValidator::isAdmin($admin, AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        // Get Company
        $company = $this->getManager()->getRepository(Company::class)->findOneByCompanyId($companyId);

        /*
         * @var $company Company
         */
        // Get Company Notes
        $result = CompanyNoteOutput::createNotes($company);

        return ResultUtil::successResult($result);
    }


    /**
     * @param Request $request
     * @param $companyId
     * @return JsonResponse
     */
    public function createCompanyNotes(Request $request, $companyId)
    {
        // Validation if user is an admin
        $admin = $this->getEmployee();
        if (!AdminValidator::isAdmin($admin, AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        // Validate content
        $content = RequestUtil::getContentAsArray($request);
        // TODO VALIDATE CONTENT

        // Get Company
        $company = $this->getManager()->getRepository(Company::class)->findOneByCompanyId($companyId);

        // Create Note
        $note = new CompanyNote();
        $note->setCreationDate(new \DateTime());
        $note->setCreator($admin);
        $note->setCompany($company);
        $note->setNote($content['note']);

        $this->getManager()->persist($note);
        $this->getManager()->flush();

        $result = CompanyNoteOutput::createNoteResponse($note);

        return ResultUtil::successResult($result);
    }
    
    
}