<?php

namespace AppBundle\Controller;

use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Client;
use AppBundle\Entity\Company;
use AppBundle\Entity\CompanyAddress;
use AppBundle\Entity\CompanyNote;
use AppBundle\Entity\BillingAddress;
use AppBundle\Entity\CompanyRepository;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationAddress;
use AppBundle\Output\CompanyNoteOutput;
use AppBundle\Output\CompanyOutput;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\ArrayUtil;
use AppBundle\Validation\AdminValidator;
use AppBundle\Validation\CompanyValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Doctrine\ORM\Query;

/**
 * @Route("/api/v1/companies")
 */
class CompanyAPIController extends APIController
{
    const DEFAULT_COUNTRY = '';

    /**
     * @param Request $request the request object
     *
     * @return JsonResponse
     * @Route("")
     * @Method("GET")
     */
    public function getCompanies(Request $request)
    {
        // Validation if user is an admin
        $admin = $this->getEmployee();
        $adminValidator = new AdminValidator($admin);

        if (!$adminValidator->getIsAccessGranted()) {
            return $adminValidator->createJsonErrorResponse();
        }

        // Get all companies
        $em = $this->getDoctrine()->getManager();
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

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $result), 200);
    }

    /**
     * @param Request $request the request object
     *
     * @return JsonResponse
     * @Route("")
     * @Method("POST")
     */
    public function createCompany(Request $request)
    {
        // Validation if user is an admin
        $admin = $this->getEmployee();
        $adminValidator = new AdminValidator($admin);

        if (!$adminValidator->getIsAccessGranted()) {
            return $adminValidator->createJsonErrorResponse();
        }

        // Validate content
        $content = $this->getContentAsArray($request);
        /** @var ObjectManager $em */
        $em = $this->getDoctrine()->getManager();

        // TODO VALIDATE CONTENT
        $companyValidator = new CompanyValidator($em, $content);
        if(!$companyValidator->getIsInputValid()) { return $companyValidator->createJsonResponse(); }

        // Create Owner
        $contentUsers = $content->get('users');
        $contentOwner = $content->get('owner');

        $emailAddressOwner = $contentOwner['email_address'];
        if(CompanyValidator::doesClientAlreadyExist($em, $emailAddressOwner)) {
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
        $repository = $this->getDoctrine()->getRepository(Constant::LOCATION_REPOSITORY);

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
            if(CompanyValidator::doesClientAlreadyExist($em, $emailAddressUser)) {
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
                $this->getDoctrine()->getManager()->persist($user);
            }
        }

        // Save to Database
        $this->getDoctrine()->getManager()->persist($company);
        $this->getDoctrine()->getManager()->flush();

        $log = ActionLogWriter::createCompany($this->getManager(), $owner, $admin, $request->getContent());

        // Send Email with passwords to Owner & Users
        $password = $this->persistNewPassword($company->getOwner());
        $this->emailNewPasswordToPerson($company->getOwner(), $password, false, true);

        foreach ($company->getCompanyUsers() as $user) {
            $password = $this->persistNewPassword($user);
            $this->emailNewPasswordToPerson($user, $password, false, true);
        }

        //Update all LocationOfBirths of Animals, for locations belonging to this company
        //This information is necessary to show the most up to date information on the PedigreeCertificates
        /** @var AnimalRepository $animalRepository */
        $animalRepository = $this->getDoctrine()->getRepository(Animal::class);
        $animalRepository->updateLocationOfBirthByCompany($company);

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => 'ok'), 200);
    }

    /**
     * @param Request $request   the request object
     * @param Company $companyId
     *
     * @return JsonResponse
     * @Route("/{companyId}")
     * @Method("GET")
     */
    public function getCompany(Request $request, $companyId)
    {
        // Validation if user is an admin
        $admin = $this->getEmployee();
        $adminValidator = new AdminValidator($admin);

        if (!$adminValidator->getIsAccessGranted()) {
            return $adminValidator->createJsonErrorResponse();
        }

        // Get Company
        $repository = $this->getDoctrine()->getRepository(Constant::COMPANY_REPOSITORY);
        $company = $repository->findOneByCompanyId($companyId);

        // Generate Company Details
        $result = CompanyOutput::createCompany($company);

        return new \AppBundle\Component\HttpFoundation\JsonResponse(array(Constant::RESULT_NAMESPACE => $result), 200);
    }

    /**
     * @var Company $company
     * @param Request $request the request object
     *
     * @return JsonResponse
     * @Route("/{companyId}")
     * @Method("PUT")
     */
    public function UpdateCompany(Request $request, $companyId)
    {
        // Validation if user is an admin
        $admin = $this->getEmployee();
        $adminValidator = new AdminValidator($admin);

        if (!$adminValidator->getIsAccessGranted()) {
            return $adminValidator->createJsonErrorResponse();
        }
        // Validate content
        $content = $this->getContentAsArray($request);
        // TODO VALIDATE CONTENT

        // Get Company
        $repository = $this->getDoctrine()->getRepository(Constant::COMPANY_REPOSITORY);
        $company = $repository->findOneByCompanyId($companyId);

        /**
         * @var Company $company
         * @var Client $owner
         */
        
        // Update Owner
        $contentOwner = $content->get('owner');

        $emailAddressOwner = $contentOwner['email_address'];
        $repository = $this->getDoctrine()->getRepository(Constant::CLIENT_REPOSITORY);
        $owner = $repository->findOneBy(array('emailAddress' => $emailAddressOwner, 'isActive' => true));

        if(isset($contentOwner['person_id'])) {
            $contentPersonId = $contentOwner['person_id'];

            if($owner && $owner->getPersonId() != $contentPersonId) {
                return CompanyValidator::emailAddressIsInUseErrorMessage($emailAddressOwner);
            }

            $repository = $this->getDoctrine()->getRepository(Constant::CLIENT_REPOSITORY);
            $owner = $repository->findOneByPersonId($contentPersonId);

            $owner->setFirstName($contentOwner['first_name']);
            $owner->setLastName($contentOwner['last_name']);
            $owner->setEmailAddress($contentOwner['email_address']);

            $this->getDoctrine()->getManager()->persist($owner);
            $this->getDoctrine()->getManager()->flush();
        } else {
            if($owner) {
                return CompanyValidator::emailAddressIsInUseErrorMessage($emailAddressOwner);
            }

            $owner = $company->getOwner();
            $owner->setIsActive(false);
            $this->getDoctrine()->getManager()->persist($owner);
            $this->getDoctrine()->getManager()->flush();

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
            $repository = $this->getDoctrine()->getRepository(Constant::LOCATION_REPOSITORY);
            $location = $repository->findOneByLocationId($contentLocationId);

            if ($location) {
                $location->setIsActive(false);
                $this->getDoctrine()->getManager()->persist($location);
                $this->getDoctrine()->getManager()->flush();
            }
        }

        // Updated Locations
        $contentLocations = $content->get('locations');
        $repository = $this->getDoctrine()->getRepository(Constant::LOCATION_REPOSITORY);
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

                $this->getDoctrine()->getManager()->persist($location);
                $this->getDoctrine()->getManager()->flush();
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
                $repository = $this->getDoctrine()->getRepository(Constant::CLIENT_REPOSITORY);
                $user = $repository->findOneByPersonId($contentPersonId);

                if ($user) {
                    /**
                     * @var Client $user
                     */
                    $user->setIsActive(false);
                    $this->getDoctrine()->getManager()->persist($user);
                    $this->getDoctrine()->getManager()->flush();
                }
            }
        }

        // Updated Users
        $contentUsers = $content->get('users');
        $newUsers = array();
        $repository = $this->getDoctrine()->getRepository(Constant::CLIENT_REPOSITORY);

        foreach($contentUsers as $contentUser) {
            $emailAddressUser = $contentUser['email_address'];
            $user = $repository->findOneBy(array('emailAddress' => $emailAddressUser, 'isActive' => true));

            if(isset($contentUser['person_id'])) {
                $contentPersonId = $contentUser['person_id'];

                if($user && $user->getPersonId() != $contentPersonId) {
                    return CompanyValidator::emailAddressIsInUseErrorMessage($emailAddressUser);
                }

                $repository = $this->getDoctrine()->getRepository(Constant::CLIENT_REPOSITORY);
                $user = $repository->findOneByPersonId($contentPersonId);

                $user->setFirstName($contentUser['first_name']);
                $user->setLastName($contentUser['last_name']);
                $user->setEmailAddress($contentUser['email_address']);

                $this->getDoctrine()->getManager()->persist($user);
                $this->getDoctrine()->getManager()->flush();
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

        $this->getDoctrine()->getManager()->persist($company);
        $this->getDoctrine()->getManager()->flush();

        foreach ($newUsers as $user) {
            $password = $this->persistNewPassword($user);
            $this->emailNewPasswordToPerson($user, $password, false, true);
        }

        $log = ActionLogWriter::editCompany($this->getManager(), $owner, $admin, $request->getContent());

        /** @var AnimalRepository $animalRepository */
        $animalRepository = $this->getDoctrine()->getRepository(Animal::class);
        $animalRepository->updateLocationOfBirthByCompany($company);

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => 'ok'), 200);
    }

    /**
     * @param Request $request   the request object
     * @param Company $companyId
     *
     * @return JsonResponse
     * @Route("/{companyId}/status")
     * @Method("PUT")
     */
    public function setCompanyInactive(Request $request, $companyId)
    {
        // Validation if user is an admin
        $admin = $this->getEmployee();
        $adminValidator = new AdminValidator($admin);

        if (!$adminValidator->getIsAccessGranted()) {
            return $adminValidator->createJsonErrorResponse();
        }

        // Validate content
        $content = $this->getContentAsArray($request);
        // TODO VALIDATE CONTENT

        // Get Content
        $isActive = $content->get('is_active');

        // Get Company
        $repository = $this->getDoctrine()->getRepository(Company::class);
        /** @var Company $company */
        $company = $repository->findOneByCompanyId($companyId);

        // Set Company inactive
        $company->setIsActive($isActive);
        $this->getDoctrine()->getManager()->persist($company);
        $this->getDoctrine()->getManager()->flush();

        $log = ActionLogWriter::activeStatusCompany($this->getManager(), $isActive, $company, $admin);

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => 'ok'), 200);
    }

    /**
     * @param Request $request   the request object
     * @param String  $companyId
     *
     * @return JsonResponse
     * @Route("/{companyId}/details")
     * @Method("GET")
     */
    public function GetCompanyDetails(Request $request, $companyId)
    {
        // Validation if user is an admin
        $admin = $this->getEmployee();
        $adminValidator = new AdminValidator($admin);

        if (!$adminValidator->getIsAccessGranted()) {
            return $adminValidator->createJsonErrorResponse();
        }

        // Get Company
        $repository = $this->getDoctrine()->getRepository(Constant::COMPANY_REPOSITORY);
        $company = $repository->findOneByCompanyId($companyId);

        // Generate Company Details
        $result = CompanyOutput::createCompanyDetails($company);

        return new \AppBundle\Component\HttpFoundation\JsonResponse(array(Constant::RESULT_NAMESPACE => $result), 200);
    }

    /**
     * @param Request $request   the request object
     * @param String  $companyId
     *
     * @return JsonResponse
     * @Route("/{companyId}/notes")
     * @Method("GET")
     */
    public function GetCompanyNotes(Request $request, $companyId)
    {
        // Validation if user is an admin
        $admin = $this->getEmployee();
        $adminValidator = new AdminValidator($admin);

        if (!$adminValidator->getIsAccessGranted()) {
            return $adminValidator->createJsonErrorResponse();
        }

        // Get Company
        $repository = $this->getDoctrine()->getRepository(Constant::COMPANY_REPOSITORY);
        $company = $repository->findOneByCompanyId($companyId);

        /*
         * @var $company Company
         */
        // Get Company Notes
        $result = CompanyNoteOutput::createNotes($company);

        return new \AppBundle\Component\HttpFoundation\JsonResponse(array(Constant::RESULT_NAMESPACE => $result), 200);
    }

    /**
     * @param Request $request   the request object
     * @param String  $companyId
     *
     * @return JsonResponse
     * @Route("/{companyId}/notes")
     * @Method("POST")
     */
    public function CreateCompanyNotes(Request $request, $companyId)
    {
        // Validation if user is an admin
        $admin = $this->getEmployee();
        $adminValidator = new AdminValidator($admin);

        if (!$adminValidator->getIsAccessGranted()) {
            return $adminValidator->createJsonErrorResponse();
        }

        // Validate content
        $content = $this->getContentAsArray($request);
        // TODO VALIDATE CONTENT

        // Get Company
        $repository = $this->getDoctrine()->getRepository(Constant::COMPANY_REPOSITORY);
        $company = $repository->findOneByCompanyId($companyId);

        // Create Note
        $note = new CompanyNote();
        $note->setCreationDate(new \DateTime());
        $note->setCreator($admin);
        $note->setCompany($company);
        $note->setNote($content['note']);

        $this->getDoctrine()->getManager()->persist($note);
        $this->getDoctrine()->getManager()->flush();

        $result = CompanyNoteOutput::createNoteResponse($note);

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $result), 200);
    }

}
