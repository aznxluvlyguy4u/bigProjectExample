<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\Animal;
use AppBundle\Entity\BillingAddress;
use AppBundle\Entity\Client;
use AppBundle\Entity\Company;
use AppBundle\Entity\CompanyAddress;
use AppBundle\Entity\CompanyNote;
use AppBundle\Entity\Country;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationAddress;
use AppBundle\Entity\PedigreeRegisterRegistration;
use AppBundle\Entity\Tag;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Filter\ActiveCompanyFilter;
use AppBundle\Filter\ActiveInvoiceFilter;
use AppBundle\Filter\ActiveLocationFilter;
use AppBundle\Output\CompanyNoteOutput;
use AppBundle\Output\CompanyOutput;
use AppBundle\Service\ExternalProvider\ExternalProviderCustomerService;
use AppBundle\Setting\InvoiceSetting;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\AdminActionLogWriter;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Util\Validator;
use AppBundle\Validation\AdminValidator;
use AppBundle\Validation\CompanyValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Exception;
use SoapFault;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;


class CompanyService extends AuthServiceBase
{

    /** @var ExternalProviderCustomerService $externalProviderCustomerService */
    private $externalProviderCustomerService;

    public function setExternalCustomerProviderService(ExternalProviderCustomerService $externalProviderCustomerService)
    {
        $this->externalProviderCustomerService = $externalProviderCustomerService;
    }

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
            'SELECT c,a,u,l,o,i,b,la,lc         
            FROM AppBundle:Company c 
            LEFT JOIN c.locations l 
            LEFT JOIN c.owner o 
            LEFT JOIN c.companyUsers u 
            LEFT JOIN c.address a
            LEFT JOIN c.billingAddress b
            LEFT JOIN c.invoices i
            LEFT JOIN l.address la
            LEFT JOIN la.countryDetails lc
            '
        );
        $companies = $query->getResult(Query::HYDRATE_ARRAY);

        // Generate Company Overview
        $result = CompanyOutput::createCompaniesOverview($companies);

        return ResultUtil::successResult($result);
    }


    /**
     * company_relation_number
     * @param $companyRelationNumber
     */
    private function validateIfRelationNumberKeeperIsNotNull($companyRelationNumber)
    {
        if (empty(trim($companyRelationNumber))) {
            throw new PreconditionFailedHttpException($this->translator->trans(
                'COMPANY RELATION NUMBER CANNOT BE EMPTY'
            ));
        }
    }


    /**
     * @param Request $request
     * @return JsonResponse
     * @throws SoapFault
     */
    public function createCompany(Request $request)
    {
        // Validation if user is an admin
        $admin = $this->getEmployee();
        if (!AdminValidator::isAdmin($admin, AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        // Validate content
        $content = RequestUtil::getContentAsArrayCollection($request);

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

        $companyRelationNumber = $content->get('company_relation_number');
        $this->validateIfRelationNumberKeeperIsNotNull($companyRelationNumber);
        $owner->setRelationNumberKeeper($companyRelationNumber);

        $owner->setObjectType('Client');
        $owner->setIsActive(true);

        // Create Address
        $contentAddress = $content->get('address');

        $addressCountry = ArrayUtil::get('country', $contentAddress);
        if (empty($addressCountry)) {
            return ResultUtil::errorResult($this->translateUcFirstLower('ADDRESS COUNTRY CANNOT BE EMPTY'), Response::HTTP_PRECONDITION_REQUIRED);
        }

        $address = new CompanyAddress();
        $address->setStreetName($contentAddress['street_name']);
        $address->setAddressNumber($contentAddress['address_number']);

        if(isset($contentAddress['suffix'])) {
            $address->setAddressNumberSuffix($contentAddress['suffix']);
        }

        /** @var Country $addressCountryDB */
        $addressCountryDB = $this->getCountryByName($addressCountry);

        $address->setPostalCode($contentAddress['postal_code']);
        $address->setCity($contentAddress['city']);
        $address->setState(ArrayUtil::get('state', $contentAddress));
        $address->setCountryDetails($addressCountryDB);

        // Create Billing Address
        $contentBillingAddress = $content->get('billing_address');
        $billingAddress = new BillingAddress();
        $billingAddress->setStreetName($contentBillingAddress['street_name']);
        $billingAddress->setAddressNumber($contentBillingAddress['address_number']);


        $billingAddressCountry = ArrayUtil::get('country', $contentBillingAddress);        if (empty($billingAddressCountry)) {
            return ResultUtil::errorResult($this->translateUcFirstLower('BILLING ADDRESS COUNTRY CANNOT BE EMPTY'), Response::HTTP_PRECONDITION_REQUIRED);
        }

        if(isset($contentBillingAddress['suffix'])) {
            $billingAddress->setAddressNumberSuffix($contentBillingAddress['suffix']);
        }

        $billingAddress->setPostalCode($contentBillingAddress['postal_code']);
        $billingAddress->setCity($contentBillingAddress['city']);
        $billingAddress->setState(ArrayUtil::get('state', $contentBillingAddress));
        $billingAddress->setCountryDetails($this->getCountryByName($billingAddressCountry));

        // Create Company
        $company = new Company();
        $company->setCompanyName($content->get('company_name'));
        $company->setTelephoneNumber($content->get('telephone_number'));
        $company->setCompanyRelationNumber($companyRelationNumber);

        $company->setVatNumber($content->get('vat_number'));
        $company->setChamberOfCommerceNumber($content->get('chamber_of_commerce_number'));
        $company->setAnimalHealthSubscription($content->get('animal_health_subscription'));

        if (InvoiceSetting::IS_ACTIVE) {
            $company->setTwinfieldOfficeCode($content->get("twinfield_administration_code"));
            $company->setDebtorNumber($content->get('debtor_number'));
        }

        if($content->get('subscription_date')) {
            $company->setSubscriptionDate(TimeUtil::getDayOfDateTime(new \DateTime($content->get('subscription_date'))));
        }
        $company->setIsActive(true);
        $company->setOwner($owner);
        $company->setAddress($address);
        $company->setBillingAddress($billingAddress);
        $company->setIsRevealHistoricAnimals(true);

        // Create Location
        $locations = new ArrayCollection();
        $contentLocations = $content->get('locations');

        foreach ($contentLocations as $contentLocation) {
            $ubn = $contentLocation['ubn'];

            $this->validateUbnFormat($ubn);
            $location = $this->findActiveLocationByUbn($ubn);

            if($location) {
                throw new PreconditionFailedHttpException($this->translateUcFirstLower('THIS UBN IS ALREADY REGISTERED IN ANOTHER COMPANY. UBN HAS TO BE UNIQUE.').' '.$ubn);
            }

            // Create Location Address
            $contentLocationAddress = $contentLocation['address'];

            $locationAddressCountry = ArrayUtil::get('country', $contentLocationAddress);        if (empty($locationAddressCountry)) {
                return ResultUtil::errorResult($this->translateUcFirstLower('LOCATION ADDRESS COUNTRY CANNOT BE EMPTY'), Response::HTTP_PRECONDITION_REQUIRED);
            }

            $locationAddress = new LocationAddress();
            $locationAddress->setStreetName($contentLocationAddress['street_name']);
            $locationAddress->setAddressNumber($contentLocationAddress['address_number']);

            if(isset($contentLocationAddress['suffix'])) {
                $locationAddress->setAddressNumberSuffix($contentLocationAddress['suffix']);
            }

            $locationAddress->setPostalCode($contentLocationAddress['postal_code']);
            $locationAddress->setCity($contentLocationAddress['city']);
            $locationAddress->setState(ArrayUtil::get('state', $contentLocationAddress));
            $locationAddress->setCountryDetails($this->getCountryByName($locationAddressCountry));

            $location = new Location();
            $location->setUbn($ubn);
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

        // Create TwinField entry
        $this->externalProviderCustomerService->createOrEditCustomer($content, $addressCountryDB->getCode());

        $log = ActionLogWriter::createCompany($this->getManager(), $content, $company, $admin);

        // Send Email with passwords to Owner & Users
        $password = AuthService::persistNewPassword($this->encoder, $this->getManager(), $company->getOwner());
        $this->emailService->emailNewPasswordToPerson($company->getOwner(), $password, true);

        foreach ($company->getCompanyUsers() as $user) {
            $password = AuthService::persistNewPassword($this->encoder, $this->getManager(), $user);
            $this->emailService->emailNewPasswordToPerson($user, $password, true);
        }

        //Update all LocationOfBirths of Animals, for locations belonging to this company
        //This information is necessary to show the most up to date information on the PedigreeCertificates
        $this->getManager()->getRepository(Animal::class)->updateLocationOfBirthByCompany($company);

        $this->getCacheService()->delete(UbnService::getAllUbnCacheIds());

        return ResultUtil::successResult(['company_id' => $company->getCompanyId()]);
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
        $this->activateFilter(ActiveInvoiceFilter::NAME);
        $company = $this->getManager()->getRepository(Company::class)->findOneByCompanyId($companyId);
        $this->deactivateFilter(ActiveInvoiceFilter::NAME);

        return ResultUtil::successResult($this->getBaseSerializer()->getDecodedJson($company, [JmsGroup::DOSSIER]));
    }


    /**
     * @param Request $request
     * @param $companyId
     * @return JsonResponse
     * @throws Exception
     */
    public function updateCompany(Request $request, $companyId)
    {
        // Validation if user is an admin
        $admin = $this->getEmployee();
        if (!AdminValidator::isAdmin($admin, AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }
        // Validate content
        $content = RequestUtil::getContentAsArrayCollection($request);
        // TODO VALIDATE CONTENT

        // Get Company
        /** @var Company $company */
        $company = $this->getManager()->getRepository(Company::class)->findOneByCompanyId($companyId);

        // Update Owner
        $contentOwner = $content->get('owner');

        $emailAddressOwner = $contentOwner['email_address'];

        /** @var Client $owner */
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
            $tags = $owner->getTags();
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

            // Change the owner of the tags
            /** @var Tag $tag */
            foreach ($tags as $tag) {
                $tag->getId();
                $owner->addTag($tag);
                $tag->setOwner($owner);
                $this->getManager()->persist($tag);
            }

            $this->getManager()->persist($owner);
            $this->getManager()->flush();
        }

        // Update Address
        $address = $company->getAddress();
        $contentAddress = $content->get('address');

        $addressCountry = ArrayUtil::get('country', $contentAddress);
        if (empty($addressCountry)) {
            return ResultUtil::errorResult($this->translateUcFirstLower('ADDRESS COUNTRY CANNOT BE EMPTY'), Response::HTTP_PRECONDITION_REQUIRED);
        }

        $address->setStreetName($contentAddress['street_name']);
        $address->setAddressNumber($contentAddress['address_number']);

        if(isset($contentAddress['suffix'])) {
            $address->setAddressNumberSuffix($contentAddress['suffix']);
        } else {
            $address->setAddressNumberSuffix('');
        }

        $address->setPostalCode($contentAddress['postal_code']);
        $address->setCity($contentAddress['city']);
        $address->setState(ArrayUtil::get('state', $contentAddress));
        $countryName = ArrayUtil::get('country', $contentAddress, $addressCountry);
        $address->setCountryDetails($this->getCountryByName($countryName));

        // Update Billing Address
        $billingAddress = $company->getBillingAddress();
        $contentBillingAddress = $content->get('billing_address');

        $billingAddressCountry = ArrayUtil::get('country', $contentBillingAddress);        if (empty($billingAddressCountry)) {
        return ResultUtil::errorResult($this->translateUcFirstLower('BILLING ADDRESS COUNTRY CANNOT BE EMPTY'), Response::HTTP_PRECONDITION_REQUIRED);
        }

        $billingAddress->setStreetName($contentBillingAddress['street_name']);
        $billingAddress->setAddressNumber($contentBillingAddress['address_number']);

        if(isset($contentBillingAddress['suffix'])) {
            $billingAddress->setAddressNumberSuffix($contentBillingAddress['suffix']);
        } else {
            $billingAddress->setAddressNumberSuffix('');
        }

        $billingAddress->setPostalCode($contentBillingAddress['postal_code']);
        $billingAddress->setCity($contentBillingAddress['city']);
        $billingAddress->setState(ArrayUtil::get('state', $contentBillingAddress));
        $billingAddress->setCountryDetails($this->getCountryByName($billingAddressCountry));

        // Update Company
        $company->setCompanyName($content->get('company_name'));
        $company->setTelephoneNumber($content->get('telephone_number'));

        $companyRelationNumber = $content->get('company_relation_number');
        $this->validateIfRelationNumberKeeperIsNotNull($companyRelationNumber);
        $company->setCompanyRelationNumber($companyRelationNumber);

        $company->setVatNumber($content->get('vat_number'));
        $company->setChamberOfCommerceNumber($content->get('chamber_of_commerce_number'));

        if (InvoiceSetting::IS_ACTIVE) {
            $company->setTwinfieldOfficeCode($content->get("twinfield_administration_code"));
            $company->setDebtorNumber($content->get('debtor_number'));
        }

        if ($company->getAnimalHealthSubscription() != $content->get('animal_health_subscription')) {
            $company->setAnimalHealthSubscription($content->get('animal_health_subscription'));
            AdminActionLogWriter::updateAnimalHealthSubscription($this->getManager(), $admin, $company);
        }

        if($content->get('subscription_date')) {
            $company->setSubscriptionDate(TimeUtil::getDayOfDateTime(new \DateTime($content->get('subscription_date'))));
        }

        $company->getOwner()->setRelationNumberKeeper($companyRelationNumber);

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
        foreach($contentLocations as $contentLocation) {
            $ubn = trim($contentLocation['ubn']);
            $locationId = ArrayUtil::get('location_id', $contentLocation);

            $this->validateUbnFormat($ubn);
            $locationByUbn = $this->findActiveLocationByUbn($ubn);
            $location = $this->findLocationByLocationId($locationId);

            if ($locationByUbn && $location &&
                $locationByUbn->getLocationId() !== $location->getLocationId()
            ) {
                if ($locationByUbn->getOwner()->getId() !== $location->getOwner()->getId()) {
                    throw new PreconditionFailedHttpException($this->translateUcFirstLower('THIS UBN IS ALREADY REGISTERED IN ANOTHER COMPANY. UBN HAS TO BE UNIQUE.').' '.$ubn);
                } else {
                    throw new PreconditionFailedHttpException($this->translateUcFirstLower('SWITCHING UBN NUMBERS BETWEEN TWO LOCATIONS IS NOT ALLOWED').'.');
                }
            }

            if($location) {

                $location->setUbn($ubn);
                $locationAddress = $location->getAddress();
                $contentLocationAddress = $contentLocation['address'];

                $locationAddressCountry = ArrayUtil::get('country', $contentLocationAddress);        if (empty($locationAddressCountry)) {
                    return ResultUtil::errorResult($this->translateUcFirstLower('LOCATION ADDRESS COUNTRY CANNOT BE EMPTY'), Response::HTTP_PRECONDITION_REQUIRED);
                }

                $locationAddress->setStreetName($contentLocationAddress['street_name']);
                $locationAddress->setAddressNumber($contentLocationAddress['address_number']);

                if(isset($contentLocationAddress['suffix'])) {
                    $locationAddress->setAddressNumberSuffix($contentLocationAddress['suffix']);
                } else {
                    $locationAddress->setAddressNumberSuffix('');
                }

                $locationAddress->setPostalCode($contentLocationAddress['postal_code']);
                $locationAddress->setCity($contentLocationAddress['city']);
                $locationAddress->setState(ArrayUtil::get('state', $contentLocationAddress));
                $locationAddress->setCountryDetails($this->getCountryByName($locationAddressCountry));

                $this->getManager()->persist($location);
                $this->getManager()->flush();
            } else {

                $contentLocationAddress = $contentLocation['address'];

                $locationAddressCountry = ArrayUtil::get('country', $contentLocationAddress);        if (empty($locationAddressCountry)) {
                    return ResultUtil::errorResult($this->translateUcFirstLower('LOCATION ADDRESS COUNTRY CANNOT BE EMPTY'), Response::HTTP_PRECONDITION_REQUIRED);
                }

                $locationAddress = new LocationAddress();
                $locationAddress->setStreetName($contentLocationAddress['street_name']);
                $locationAddress->setAddressNumber($contentLocationAddress['address_number']);

                if(isset($contentLocationAddress['suffix'])) {
                    $locationAddress->setAddressNumberSuffix($contentLocationAddress['suffix']);
                }

                $locationAddress->setPostalCode($contentLocationAddress['postal_code']);
                $locationAddress->setCity($contentLocationAddress['city']);
                $locationAddress->setState(ArrayUtil::get('state', $contentLocationAddress));
                $locationAddress->setCountryDetails($this->getCountryByName($locationAddressCountry));

                $location = new Location();
                $location->setUbn($ubn);
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
            $this->emailService->emailNewPasswordToPerson($user, $password, true);
        }

        $log = ActionLogWriter::editCompany($this->getManager(), $content, $company, $admin);

        $this->getManager()->getRepository(Animal::class)->updateLocationOfBirthByCompany($company);

        $this->getCacheService()->delete(UbnService::getAllUbnCacheIds());

        return ResultUtil::successResult(['company_id' => $company->getCompanyId()]);
    }


    /**
     * @param $ubn
     */
    private function validateUbnFormat($ubn): void
    {
        if (!Validator::hasValidUbnFormat($ubn)) {
            throw new PreconditionFailedHttpException($this->translateUcFirstLower('UBN IS NOT A VALID NUMBER').': '.$ubn);
        }
    }


    /**
     * @param string $ubn
     * @return Location|null
     */
    private function findActiveLocationByUbn($ubn): ?Location
    {
        return $this->getManager()->getRepository(Location::class)
            ->findOneBy(['ubn' => $ubn, 'isActive' => true]);
    }


    /**
     * @param string $locationId
     * @return Location|null
     */
    private function findLocationByLocationId($locationId): ?Location
    {
        if (empty($locationId)) {
            return null;
        }

        $location = $this->getManager()->getRepository(Location::class)->findOneBy(['locationId' => $locationId]);
        if (!$location) {
            throw new PreconditionFailedHttpException('NO LOCATION FOUND FOR LOCATION_ID: '.$locationId);
        }
        return $location;
    }


    /**
     * @param Request $request
     * @param $companyId
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function setCompanyActiveStatus(Request $request, $companyId)
    {
        // Validation if user is an admin
        $admin = $this->getEmployee();
        if (!AdminValidator::isAdmin($admin, AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        // Validate content
        $content = RequestUtil::getContentAsArrayCollection($request);
        // TODO VALIDATE CONTENT

        // Get Content
        $isActive = $content->get('is_active');

        // Get Company
        /** @var Company $company */
        $company = $this->getManager()->getRepository(Company::class)->findOneByCompanyId($companyId);
        $company->setIsActive($isActive);

        if ($isActive) {
            // Reactivate all owned locations if company is reactivated
            /** @var Location[]|array $activeLocations */
            $activeLocations = [];
            $reactivatedLocationsCount = 0;
            foreach ($company->getLocations() as $location) {
                $activeLocation = $this->getManager()->getRepository(Location::class)->findOneByActiveUbn($location->getUbn());
                if (!$activeLocation) {
                    // Only reactivate locations for which there is no other active location with the same UBN
                    $location->setIsActive($isActive);
                    $this->getManager()->persist($location);
                    $reactivatedLocationsCount++;
                } else {
                    $activeLocations[] = $activeLocation;
                }
            }

            if ($reactivatedLocationsCount == 0) {
                $errorMessage = "Voor dit bedrijf kon geen enkele bijbehorende locatie worden heractiveerd ".
                "omdat hetzelde UBN al bestaat bij andere bedrijven: ";
                $prefix = "";
                foreach ($activeLocations as $location) {
                    $errorMessage .= $prefix . "UBN " . $location->getUbn() . " bij bedrijf ".$location->getCompanyName();
                    $prefix = ", ";
                }
                throw new BadRequestHttpException($errorMessage);
            }
        } else {
            // Deactivate all owned locations if company is deactivated
            foreach ($company->getLocations() as $location) {
                $location->setIsActive($isActive);
                $this->getManager()->persist($location);
            }
        }

        $this->getManager()->persist($company);
        $this->getManager()->flush();

        $log = ActionLogWriter::activeStatusCompany($this->getManager(), $isActive, $company, $admin);

        $this->getCacheService()->delete(UbnService::getAllUbnCacheIds());

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
        $breederNumbers = $this->getManager()->getRepository(PedigreeRegisterRegistration::class)
            ->getCompanyBreederNumbersWithPedigreeRegisterAbbreviations($company, $this->getLogger());

        // Generate Company Details
        $result = CompanyOutput::createCompanyDetails($company, $breederNumbers);

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
        $content = RequestUtil::getContentAsArrayCollection($request);
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


    /**
     * @return JsonResponse
     */
    public function getCompanyInvoiceDetails(){

        $this->activateFilter(ActiveCompanyFilter::NAME);
        $this->activateFilter(ActiveLocationFilter::NAME);
        $companies = $this->getManager()->getRepository(Company::class)->findBy(['isActive' => true]);
        $this->deactivateFilter(ActiveCompanyFilter::NAME);
        $this->deactivateFilter(ActiveLocationFilter::NAME);

        return ResultUtil::successResult($this->getBaseSerializer()->getDecodedJson($companies, JmsGroup::INVOICE));
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getCompaniesByName(Request $request){
        $name = $request->query->get('company_name');

        /** @var QueryBuilder $qb */
        $qb = $this->getManager()->getRepository(Company::class)
            ->createQueryBuilder('qb')
            ->where('LOWER(qb.companyName) LIKE :company_name')
            ->andWhere('qb.isActive = true')
            ->setParameter('company_name', '%'.strtolower($name).'%');

        $companies = $qb->getQuery()->getResult();
        return ResultUtil::successResult($this->getBaseSerializer()->getDecodedJson($companies, JmsGroup::INVOICE));
    }

}
