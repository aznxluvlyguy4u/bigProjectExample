<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use AppBundle\Entity\Company;
use AppBundle\Entity\CompanyAddress;
use AppBundle\Entity\BillingAddress;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationAddress;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Output\CompanyOutput;
use AppBundle\Validation\AdminValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * @Route("/api/v1/company")
 */
class CompanyAPIController extends APIController {

    /**
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("")
     * @Method("GET")
     */
    public function getCompanies(Request $request)
    {
        // Validation if user is an admin
        $admin = $this->getAuthenticatedEmployee($request);
        $adminValidator = new AdminValidator($admin);

        if(!$adminValidator->getIsAccessGranted()) {
            return $adminValidator->createJsonErrorResponse();
        }

        // Get all companies
        $repository = $this->getDoctrine()->getRepository(Constant::COMPANY_REPOSITORY);
        $companies = $repository->findAll();

        // Generate Company Overview
        $result = CompanyOutput::createCompaniesOverview($companies);

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $result), 200);
    }

    /**
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("")
     * @Method("POST")
     */
    public function createCompany(Request $request)
    {
        // Validation if user is an admin
        $admin = $this->getAuthenticatedEmployee($request);
        $adminValidator = new AdminValidator($admin);

        if(!$adminValidator->getIsAccessGranted()) {
            return $adminValidator->createJsonErrorResponse();
        }

        // Validate content
        $content = $this->getContentAsArray($request);
        // TODO VALIDATE CONTENT

        // Create Owner
        $contentOwner = $content->get('owner');
        $owner = new Client();
        $owner->setFirstName($contentOwner['first_name']);
        $owner->setLastName($contentOwner['last_name']);
        $owner->setEmailAddress($contentOwner['email_address']);
        $owner->setObjectType('Client');
        $owner->setIsActive(true);

        // Create Address
        $contentAddress = $content->get('address');
        $address = new CompanyAddress();
        $address->setStreetName($contentAddress['street_name']);
        $address->setAddressNumber($contentAddress['address_number']);
        $address->setAddressNumberSuffix($contentAddress['suffix']);
        $address->setPostalCode($contentAddress['postal_code']);
        $address->setCity($contentAddress['city']);
        $address->setState($contentAddress['state']);

        // Create Billing Address
        $contentBillingAddress = $content->get('billing_address');
        $billingAddress = new BillingAddress();
        $billingAddress->setStreetName($contentBillingAddress['street_name']);
        $billingAddress->setAddressNumber($contentBillingAddress['address_number']);
        $billingAddress->setAddressNumberSuffix($contentBillingAddress['suffix']);
        $billingAddress->setPostalCode($contentBillingAddress['postal_code']);
        $billingAddress->setCity($contentBillingAddress['city']);
        $billingAddress->setState($contentBillingAddress['state']);

        // Create Company
        $company = new Company();
        $company->setCompanyName($content->get('company_name'));
        $company->setTelephoneNumber($content->get('telephone_number'));
        $company->setCompanyRelationNumber($content->get('company_relation_number'));
        $company->setDebtorNumber($content->get('debtor_number'));
        $company->setVatNumber($content->get('vat_number'));
        $company->setChamberOfCommerceNumber($content->get('chamber_of_commerce_number'));
        $company->setNotes($content->get('notes'));
        $company->setOwner($owner);
        $company->setAddress($address);
        $company->setBillingAddress($billingAddress);

        // Create Location
        $contentLocations = $content->get('locations');

        foreach ($contentLocations as $contentLocation) {
            // Create Location Address
            $contentLocationAddress = $contentLocation['address'];
            $locationAddress = new LocationAddress();
            $locationAddress->setStreetName($contentLocationAddress['street_name']);
            $locationAddress->setAddressNumber($contentLocationAddress['address_number']);
            $locationAddress->setAddressNumberSuffix($contentLocationAddress['suffix']);
            $locationAddress->setPostalCode($contentLocationAddress['postal_code']);
            $locationAddress->setCity($contentLocationAddress['city']);
            $locationAddress->setState($contentLocationAddress['state']);

            $location = new Location();
            $location->setUbn($contentLocation['ubn']);
            $location->setAddress($locationAddress);

            $company->addLocation($location);
        }

        // Create Users
        $contentUsers = $content->get('users');

        foreach ($contentUsers as $contentUser) {
            $user = new Client();
            $user->setFirstName($contentUser['first_name']);
            $user->setLastName($contentUser['last_name']);
            $user->setEmailAddress($contentUser['email_address']);
            $user->setObjectType('Client');
            $user->setIsActive(true);

            $company->addCompanyUser($user);

            // TODO GENERATE TOKEN
            // TODO GENERATE PASSWORD
            // TODO EMAIL PASSWORD
        }

        // TODO OWNER -> GENERATE TOKEN
        // TODO OWNER -> GENERATE PASSWORD
        // TODO OWNER -> EMAIL PASSWORD

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => 'ok'), 200);
    }

    /**
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("")
     * @Method("PUT")
     */
    public function UpdateCompany(Request $request)
    {
        // Validation if user is an admin
        $admin = $this->getAuthenticatedEmployee($request);
        $adminValidator = new AdminValidator($admin);

        if(!$adminValidator->getIsAccessGranted()) {
            return $adminValidator->createJsonErrorResponse();
        }

        // Validate content
        $content = $this->getContentAsArray($request);
        // TODO VALIDATE CONTENT

        // Get Company
        $contentCompanyId = $content->get('company_id');
        $repository = $this->getDoctrine()->getRepository(Constant::COMPANY_REPOSITORY);
        $company = $repository->find($contentCompanyId);

        // TODO UPDATE COMPANY

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => 'ok'), 200);
    }

    /**
     * @param Request $request the request object
     * @param String $companyId
     * @return JsonResponse
     * @Route("/details/{companyId}")
     * @Method("GET")
     */
    public function GetCompanyDetails(Request $request, $companyId)
    {
        // Validation if user is an admin
        $admin = $this->getAuthenticatedEmployee($request);
        $adminValidator = new AdminValidator($admin);

        if(!$adminValidator->getIsAccessGranted()) {
            return $adminValidator->createJsonErrorResponse();
        }

        // Get Company
        $repository = $this->getDoctrine()->getRepository(Constant::COMPANY_REPOSITORY);
        $company = $repository->find($companyId);

        // Generate Company Details
        $result = CompanyOutput::createCompanyDetails($company);

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $result), 200);
    }
}