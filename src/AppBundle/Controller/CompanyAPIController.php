<?php

namespace AppBundle\Controller;

use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use AppBundle\Entity\Company;
use AppBundle\Entity\CompanyAddress;
use AppBundle\Entity\CompanyNote;
use AppBundle\Entity\BillingAddress;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationAddress;
use AppBundle\Output\CompanyNoteOutput;
use AppBundle\Output\CompanyOutput;
use AppBundle\Validation\AdminValidator;
use Doctrine\Common\Collections\ArrayCollection;
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
        $admin = $this->getAuthenticatedEmployee($request);
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
        $admin = $this->getAuthenticatedEmployee($request);
        $adminValidator = new AdminValidator($admin);

        if (!$adminValidator->getIsAccessGranted()) {
            return $adminValidator->createJsonErrorResponse();
        }

        // Validate content
        $content = $this->getContentAsArray($request);
        // TODO VALIDATE CONTENT

        // Create Owner
        $contentOwner = $content->get('owner');

        $repository = $this->getDoctrine()->getRepository(Constant::CLIENT_REPOSITORY);
        $owner = $repository->findOneBy(array('emailAddress' => $contentOwner['email_address'], 'isActive' => true));

        if($owner) {
            return new JsonResponse(
                array(
                    Constant::CODE_NAMESPACE => 400,
                    Constant::MESSAGE_NAMESPACE => 'THIS EMAIL IS ALREADY REGISTERED FOR ANOTHER USER. EMAIL HAS TO BE UNIQUE.',
                    'data' => $contentOwner['email_address']
                ),
                400
            );
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
        $address->setCountry('');

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
        $billingAddress->setCountry('');

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
            $locationAddress->setCountry('');

            $location = new Location();
            $location->setUbn($contentLocation['ubn']);
            $location->setAddress($locationAddress);
            $location->setCompany($company);
            $location->setIsActive(true);
            $locations->add($location);
        }

        $company->setLocations($locations);

        // Create Users
        $contentUsers = $content->get('users');
        $repository = $this->getDoctrine()->getRepository(Constant::CLIENT_REPOSITORY);

        foreach ($contentUsers as $contentUser) {
            $user = $repository->findOneBy(array('emailAddress' => $contentUser['email_address'], 'isActive' => true));

            if($user) {
                return new JsonResponse(
                    array(
                        Constant::CODE_NAMESPACE => 400,
                        Constant::MESSAGE_NAMESPACE => 'THIS EMAIL IS ALREADY REGISTERED FOR ANOTHER USER. EMAIL HAS TO BE UNIQUE.',
                        'data' => $contentUser['email_address']
                    ),
                    400
                );
            }

            $user = new Client();
            $user->setFirstName($contentUser['first_name']);
            $user->setLastName($contentUser['last_name']);
            $user->setEmailAddress($contentUser['email_address']);
            $user->setObjectType('Client');
            $user->setIsActive(true);
            $user->setEmployer($company);
        }

        // Save to Database
        $this->getDoctrine()->getEntityManager()->persist($company);
        $this->getDoctrine()->getEntityManager()->flush();

        // Send Email with passwords to Owner & Users
        $password = $this->persistNewPassword($company->getOwner());
        $this->emailNewPasswordToPerson($company->getOwner(), $password, false, true);

        foreach ($company->getCompanyUsers() as $user) {
            $password = $this->persistNewPassword($user);
            $this->emailNewPasswordToPerson($user, $password, false, true);
        }

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
        $admin = $this->getAuthenticatedEmployee($request);
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
        $admin = $this->getAuthenticatedEmployee($request);
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

        $repository = $this->getDoctrine()->getRepository(Constant::CLIENT_REPOSITORY);
        $owner = $repository->findOneBy(array('emailAddress' => $contentOwner['email_address'], 'isActive' => true));

        if(isset($contentOwner['person_id'])) {
            $contentPersonId = $contentOwner['person_id'];

            if($owner && $owner->getPersonId() != $contentPersonId) {
                return new JsonResponse(
                    array(
                        Constant::CODE_NAMESPACE => 400,
                        Constant::MESSAGE_NAMESPACE => 'THIS EMAIL IS ALREADY REGISTERED FOR ANOTHER USER. EMAIL HAS TO BE UNIQUE.',
                        'data' => $contentOwner['email_address']
                    ),
                    400
                );
            }

            $repository = $this->getDoctrine()->getRepository(Constant::CLIENT_REPOSITORY);
            $owner = $repository->findOneByPersonId($contentPersonId);

            $owner->setFirstName($contentOwner['first_name']);
            $owner->setLastName($contentOwner['last_name']);
            $owner->setEmailAddress($contentOwner['email_address']);

            $this->getDoctrine()->getEntityManager()->persist($owner);
            $this->getDoctrine()->getEntityManager()->flush();
        } else {
            if($owner) {
                return new JsonResponse(
                    array(
                        Constant::CODE_NAMESPACE => 400,
                        Constant::MESSAGE_NAMESPACE => 'THIS EMAIL IS ALREADY REGISTERED FOR ANOTHER USER. EMAIL HAS TO BE UNIQUE.',
                        'data' => $contentOwner['email_address']
                    ),
                    400
                );
            }

            $owner = $company->getOwner();
            $owner->setIsActive(false);
            $this->getDoctrine()->getEntityManager()->persist($owner);
            $this->getDoctrine()->getEntityManager()->flush();

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
        $address->setCountry('');

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
        $billingAddress->setCountry('');

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
                $this->getDoctrine()->getEntityManager()->persist($location);
                $this->getDoctrine()->getEntityManager()->flush();
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
                $locationAddress->setCountry('');

                $this->getDoctrine()->getEntityManager()->persist($location);
                $this->getDoctrine()->getEntityManager()->flush();
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
                $locationAddress->setCountry('');

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
                    $this->getDoctrine()->getEntityManager()->persist($user);
                    $this->getDoctrine()->getEntityManager()->flush();
                }
            }
        }

        // Updated Users
        $contentUsers = $content->get('users');
        $newUsers = array();
        $repository = $this->getDoctrine()->getRepository(Constant::CLIENT_REPOSITORY);

        foreach($contentUsers as $contentUser) {
            $user = $repository->findOneBy(array('emailAddress' => $contentUser['email_address'], 'isActive' => true));

            if(isset($contentUser['person_id'])) {
                $contentPersonId = $contentUser['person_id'];

                if($user && $user->getPersonId() != $contentPersonId) {
                    return new JsonResponse(
                        array(
                            Constant::CODE_NAMESPACE => 400,
                            Constant::MESSAGE_NAMESPACE => 'THIS EMAIL IS ALREADY REGISTERED FOR ANOTHER USER. EMAIL HAS TO BE UNIQUE.',
                            'data' => $contentUser['email_address']
                        ),
                        400
                    );
                }

                $repository = $this->getDoctrine()->getRepository(Constant::CLIENT_REPOSITORY);
                $user = $repository->findOneByPersonId($contentPersonId);

                $user->setFirstName($contentUser['first_name']);
                $user->setLastName($contentUser['last_name']);
                $user->setEmailAddress($contentUser['email_address']);

                $this->getDoctrine()->getEntityManager()->persist($user);
                $this->getDoctrine()->getEntityManager()->flush();
            } else {
                if($user) {
                    return new JsonResponse(
                        array(
                            Constant::CODE_NAMESPACE => 400,
                            Constant::MESSAGE_NAMESPACE => 'THIS EMAIL IS ALREADY REGISTERED FOR ANOTHER USER. EMAIL HAS TO BE UNIQUE.',
                            'data' => $contentUser['email_address']
                        ),
                        400
                    );
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

        $this->getDoctrine()->getEntityManager()->persist($company);
        $this->getDoctrine()->getEntityManager()->flush();

        foreach ($newUsers as $user) {
            $password = $this->persistNewPassword($user);
            $this->emailNewPasswordToPerson($user, $password, false, true);
        }
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
        $admin = $this->getAuthenticatedEmployee($request);
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
        $repository = $this->getDoctrine()->getRepository(Constant::COMPANY_REPOSITORY);
        $company = $repository->findOneByCompanyId($companyId);

        // Set Company inactive
        $company->setIsActive($isActive);
        $this->getDoctrine()->getEntityManager()->persist($company);
        $this->getDoctrine()->getEntityManager()->flush();

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
        $admin = $this->getAuthenticatedEmployee($request);
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
        $admin = $this->getAuthenticatedEmployee($request);
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
        $admin = $this->getAuthenticatedEmployee($request);
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

        $this->getDoctrine()->getEntityManager()->persist($note);
        $this->getDoctrine()->getEntityManager()->flush();

        $result = CompanyNoteOutput::createNoteResponse($note);

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $result), 200);
    }

    /**
     * @param Request $request the request object
     *
     * @return JsonResponse
     * @Route("/generate-ids")
     * @Method("POST")
     */
    public function generateNewCompanyIds(Request $request)
    {
        // Validation if user is an admin
        $admin = $this->getAuthenticatedEmployee($request);
        $adminValidator = new AdminValidator($admin);

        if (!$adminValidator->getIsAccessGranted()) {
            return $adminValidator->createJsonErrorResponse();
        }

        $companies = $this->getDoctrine()->getRepository(Constant::COMPANY_REPOSITORY)->findAll();

        foreach ($companies as $company) {
            /*
             * @var Company
             */
            if ($company->getCompanyId() == null || $company->getCompanyId() == '') {
                $company->setCompanyId(Utils::generateTokenCode());
                $this->getDoctrine()->getEntityManager()->persist($company);
                $this->getDoctrine()->getEntityManager()->flush();
            }
        }

        return new JsonResponse('ok', 200);
    }
}
