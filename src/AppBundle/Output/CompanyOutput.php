<?php

namespace AppBundle\Output;

use AppBundle\Component\Utils;
use AppBundle\Entity\Company;
use AppBundle\Entity\Location;
use AppBundle\Entity\Invoice;
use AppBundle\Entity\Client;
use AppBundle\Entity\Pedigree;
use Doctrine\Common\Collections\Collection;
use AppBundle\Component\Count;

class CompanyOutput
{
    /**
     * @param array $companies
     *
     * @return array
     */
    public static function createCompaniesOverview($companies)
    {
        $res = array();

        foreach ($companies as $company) {
            $users = array();
            if(sizeof($company['companyUsers']) > 0) {
                foreach($company['companyUsers'] as $user) {
                    if($user['isActive']) {
                        $users[] = array(
                            'person_id' => Utils::fillNull($user['personId']),
                            'prefix' => Utils::fillNull($user['prefix']),
                            'email_address' => Utils::fillNull($user['emailAddress']),
                            'first_name' => Utils::fillNull($user['firstName']),
                            'last_name' => Utils::fillNull($user['lastName'])
                        );
                    }
                }
            }

            $locations = array();
            if(sizeof($company['locations']) > 0) {
                foreach($company['locations'] as $location) {
                    if($location['isActive']) {
                        $locations[] = Utils::fillNull($location['ubn']);
                    }
                }
            }

            $pedigrees = array();
            if(sizeof($company['pedigrees']) > 0) {
                foreach($company['pedigrees'] as $pedigree) {
                    $pedigrees[] = Utils::fillNull($pedigree['$pedigreeCode']);
                }
            }

            $invoices = array();
            if(sizeof($company['invoices']) > 0) {
                foreach($company['invoices'] as $invoice) {

                    if($invoice['status'] == 'UNPAID') {
                        $invoices[] = $invoice;
                    };
                }
            }

            $res[] = array(
                'company_id' => Utils::fillNull($company['companyId']),
                'debtor_number' => Utils::fillNull($company['debtorNumber']),
                'company_name' => Utils::fillNull($company['companyName']),
                'subscription_date' => Utils::fillNull($company['subscriptionDate']),
                'animal_health_subscription' => Utils::fillNull($company['animalHealthSubscription']),
                'status' => Utils::fillNull($company['isActive']),
                'address' => array(
                    'street_name' => Utils::fillNull($company['address']['streetName']),
                    'address_number' => Utils::fillNull($company['address']['addressNumber']),
                    'suffix' => Utils::fillNull($company['address']['addressNumber']),
                    'postal_code' => Utils::fillNull($company['address']['postalCode']),
                    'city' => Utils::fillNull($company['address']['city']),
                    'state' => Utils::fillNull($company['address']['state'])
                ),
                'owner' => array(
                    'person_id' => Utils::fillNull($company['owner']['personId']),
                    'email_address' => Utils::fillNull($company['owner']['emailAddress']),
                    'first_name' => Utils::fillNull($company['owner']['firstName']),
                    'last_name' => Utils::fillNull($company['owner']['lastName']),
                ),
                'users' => $users,
                'locations' => $locations,
                'pedigrees' => $pedigrees,
                'unpaid_invoices' => sizeof($invoices)
            );
        }
        return $res;
    }

    /**
     * @param Company $company
     *
     * @return array
     */
    public static function createCompany($company)
    {
        $res = array();

        $res['company_id'] = Utils::fillNull($company->getCompanyId());
        $res['company_name'] = Utils::fillNull($company->getCompanyName());
        $res['telephone_number'] = Utils::fillNull($company->getTelephoneNumber());
        $res['company_relation_number'] = Utils::fillNull($company->getOwner()->getRelationNumberKeeper());
        $res['debtor_number'] = Utils::fillNull($company->getDebtorNumber());
        $res['vat_number'] = Utils::fillNull($company->getVatNumber());
        $res['chamber_of_commerce_number'] = Utils::fillNull($company->getChamberOfCommerceNumber());
        $res['animal_health_subscription'] = Utils::fillNull($company->getAnimalHealthSubscription());
        $res['subscription_date'] = Utils::fillNull($company->getSubscriptionDate());

        $res['owner'] = array(
            'person_id' => $company->getOwner()->getPersonId(),
            'prefix' => $company->getOwner()->getPrefix(),
            'first_name' => $company->getOwner()->getFirstName(),
            'last_name' => $company->getOwner()->getLastName(),
            'email_address' => $company->getOwner()->getEmailAddress(),
        );

        $res['address'] = array(
            'street_name' => Utils::fillNull($company->getAddress()->getStreetName()),
            'address_number' => Utils::fillNull($company->getAddress()->getAddressNumber()),
            'suffix' => Utils::fillNull($company->getAddress()->getAddressNumberSuffix()),
            'postal_code' => Utils::fillNull($company->getAddress()->getPostalCode()),
            'city' => Utils::fillNull($company->getAddress()->getCity()),
            'state' => Utils::fillNull($company->getAddress()->getState()),
            'country' => Utils::fillNull($company->getAddress()->getCountry()),
        );

        $res['billing_address'] = array(
            'street_name' => Utils::fillNull($company->getBillingAddress()->getStreetName()),
            'address_number' => Utils::fillNull($company->getBillingAddress()->getAddressNumber()),
            'suffix' => Utils::fillNull($company->getBillingAddress()->getAddressNumberSuffix()),
            'postal_code' => Utils::fillNull($company->getBillingAddress()->getPostalCode()),
            'city' => Utils::fillNull($company->getBillingAddress()->getCity()),
            'state' => Utils::fillNull($company->getBillingAddress()->getState()),
            'country' => Utils::fillNull($company->getBillingAddress()->getCountry()),
        );

        $invoices = $company->getInvoices();

        $res['invoices'] = [];

        foreach ($invoices as $invoice){
            if ($invoice->isDeleted() == false){
                $res['invoices'][] = InvoiceOutput::createInvoiceOutputNoCompany($invoice);
            }
        }

        $locations = $company->getLocations();
        $res['locations'] = [];
        foreach ($locations as $location) {
            if($location->getIsActive()) {
                $newLocation = array();
                $newLocation['location_id'] = $location->getLocationId();
                $newLocation['ubn'] = $location->getUbn();
                $newLocation['is_active'] = $location->getIsActive();
                $newLocation['address'] = array(
                    'street_name' => Utils::fillNull($location->getAddress()->getStreetName()),
                    'address_number' => Utils::fillNull($location->getAddress()->getAddressNumber()),
                    'suffix' => Utils::fillNull($location->getAddress()->getAddressNumberSuffix()),
                    'postal_code' => Utils::fillNull($location->getAddress()->getPostalCode()),
                    'city' => Utils::fillNull($location->getAddress()->getCity()),
                    'state' => Utils::fillNull($location->getAddress()->getState()),
                    'country' => Utils::fillNull($location->getAddress()->getCountry()),
                );
                $res['locations'][] = $newLocation;
            }
        }

        $users = $company->getCompanyUsers();
        $res['users'] = [];
        foreach ($users as $user) {
            /**
             * @var Client $user
             */

            if($user->getIsActive()) {
                $newUser = array();
                $newUser['person_id'] = $user->getPersonId();
                $newUser['prefix'] = $user->getPrefix();
                $newUser['first_name'] = $user->getFirstName();
                $newUser['last_name'] = $user->getLastName();
                $newUser['email_address'] = $user->getEmailAddress();
                $res['users'][] = $newUser;
            }
        }
        return $res;
    }

    /**
     * @param Company $company
     *
     * @return array
     */
    public static function createCompanyDetails($company)
    {
        $res = array();
        $res['company_id'] = Utils::fillNull($company->getCompanyId());
        $res['company_name'] = Utils::fillNull($company->getCompanyName());
        $res['telephone_number'] = Utils::fillNull($company->getTelephoneNumber());
        $res['owner'] = array(
            'person_id' => $company->getOwner()->getPersonId(),
            'email_address' => Utils::fillNull($company->getOwner()->getEmailAddress()),
            'prefix' => Utils::fillNull($company->getOwner()->getPrefix()),
            'first_name' => Utils::fillNull($company->getOwner()->getFirstName()),
            'last_name' => Utils::fillNull($company->getOwner()->getLastName()),
        );
        $res['status'] = $company->isActive();
        $res['subscription_date'] = Utils::fillNull($company->getSubscriptionDate());
        $res['address'] = array(
            'street_name' => Utils::fillNull($company->getAddress()->getStreetName()),
            'address_number' => Utils::fillNull($company->getAddress()->getAddressNumber()),
            'suffix' => Utils::fillNull($company->getAddress()->getAddressNumberSuffix()),
            'postal_code' => Utils::fillNull($company->getAddress()->getPostalCode()),
            'city' => Utils::fillNull($company->getAddress()->getCity()),
            'state' => Utils::fillNull($company->getAddress()->getState()),
        );

        $liveStockCount = Count::getCompanyLiveStockCount($company);
        $res['livestock'] = array(
            'ram' => array(
                'total' => $liveStockCount['RAM_TOTAL'],
                'less_6_months' => $liveStockCount['RAM_UNDER_SIX'],
                'between_6_12_months' => $liveStockCount['RAM_BETWEEN_SIX_AND_TWELVE'],
                'greater_12_months' => $liveStockCount['RAM_OVER_TWELVE'],
            ),
            'ewe' => array(
                'total' => $liveStockCount['EWE_TOTAL'],
                'less_6_months' => $liveStockCount['EWE_UNDER_SIX'],
                'between_6_12_months' => $liveStockCount['EWE_BETWEEN_SIX_AND_TWELVE'],
                'greater_12_months' => $liveStockCount['EWE_OVER_TWELVE'],
            ),
            'neuter' => array(
                'total' => $liveStockCount['NEUTER_TOTAL'],
                'less_6_months' => $liveStockCount['NEUTER_UNDER_SIX'],
                'between_6_12_months' => $liveStockCount['NEUTER_BETWEEN_SIX_AND_TWELVE'],
                'greater_12_months' => $liveStockCount['NEUTER_OVER_TWELVE'],
            ),
        );

        $res['breeder_numbers'] = array();
        $res['invoices'] = $company->getInvoices();

        /*
         * @var $company Company
         */
        $locations = $company->getLocations();
        $healthInspections = array();
        foreach ($locations as $location) {

            /*
             * @var $location Location
             */
//            $healthInspections[] = $location->getInspections();
        }

        $users = $company->getCompanyUsers();
        $res['users'] = [];
        foreach ($users as $user) {
            /**
             * @var Client $user
             */

            if($user->getIsActive()) {
                $newUser = array();
                $newUser['person_id'] = $user->getPersonId();
                $newUser['prefix'] = $user->getPrefix();
                $newUser['first_name'] = $user->getFirstName();
                $newUser['last_name'] = $user->getLastName();
                $newUser['email_address'] = $user->getEmailAddress();
                $res['users'][] = $newUser;
            }
        }

        $res['health_inspections'] = $healthInspections;

        return $res;
    }

    public static function createCompanyInvoiceOutputList($companies){
        $results = array();
        /** @var Company $company */
        foreach ($companies as $company) {
            if ($company->isActive()) {
                $results[] = self::createCompanyInvoiceOutput($company);
            }
        }
        return $results;
    }

    /**
     * @param Company $company
     * @return array;
     */
    public static function createCompanyInvoiceOutput($company){
        return array(
            'id' => $company->getId(),
            'locations' => LocationOutput::generateInvoiceLocationArrayList($company->getLocations()),
            'company_relation_number' => $company->getCompanyRelationNumber(),
            'chamber_of_commerce_number' => $company->getChamberOfCommerceNumber(),
            'vat_number' => $company->getVatNumber(),
            'company_name' => $company->getCompanyName(),
            'debtor_number' => $company->getDebtorNumber(),
            'owner' => $company->getOwner(),
            'company_address' => AddressOutput::createAddressOutput($company->getAddress()),
            'invoices' => InvoiceOutput::createInvoiceOutputListNoCompany($company->getInvoices()),
        );
    }

    /**
     * @param Company $company
     * @return array;
     */
    public static function createCompanyOutputNoInvoices($company){
        return array(
            'id' => $company->getId(),
            'company_id' => $company->getCompanyId(),
            'locations' => LocationOutput::generateInvoiceLocationArrayList($company->getLocations()),
            'company_relation_number' => $company->getCompanyRelationNumber(),
            'chamber_of_commerce_number' => $company->getChamberOfCommerceNumber(),
            'vat_number' => $company->getVatNumber(),
            'company_name' => $company->getCompanyName(),
            'debtor_number' => $company->getDebtorNumber(),
            'owner' => $company->getOwner(),
            'company_address' => AddressOutput::createAddressOutput($company->getAddress()),
        );
    }

    public static function createCompanyList($companies){
        $results = array();
        /** @var Company $company */
        foreach ($companies as $company) {
            if ($company->isActive()) {
                $results[] = self::createCompanyArrayOutput($company);
            }
        }
        return $results;
    }

    /**
     * @param Company $company
     * @return array;
     */
    public static function createCompanyArrayOutput($company){
        return array(
            'id' => $company->getId(),
            'company_id' => $company->getCompanyId(),
            'locations' => [],
            'company_relation_number' => $company->getCompanyRelationNumber(),
            'chamber_of_commerce_number' => $company->getChamberOfCommerceNumber(),
            'vat_number' => $company->getVatNumber(),
            'company_name' => $company->getCompanyName(),
            'debtor_number' => $company->getDebtorNumber(),
            'owner' => ClientOutput::createOwnerOutput($company->getOwner()),
            'company_address' => AddressOutput::createAddressOutput($company->getAddress()),
            'invoices' => InvoiceOutput::createInvoiceOutputList($company->getInvoices()),
        );
    }
}
